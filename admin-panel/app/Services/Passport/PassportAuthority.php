<?php

declare(strict_types=1);

namespace App\Services\Passport;

use App\Http\Middleware\EnsureUserIsStaff;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Who may open a learner's passport, and as whom (Action Plan 4.10;
 * engineering/specs/people/learner-passport.md):
 *
 * - the learner themself (approved, signed in at a school where they are a learner);
 * - a guardian with an active link that may approve for the learner, at this school;
 * - staff of this school with profile permission (profile-person-view or -edit).
 *
 * The learner is always found by this school's own learner id, never by a
 * LURITS number: the passport is not a lookup tool. Everyone else is refused:
 * another learner or guardian 404 (nothing to say the learner exists), a staff
 * member without profile permission 403, and Navigate Ed staff, impersonation
 * and support sessions 403.
 */
class PassportAuthority
{
    public const VIEW_PERMISSIONS = ['profile-person-view', 'profile-person-edit'];

    public function actor(User $actor): User
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && ! $school->isSupportSession() && $school->school()?->isActive(), 403);
        abort_if(PlatformStaffIdentity::query()->where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required, 403);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($gate->allows('passport'), 403);

        return $fresh;
    }

    /**
     * @return array{actor: User, learner: Students, viewer: 'learner'|'guardian'|'staff'}
     */
    public function resolve(User $actor, int $learnerId): array
    {
        $fresh = $this->actor($actor);
        $policy = app(ProfilePolicy::class);
        $roles = $policy->roles($fresh);
        $learner = Students::query()->find($learnerId);
        if ($learner !== null && (int) $learner->user_id === (int) $fresh->id && in_array('Student', $roles, true)) {
            app(ApprovalService::class)->assertAccess($fresh);

            return ['actor' => $fresh, 'learner' => $learner, 'viewer' => 'learner'];
        }
        if ($learner !== null && in_array('Parent', $roles, true) && GuardianLink::query()->where('learner_id', $learner->id)
            ->where('guardian_user_id', $fresh->id)->where('status', 'active')->where('can_approve', true)->exists()) {
            return ['actor' => $fresh, 'learner' => $learner, 'viewer' => 'guardian'];
        }
        if ($this->isStaff($fresh)) {
            abort_unless($this->staffMayView($fresh), 403);
            abort_if($learner === null, 404);

            return ['actor' => $fresh, 'learner' => $learner, 'viewer' => 'staff'];
        }
        abort(404);
    }

    /** The signed-in learner's own row at this school. */
    public function ownLearnerId(User $actor): int
    {
        $fresh = $this->actor($actor);
        abort_unless(in_array('Student', app(ProfilePolicy::class)->roles($fresh), true), 403);
        $id = Students::query()->where('user_id', $fresh->id)->value('id');
        abort_if($id === null, 404);

        return (int) $id;
    }

    /** Staff of this school who may open passports: for the panel and its menu. */
    public function staff(User $actor): User
    {
        $fresh = $this->actor($actor);
        abort_unless($this->isStaff($fresh) && $this->staffMayView($fresh), 403);

        return $fresh;
    }

    public function available(User $actor): bool
    {
        try {
            $this->staff($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    private function isStaff(User $fresh): bool
    {
        return array_diff(app(ProfilePolicy::class)->roles($fresh), EnsureUserIsStaff::APP_ONLY_ROLES) !== [];
    }

    private function staffMayView(User $fresh): bool
    {
        $policy = app(ProfilePolicy::class);
        foreach (self::VIEW_PERMISSIONS as $permission) {
            if ($policy->permission($fresh, $permission)) {
                return true;
            }
        }

        return false;
    }
}
