<?php

declare(strict_types=1);

namespace App\Services\Platform\SignIn;

use App\Http\Middleware\EnsureUserIsStaff;
use App\Models\Platform\Role;
use App\Models\Platform\SchoolMembership;
use App\Models\SignIn\SignInProviderSetting;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Stages\SchoolStages;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Each school's choices for Google and Microsoft sign-in (Action Plan 5.9).
 * Always read and written in the current school.
 *
 * - Off by default. A school switches each provider on itself, and only when
 *   this server has that provider's client (ProviderCatalogue::configured).
 * - Google needs at least one allowed email domain; Microsoft at least one
 *   allowed tenant id (domains are optional there and checked when listed).
 *   Consumer email domains (gmail.com, outlook.com and the like) are refused.
 * - Who: staff roles (every staff role by default, or the ones chosen) and,
 *   only when the school says so, learners of the chosen age stages (high
 *   school by default; early years never sign in themselves). Parents and
 *   the facilitator and vendor profiles never use it. Only current (not
 *   leaver) memberships count.
 * - Only the school's Super Admin changes them, and every change is audited.
 */
final class ProviderSettings
{
    /** Roles that never sign in with a provider. */
    public const NEVER = ['Parent', SchoolRoles::FACILITATOR, SchoolRoles::VENDOR];

    public const LEARNER_STAGES = [SchoolStages::JUNIOR, SchoolStages::HIGH_SCHOOL];

    private const DOMAIN = '/^(?=.{4,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/D';

    private const GUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/D';

    public function __construct(
        private readonly ProviderCatalogue $catalogue,
        private readonly CurrentSchool $currentSchool,
        private readonly MembershipService $memberships,
    ) {}

    /** The current school's row for the provider, or an unsaved one with the defaults. */
    public function for(string $provider): SignInProviderSetting
    {
        return SignInProviderSetting::query()->where('provider', $provider)->first()
            ?? new SignInProviderSetting(['provider' => $provider, 'enabled' => false, 'require_hosted_domain' => true, 'learners_allowed' => false]);
    }

    /** The current school's setting when the provider may be used now: switched on here and set up on this server. */
    public function usable(string $provider): ?SignInProviderSetting
    {
        if (! $this->catalogue->configured($provider)) {
            return null;
        }
        $setting = SignInProviderSetting::query()->where('provider', $provider)->where('enabled', true)->first();

        return $setting !== null && $this->complete($setting) ? $setting : null;
    }

    /** @return list<array{provider: string, label: string}> the providers the current school offers now */
    public function offered(): array
    {
        $offered = [];
        foreach (ProviderCatalogue::ALL as $provider) {
            if ($this->usable($provider) !== null) {
                $offered[] = ['provider' => $provider, 'label' => $this->catalogue->label($provider)];
            }
        }

        return $offered;
    }

    /** @return list<string> */
    public function domains(SignInProviderSetting $setting): array
    {
        return array_values(array_filter(array_map(fn ($d) => strtolower((string) $d), (array) ($setting->allowed_domains ?? []))));
    }

    /** @return list<string> */
    public function tenants(SignInProviderSetting $setting): array
    {
        return array_values(array_filter(array_map(fn ($t) => strtolower((string) $t), (array) ($setting->allowed_tenants ?? []))));
    }

    public function allowsDomain(SignInProviderSetting $setting, string $domain): bool
    {
        return $domain !== '' && in_array(strtolower($domain), $this->domains($setting), true)
            && ! in_array(strtolower($domain), (array) config('sign_in_providers.consumer_domains', []), true);
    }

    public function allowsTenant(SignInProviderSetting $setting, string $tenant): bool
    {
        return $tenant !== '' && in_array(strtolower($tenant), $this->tenants($setting), true);
    }

    /** @return list<string> */
    public function learnerStages(SignInProviderSetting $setting): array
    {
        return $setting->learner_stages === null ? [SchoolStages::HIGH_SCHOOL] : array_values(array_intersect((array) $setting->learner_stages, self::LEARNER_STAGES));
    }

    /**
     * The roles the person holds at the current school, on a current (not
     * leaver, not ended) membership, that this setting lets sign in.
     *
     * @return list<string>
     */
    public function allowedRoles(SignInProviderSetting $setting, User $user): array
    {
        $school = $this->currentSchool->id();
        $held = $this->memberships->active($user)
            ->filter(fn (SchoolMembership $m) => (int) $m->school_id === $school && $m->status === SchoolMembership::ACTIVE)
            ->pluck('role')->unique()->values()->all();
        $allowed = [];
        foreach ($held as $role) {
            if (in_array($role, self::NEVER, true)) {
                continue;
            }
            if ($role === SchoolRoles::STUDENT) {
                if ($setting->learners_allowed && in_array(app(SchoolStages::class)->forUser($user), $this->learnerStages($setting), true)) {
                    $allowed[] = $role;
                }

                continue;
            }
            if ($setting->staff_roles === null || in_array($role, (array) $setting->staff_roles, true)) {
                $allowed[] = $role;
            }
        }

        return $allowed;
    }

