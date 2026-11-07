<?php

declare(strict_types=1);

namespace App\Services\Learning\Strengths;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Auth;

/**
 * Who may reach the strengths mirror (Action Plan 4.8).
 *
 * The mirror is the signed-in learner's own. Only a current, guardian-approved
 * Student of the selected school reaches it, in a stage the module allows (no
 * early years), never across schools, while impersonating, as platform staff,
 * or with another role at the school as well (a shared account cannot become
 * a way in). Guardians, teachers, school leaders and HQ have no route to it;
 * a guardian or teacher sees only a copy the learner chose to share with them
 * by name (StrengthsShares::view).
 */
class StrengthsAccess
{
    public const MODULE = 'strengths_mirror';

    public function learner(User $actor, bool $write = false): Students
    {
        $fresh = $this->actor($actor, $write);
        abort_unless(app(ProfilePolicy::class)->roles($fresh) === ['Student'], 403);
        // Stated again here, not only through the request's signed-in user: no early years learner.
        abort_unless(app(ModuleGate::class)->allowsStageOf(self::MODULE, $fresh), 403);
        abort_unless(app(CurrentLearnerMembership::class)->users()->whereKey($fresh->id)->exists(), 404);
        $learner = Students::where('user_id', $fresh->id)->firstOrFail();
        abort_unless(app(ApprovalService::class)->approved($learner), 403, 'Guardian approval required.');

        return $learner;
    }

    /** A signed-in guardian or teacher who may be named on a share; the share itself decides what they see. */
    public function viewer(User $actor): User
    {
        $fresh = $this->actor($actor, false);
        abort_if(array_intersect(app(ProfilePolicy::class)->roles($fresh), ['Parent', 'Teacher']) === [], 403);

        return $fresh;
    }

    private function actor(User $actor, bool $write): User
    {
        // Only for the person making this request: a job, command or another
        // service cannot pass someone else's account in to read their mirror.
        abort_unless(Auth::hasUser() && (int) Auth::id() === (int) $actor->id, 403);
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->isActive(), 403);
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($actor);
        abort_if($fresh->password_change_required || PlatformStaffIdentity::where('user_id', $fresh->id)->where('active', true)->exists(), 403);
        app(ApprovalService::class)->assertAccess($fresh);
        $gate = app(ModuleGate::class);
        $gate->forget();
        // allows() includes the learner's stage: early years learners never reach it.
        abort_unless($write ? $gate->writable(self::MODULE) : $gate->allows(self::MODULE), 403);

        return $fresh;
    }
}
