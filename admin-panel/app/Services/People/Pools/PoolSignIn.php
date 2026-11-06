<?php

declare(strict_types=1);

namespace App\Services\People\Pools;

use App\Http\Middleware\RequirePasswordChange;
use App\Models\People\Pools\PoolMember;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\ParentPolicyService;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\PortalSession;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * The pool member sign-in (Action Plan 5.6), built like the facilitator
 * sign-in: it names the school, checks the credentials, and issues nothing
 * until the starting password is changed, the current policies are accepted
 * and the person has given consent to joining the pool, all checked again
 * under a lock and completed in one transaction. Consent is recorded here,
 * by the person, never by the school. App only for now: a web portal
 * sign-in is refused, so no bearer token reaches a portal's JavaScript.
 */
final class PoolSignIn
{
    public const CONSENT = 'pool_consent';

    public function __construct(private readonly PoolAccess $access, private readonly ParentPolicyService $policies) {}

    private function text(string $html): string
    {
        $html = (string) preg_replace('#<(?:br\\s*/?|/(?:p|div|h[1-6]|li))\\s*>#i', "\n", $html);

        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /** @return array<string, mixed> */
    private function requirements(User $user, PoolMember $member, bool $password): array
    {
        $status = $this->policies->status($user);
        $documents = [];
        foreach (['navigateed', 'school'] as $key) {
            $document = $status[$key];
            if ($document['required'] && ! $document['accepted']) {
                $documents[] = ['document' => $key, 'version' => $document['version'], 'title' => $key === 'navigateed' ? 'NavigateEd' : $status['school_name'],
                    'text' => $key === 'navigateed' ? $document['body'] : $this->text($document['privacy_policy']."\n\n".$document['terms'])];
            }
        }
        if ($member->consent_status !== PoolMember::GIVEN || ! hash_equals(PoolAccess::consentVersion(), (string) $member->consent_version)) {
            $documents[] = ['document' => self::CONSENT, 'version' => PoolAccess::consentVersion(), 'title' => (string) __('pool_consent_title'), 'text' => PoolAccess::consentText()];
        }

        return ['authenticated' => false, 'token' => null, 'profile' => null, 'password_change_required' => $password, 'documents' => $documents];
    }

    /** @return array<string, mixed> */
    public function signIn(Request $request, array $input): array
    {
        abort_if(PortalSession::isPortal($request), 403, __('pool_app_only'));
        $school = School::active()->where('slug', $input['school'])->first();
        $email = strtolower(trim((string) $input['email']));
        $identity = User::acrossSchools()->where('email', $email)->first();
        $credentials = ['email' => $email, 'password' => $input['password']];
        Event::dispatch(new Attempting('sanctum', $credentials, false));
        if ($school === null || $identity === null || (int) $identity->status !== 1 || ! Hash::check($input['password'], $identity->getAuthPassword())) {
            Event::dispatch(new Failed('sanctum', $identity, $credentials));
            abort(401, 'Invalid sign-in details.');
        }

        $result = app(CurrentSchool::class)->run($school, fn () => DB::transaction(function () use ($identity, $input, $credentials) {
            $user = User::acrossSchools()->whereKey($identity->id)->where('status', 1)->lockForUpdate()->firstOrFail();
            if (! Hash::check($input['password'], $user->getAuthPassword())) {
                Event::dispatch(new Failed('sanctum', $user, $credentials));
                abort(401);
            }
            app(ModuleGate::class)->forget();
            abort_unless(app(ModuleGate::class)->allows(PoolAccess::MODULE), 403);
            abort_if(DB::table('platform_staff_identities')->where('user_id', $user->id)->where('active', true)->exists(), 403);
            $member = PoolMember::where('user_id', $user->id)->lockForUpdate()->first();
            // Someone who withdrew is invited again by the school, not by signing in.
            abort_unless($member !== null && $member->consent_status !== PoolMember::WITHDRAWN, 403, __('pool_not_in_pool'));
            $password = RequirePasswordChange::mustChange($user, $input['password']);
            $requirements = $this->requirements($user, $member, $password);
            $acceptances = $input['acceptances'] ?? [];
            $complete = ! $password || ! empty($input['new_password']);
            foreach ($requirements['documents'] as $document) {
                $complete = $complete && isset($acceptances[$document['document']]) && hash_equals((string) $document['version'], (string) $acceptances[$document['document']]);
            }
            if (! $complete) {
                return $requirements;
            }
            if ($password) {
                $new = (string) $input['new_password'];
                if (RequirePasswordChange::isDefaultPassword($user, $new) || Hash::check($new, $user->getAuthPassword())) {
                    throw ValidationException::withMessages(['new_password' => __('pool_new_password')]);
                }
            }
            foreach ($requirements['documents'] as $document) {
                if ($document['document'] === self::CONSENT) {
                    continue;
                }
                if (! $this->policies->accept($user, $document['document'], $document['version'])['ok']) {
                    throw ValidationException::withMessages(['acceptances' => __('pool_terms_changed')]);
                }
            }
            if (in_array(self::CONSENT, array_column($requirements['documents'], 'document'), true)) {
                $before = ['consent_status' => $member->consent_status];
                $member->fill(['consent_status' => PoolMember::GIVEN, 'consent_version' => PoolAccess::consentVersion(), 'consented_at' => now(), 'revision' => $member->revision + 1])->save();
                Audit::record('people_pool.consent_given', $member, $before, ['consent_status' => PoolMember::GIVEN, 'consent_version' => $member->consent_version]);
            }
            if ($password) {
                $user->forceFill(['password' => Hash::make((string) $input['new_password']), 'password_change_required' => false])->save();
                AppTokens::revokeAll($user);
            }
            // One pool session per account, bound to this school.
            AppTokens::revokeApp($user, AppTokens::POOL);
            $token = $user->createToken(AppTokens::POOL, [PoolAccess::ability((int) app(CurrentSchool::class)->id())])->plainTextToken;

            return ['authenticated' => true, 'token' => $token, 'profile' => $this->profile($user), 'password_change_required' => false, 'documents' => []];
        }, 3));
        if ($result['authenticated']) {
            Event::dispatch(new Login('sanctum', $identity, false));
        }

        return $result;
    }

    /** @return array<string, mixed> */
    public function profile(User $user): array
    {
        $member = $this->access->member($user);
        $school = app(CurrentSchool::class)->school();
        $today = $this->access->today();

        return ['user_id' => (int) $user->id, 'name' => trim($user->first_name.' '.$user->last_name), 'school' => ['id' => (int) $school->id, 'name' => $school->name, 'slug' => $school->slug],
            'pool_member_id' => (int) $member->id, 'kind' => $member->kind, 'offers_career_talk' => $member->offers_career_talk, 'offers_job_shadow' => $member->offers_job_shadow,
            'consented_at' => $member->consented_at?->toIso8601String(), 'vetting_status' => $member->vetting_status, 'vetting_expires_on' => $member->vetting_expires_on?->toDateString(),
            'vetted' => $member->vettedOn($today)];
    }
}
