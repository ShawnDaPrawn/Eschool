<?php

declare(strict_types=1);

namespace App\Policies\Growth;

use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Students;
use App\Models\User;
use App\Policies\Academics\MarksPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\ReleasedMarks;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;

class MasteryPolicy
{
    private function enabled(): void
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($gate->allows('mastery'), 403, 'The mastery module is disabled.');
    }

    public function staff(User $actor, int $groupId, ?int $learnerId = null): TeachingGroup
    {
        $this->enabled();
        $group = app(MarksPolicy::class)->group($actor, $groupId, 'read');
        if ($learnerId !== null) {
            Students::query()->findOrFail($learnerId);
            $today = app(AcademicAuthority::class)->today();
            abort_unless(TeachingGroupMembership::query()->where('teaching_group_id', $groupId)->where('learner_id', $learnerId)
                ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->exists(), 404);
        }

        return $group;
    }

    public function family(User $actor, int $learnerId): Students
    {
        $this->enabled();
        $learner = app(ReleasedMarks::class)->learner($actor, $learnerId);
        if (in_array('Student', app(ProfilePolicy::class)->roles($actor), true) && (int) $learner->user_id === (int) $actor->id) {
            abort_unless(app(ApprovalService::class)->approved($learner), 403, 'Guardian approval required.');
        }

        return $learner;
    }
}
