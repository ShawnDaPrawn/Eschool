<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\TeachingGroup;
use App\Models\Growth\MasteryTopic;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\MarkDefinitions;
use App\Services\Academics\ReleasedMarks;
use App\Services\Growth\TopicGraph;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

class PracticeAccess
{
    public function actor(User $actor, bool $write = false): User
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->isActive(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required || PlatformStaffIdentity::where('user_id', $fresh->id)->where('active', true)->exists(), 403);
        app(ApprovalService::class)->assertAccess($fresh);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('daily_practice') : $gate->allows('daily_practice'), 403);

        return $fresh;
    }

    public function staff(User $actor, int $group, bool $write = false): TeachingGroup
    {
        $fresh = $this->actor($actor, $write);
        $row = app(AcademicAuthority::class)->group($fresh, $group, true);
        $this->current($row);

        return $row;
    }

    public function learner(User $actor, int $learner, int $group, bool $write = false): TeachingGroup
    {
        $fresh = $this->actor($actor, $write);
        $person = app(ReleasedMarks::class)->learner($fresh, $learner);
        abort_unless((int) $person->user_id === (int) $fresh->id && in_array('Student', app(ProfilePolicy::class)->roles($fresh), true), 404);
        abort_unless(app(ApprovalService::class)->approved($person), 403);
        abort_unless(in_array($group, app(ContentPolicy::class)->learnerGroups($fresh, $learner), true), 404);
        $row = TeachingGroup::findOrFail($group);
        $this->current($row);

        return $row;
    }

    private function current(TeachingGroup $group): void
    {
        $today = app(AcademicAuthority::class)->today();
        abort_unless($group->active && (int) $group->session_year_id === (int) schoolSetting('session_year')
            && substr((string) $group->year?->start_date, 0, 10) <= $today && substr((string) $group->year?->end_date, 0, 10) >= $today, 404);
    }

    public function topic(TeachingGroup $group, int $topic, bool $refresh = false): MasteryTopic
    {
        if ($refresh) {
            app(TopicGraph::class)->sync((int) $group->subject_id);
        }
        $node = app(TopicGraph::class)->nodes((int) $group->subject_id)->firstWhere('id', $topic);
        abort_if($node === null || (int) $node->grade_level_id !== (int) $group->grade_level_id, 404);
        $source = $node->source;
        $plans = app(MarkDefinitions::class)->plans($group);
        abort_unless($source !== null && $plans->contains(fn ($p) => (int) $p->curriculum_plan_id === (int) $source->curriculum_plan_id && (int) $p->term_id === (int) $source->term_id), 404);

        return $node;
    }
}
