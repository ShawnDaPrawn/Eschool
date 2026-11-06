<?php

declare(strict_types=1);

namespace App\Services\People\Pools;

use App\Models\People\Pools\PoolMember;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\ParentPolicyService;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Who may do what with the alumni and volunteer pool (Action Plan 5.6).
 *
 * Staff: a current school role that is not only Parent or Student, plus
 * people-pool-manage (invite, book) or people-pool-vet (record vetting).
 *
 * Pool members, following the facilitator profile: a dedicated account with
 * no school membership and no role, signed in through the pool sign-in to a
 * people-pool-app token bound to one school. That token opens the pool
 * member endpoints only (PoolMemberContext); SetSchoolContext refuses it
 * everywhere else, and without a membership no other school route would let
 * the account in anyway. Every pool member request re-reads the person's
 * consent, account, password and policy state, so a withdrawal or a change
 * takes effect on the next request.
 */
final class PoolAccess
{
    public const MANAGE = 'people-pool-manage';

    public const VET = 'people-pool-vet';

    public const MODULE = 'people_pool';

    private const ABILITY = 'people-pool:school:';

    public function today(): string
    {
        return now(app(CurrentSchool::class)->school()?->timezone ?? 'Africa/Johannesburg')->toDateString();
    }

    /** @param  string|list<string>  $permission  one permission, or a list of which any one will do */
    public function manager(User $actor, bool $write = false, string|array $permission = self::MANAGE): User
    {
        abort_unless(app(CurrentSchool::class)->school()?->isActive(), 404);
        app(ModuleGate::class)->forget();
        abort_unless($write ? app(ModuleGate::class)->writable(self::MODULE) : app(ModuleGate::class)->allows(self::MODULE), 403);
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($actor);
        app(ApprovalService::class)->assertAccess($fresh);
        abort_if(array_diff($policy->roles($fresh), ['Parent', 'Student']) === [], 403);
        $held = false;
        foreach ((array) $permission as $one) {
            $held = $held || $policy->permission($fresh, $one);
        }
        abort_unless($held, 403);

        return $fresh;
    }

    public function mayVet(User $actor): bool
    {
        return app(ProfilePolicy::class)->permission($actor, self::VET);
    }

    /** The token ability that binds a pool session to one school. */
    public static function ability(int $schoolId): string
    {
        return self::ABILITY.$schoolId;
    }

    /** The school a pool token was issued for, or null for any other credential. */
    public function tokenSchool(Request $request): ?int
    {
        $token = $request->user()?->currentAccessToken();
        if (! $token instanceof PersonalAccessToken || $token->name !== AppTokens::POOL) {
            return null;
        }
        foreach ((array) $token->abilities as $ability) {
            if (is_string($ability) && preg_match('/^'.preg_quote(self::ABILITY, '/').'([1-9][0-9]{0,17})$/D', $ability, $match) === 1) {
                return (int) $match[1];
            }
        }

        return null;
    }

    public function ownsSession(Request $request): bool
    {
        $token = $request->user()?->currentAccessToken();

        return $token instanceof PersonalAccessToken && $token->name === AppTokens::POOL;
    }

    /** A pool token reaches the pool member endpoints only. Called by SetSchoolContext. */
    public function refuseOutsidePool(Request $request): void
    {
        abort_if($this->ownsSession($request), 403, 'This sign-in is for the alumni and volunteer pool only.');
    }

    /**
     * The signed-in pool member at the current school, read fresh: an active
     * account that is not platform staff, consent given to the current text,
     * password set and policies accepted, and the pool switched on.
     */
    public function member(User $user): PoolMember
    {
        abort_unless(app(CurrentSchool::class)->school()?->isActive(), 403);
        app(ModuleGate::class)->forget();
        abort_unless(app(ModuleGate::class)->allows(self::MODULE), 403);
        // The pool row links the account to this school; the account itself
        // has no membership, so it is read by id, not through MemberScope.
        $fresh = User::acrossSchools()->whereKey($user->id)->where('status', 1)->firstOrFail();
        abort_if(DB::table('platform_staff_identities')->where('user_id', $fresh->id)->where('active', true)->exists(), 403);
        $member = PoolMember::where('user_id', $fresh->id)->first();
        abort_unless($member !== null && $member->consentCurrent(), 403, __('pool_consent_needed'));
        abort_if($fresh->password_change_required || app(ParentPolicyService::class)->needsAcceptance($fresh), 403, __('pool_setup_needed'));

        return $member;
    }

    /** The consent text everyone in the pool agrees to. */
    public static function consentText(): string
    {
        return (string) __('pool_consent_text');
    }

    /** Its version: the English text's hash, so any change to the wording asks again. */
    public static function consentVersion(): string
    {
        return hash('sha256', (string) trans('pool_consent_text', [], 'en'));
    }
}
