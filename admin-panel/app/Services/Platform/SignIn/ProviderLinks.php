<?php

declare(strict_types=1);

namespace App\Services\Platform\SignIn;

use App\Http\Middleware\EnsureAccountActive;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\SignIn\SignInProviderLink;
use App\Models\SignIn\SignInProviderSetting;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Google and Microsoft accounts linked to NavigateEd accounts, one school at
 * a time (Action Plan 5.9). Always in the current school: a link made at one
 * school never opens another.
 *
 * - Only the person links their own account: signed in with their password,
 *   they confirm it again, and the provider's verified email must be their
 *   account's email. Nothing is ever created or linked from an email alone.
 * - The link keeps the provider's stable subject (Google sub; Microsoft oid
 *   with its tid). Sign-in matches the subject, so an email changed at the
 *   provider can never move the link to another account.
 * - A subject links to one account per school, and an account to one
 *   subject per provider and school.
 * - The person removes their own links; the school's Super Admin can remove
 *   anyone's at that school. Both are audited, and a removed link ends the
 *   sessions it opened (ProviderSessionScope).
 */
final class ProviderLinks
{
    public function __construct(
        private readonly ProviderSettings $settings,
        private readonly CurrentSchool $currentSchool,
    ) {}

    public function find(ProviderIdentity $identity): ?SignInProviderLink
    {
        return SignInProviderLink::query()->where('provider', $identity->provider)
            ->where('tenant_id', $identity->tenantId)->where('subject', $identity->subject)->first();
    }

    /** @return list<array{id: int, provider: string, email: string, linked_at: ?string, last_used_at: ?string}> the person's links at the current school */
    public function mine(User $user): array
    {
        return SignInProviderLink::query()->where('user_id', $user->id)->orderBy('provider')->get()
            ->map(fn (SignInProviderLink $link) => $this->present($link))->values()->all();
    }

    /** @return list<array{id: int, provider: string, email: string, linked_at: ?string, last_used_at: ?string, user_id: int, name: string}> every link at the current school */
    public function atSchool(): array
    {
        return SignInProviderLink::query()->with('user:id,first_name,last_name')->orderBy('provider')->orderBy('id')->limit(500)->get()
            ->map(fn (SignInProviderLink $link) => $this->present($link) + ['user_id' => (int) $link->user_id, 'name' => trim(($link->user?->first_name ?? '').' '.($link->user?->last_name ?? ''))])
            ->values()->all();
    }

    public function link(User $user, SignInProviderSetting $setting, ProviderIdentity $identity, string $via): SignInProviderLink
    {
        if (PlatformStaffIdentity::query()->where('user_id', $user->id)->exists() || ! EnsureAccountActive::isActive($user)) {
            throw new ProviderSignInRefused('account_refused');
        }
        if ($this->settings->allowedRoles($setting, $user) === []) {
            throw new ProviderSignInRefused('role_not_allowed');
        }
        if (! hash_equals(strtolower(trim((string) $user->email)), $identity->email)) {
            throw new ProviderSignInRefused('email_mismatch');
        }

        return DB::transaction(function () use ($user, $identity, $via) {
            $taken = SignInProviderLink::query()->where('provider', $identity->provider)
                ->where('tenant_id', $identity->tenantId)->where('subject', $identity->subject)->lockForUpdate()->first();
            if ($taken !== null && (int) $taken->user_id === (int) $user->id) {
                return $taken;
            }
            if ($taken !== null) {
                throw new ProviderSignInRefused('subject_taken');
            }
            if (SignInProviderLink::query()->where('user_id', $user->id)->where('provider', $identity->provider)->lockForUpdate()->exists()) {
                throw new ProviderSignInRefused('already_linked');
            }
            try {
                $link = SignInProviderLink::query()->create([
                    'user_id' => $user->id, 'provider' => $identity->provider, 'tenant_id' => $identity->tenantId,
                    'subject' => $identity->subject, 'email_at_link' => $identity->email, 'linked_via' => $via,
                ]);
            } catch (QueryException) {
                // Both unique keys hold under a race: the other request won.
                throw new ProviderSignInRefused('subject_taken');
            }
            Audit::record('sign_in.provider_linked', $link, [], ['provider' => $link->provider, 'user_id' => (int) $user->id, 'via' => $via, 'link' => $link->fingerprint()]);

            return $link;
        });
    }

    /** The person removes one of their own links at the current school; anyone else's is not found. */
    public function unlink(User $user, int $linkId): void
    {
        $link = SignInProviderLink::query()->where('user_id', $user->id)->findOrFail($linkId);
        $this->remove($link, 'self');
    }

    /** The school's Super Admin removes someone's link at this school. */
    public function removeForSchool(User $admin, int $linkId): void
    {
        abort_unless($this->isSchoolAdmin($admin), 403);
        $this->remove(SignInProviderLink::query()->findOrFail($linkId), 'school_admin');
    }

    /** Whether the person's linked accounts page has anything for them here: a link, or a provider they may link. */
    public function available(User $user): bool
    {
        if (! $this->currentSchool->isSet() || $this->currentSchool->isImpersonating()) {
            return false;
        }
        if (SignInProviderLink::query()->where('user_id', $user->id)->exists()) {
            return true;
        }
        foreach ($this->settings->offered() as $provider) {
            $setting = $this->settings->usable($provider['provider']);
            if ($setting !== null && $this->settings->allowedRoles($setting, $user) !== []) {
                return true;
            }
        }

        return false;
    }

    public function isSchoolAdmin(User $user): bool
    {
        return $this->currentSchool->isSet() && ! $this->currentSchool->isImpersonating() && $user->hasRole('Super Admin');
    }

    private function remove(SignInProviderLink $link, string $by): void
    {
        DB::transaction(function () use ($link, $by) {
            $link->delete();
            Audit::record('sign_in.provider_unlinked', $link, ['provider' => $link->provider, 'user_id' => (int) $link->user_id, 'link' => $link->fingerprint()], ['by' => $by]);
        });
    }

    /** @return array{id: int, provider: string, email: string, linked_at: ?string, last_used_at: ?string} */
    private function present(SignInProviderLink $link): array
    {
        return [
            'id' => (int) $link->id,
            'provider' => $link->provider,
            'email' => $link->email_at_link,
            'linked_at' => $link->created_at?->toIso8601String(),
            'last_used_at' => $link->last_used_at?->toIso8601String(),
        ];
    }
}