    /** @return list<string> the school's staff role names, for the settings page */
    public function staffRoles(): array
    {
        return Role::query()->orderBy('name')->pluck('name')
            ->reject(fn (string $name) => in_array($name, [...EnsureUserIsStaff::APP_ONLY_ROLES, ...self::NEVER], true))
            ->values()->all();
    }

    /**
     * Saves the current school's choice for one provider, after checking it.
     *
     * @param  array{enabled?: bool, allowed_domains?: list<string>, allowed_tenants?: list<string>, require_hosted_domain?: bool, staff_access?: string, staff_roles?: list<string>, learners_allowed?: bool, learner_stages?: list<string>}  $input
     */
    public function update(User $actor, string $provider, array $input): SignInProviderSetting
    {
        abort_unless($this->catalogue->exists($provider), 404);
        $domains = $this->cleanList($input['allowed_domains'] ?? []);
        $tenants = $this->cleanList($input['allowed_tenants'] ?? []);
        $errors = [];
        foreach ($domains as $domain) {
            if (preg_match(self::DOMAIN, $domain) !== 1) {
                $errors['allowed_domains'] = __('":domain" is not an email domain. Write it like school.org.za.', ['domain' => mb_substr($domain, 0, 60)]);
            } elseif (in_array($domain, (array) config('sign_in_providers.consumer_domains', []), true)) {
                $errors['allowed_domains'] = __('Anyone can make an account at :domain, so it cannot be allowed. Use your school\'s own domain.', ['domain' => $domain]);
            }
        }
        foreach ($tenants as $tenant) {
            if (preg_match(self::GUID, $tenant) !== 1) {
                $errors['allowed_tenants'] = __('A tenant id looks like 00000000-0000-0000-0000-000000000000.');
            }
        }
        if (count($domains) > 20 || count($tenants) > 20) {
            $errors['allowed_domains'] = __('List at most 20.');
        }
        $staffRoles = match ($input['staff_access'] ?? 'all') {
            'all' => null,
            'none' => [],
            default => array_values(array_intersect($this->cleanList($input['staff_roles'] ?? [], false), $this->staffRoles())),
        };
        $stages = array_values(array_intersect((array) ($input['learner_stages'] ?? [SchoolStages::HIGH_SCHOOL]), self::LEARNER_STAGES));
        $enabled = (bool) ($input['enabled'] ?? false);
        if ($enabled && ! $this->catalogue->configured($provider)) {
            $errors['enabled'] = __(':provider sign-in is not set up for NavigateEd yet. Ask NavigateEd to set it up first.', ['provider' => $this->catalogue->label($provider)]);
        }
        if ($enabled && $provider === ProviderCatalogue::GOOGLE && $domains === []) {
            $errors['allowed_domains'] = __('List at least one email domain your school manages.');
        }
        if ($enabled && $provider === ProviderCatalogue::MICROSOFT && $tenants === []) {
            $errors['allowed_tenants'] = __('List at least one Microsoft tenant id your school manages.');
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return DB::transaction(function () use ($actor, $provider, $input, $domains, $tenants, $staffRoles, $stages, $enabled) {
            $setting = SignInProviderSetting::query()->where('provider', $provider)->lockForUpdate()->first()
                ?? new SignInProviderSetting(['provider' => $provider]);
            $before = $setting->exists ? $this->describe($setting) : [];
            $setting->fill([
                'enabled' => $enabled,
                'allowed_domains' => $domains,
                'allowed_tenants' => $provider === ProviderCatalogue::MICROSOFT ? $tenants : [],
                'require_hosted_domain' => $provider === ProviderCatalogue::GOOGLE ? (bool) ($input['require_hosted_domain'] ?? true) : false,
                'staff_roles' => $staffRoles,
                'learners_allowed' => (bool) ($input['learners_allowed'] ?? false),
                'learner_stages' => $stages,
                'updated_by' => $actor->id,
            ])->save();
            Audit::record('sign_in.provider_settings_updated', $setting, $before, $this->describe($setting));

            return $setting;
        });
    }

    /** @return array<string, mixed> what the audit log records: choices only, never a secret */
    public function describe(SignInProviderSetting $setting): array
    {
        return [
            'provider' => $setting->provider, 'enabled' => (bool) $setting->enabled,
            'allowed_domains' => $this->domains($setting), 'allowed_tenants' => $this->tenants($setting),
            'require_hosted_domain' => (bool) $setting->require_hosted_domain,
            'staff_roles' => $setting->staff_roles, 'learners_allowed' => (bool) $setting->learners_allowed,
            'learner_stages' => $this->learnerStages($setting),
        ];
    }

    private function complete(SignInProviderSetting $setting): bool
    {
        return $setting->provider === ProviderCatalogue::MICROSOFT ? $this->tenants($setting) !== [] : $this->domains($setting) !== [];
    }

    /** @return list<string> */
    private function cleanList(mixed $value, bool $lower = true): array
    {
        $items = is_string($value) ? preg_split('/[\s,;]+/', $value) : (array) $value;

        return array_values(array_unique(array_filter(array_map(
            fn ($item) => $lower ? ltrim(strtolower(trim((string) $item)), '@') : trim((string) $item),
            $items ?: [],
        ), fn (string $item) => $item !== '')));
    }
}
