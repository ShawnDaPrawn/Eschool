<?php

declare(strict_types=1);

namespace App\Services\Learning\Diary;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

/**
 * The diary is the signed-in learner's alone. Only a current, guardian-approved
 * Student of the selected school reaches it, never across schools, while
 * impersonating, as platform staff, or as a parent, teacher or other staff role
 * (a person with another role as well is refused, so a shared account cannot
 * become a way in).
 */
class DiaryAccess
{
    public function learner(User $actor, bool $write = false): Students
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->isActive(), 403);
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($actor);
        abort_if($fresh->password_change_required || PlatformStaffIdentity::where('user_id', $fresh->id)->where('active', true)->exists(), 403);
        app(ApprovalService::class)->assertAccess($fresh);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('diary') : $gate->allows('diary'), 403);

        abort_unless($policy->roles($fresh) === ['Student'], 403);
        abort_unless(app(CurrentLearnerMembership::class)->users()->whereKey($fresh->id)->exists(), 404);
        $learner = Students::where('user_id', $fresh->id)->firstOrFail();
        abort_unless(app(ApprovalService::class)->approved($learner), 403, 'Guardian approval required.');

        return $learner;
    }
}
