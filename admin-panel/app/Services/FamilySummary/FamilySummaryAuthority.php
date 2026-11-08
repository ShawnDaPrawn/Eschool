<?php

declare(strict_types=1);

namespace App\Services\FamilySummary;

use App\Models\Parents;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\ReleasedMarks;
use App\Services\Academics\Transitions\LearnerAcademicAccess;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Who a daily family summary part may be about, in the CURRENT school only
 * (Action Plan 5.11). Every read refreshes the account, the Parent role at
 * this school and the actual guardian relationship through the existing
 * family authority (GuardianService, ReleasedMarks::learner). Platform
 * staff, impersonation and across-schools contexts are refused.
 */
class FamilySummaryAuthority
{
    public function guardian(User $actor): User
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && ! $school->isSupportSession()
            && $school->school()?->refresh()?->isActive(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor(User::findOrFail($actor->id));
        abort_if((bool) $fresh->password_change_required, 403);
        abort_unless(in_array('Parent', $policy->roles($fresh), true), 403);

        return $fresh;
    }

    /** The guardian's current children at this school, each with fresh rights. */
    public function children(User $actor): Collection
    {
        $fresh = $this->guardian($actor);
        $parent = (int) (Parents::where('user_id', $fresh->id)->value('id') ?? 0);

        return app(GuardianService::class)->childrenFor((int) $fresh->id, $parent)->orderBy('students.id')->get()
            ->filter(fn (Students $learner) => $this->allows($fresh, (int) $learner->id))->values();
    }

    /** One child, freshly: the guardian relationship here and a learner who has not left. */
    public function child(User $fresh, int $learnerId): Students
    {
        $learner = app(ReleasedMarks::class)->learner($fresh, $learnerId);
        abort_unless((int) $learner->school_id === app(CurrentSchool::class)->id(), 404);
        abort_if(app(LearnerAcademicAccess::class)->departed($learner), 404);

        return $learner;
    }

    public function allows(User $fresh, int $learnerId): bool
    {
        try {
            $this->child($fresh, $learnerId);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
