<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\TeachingGroup;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Students;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\MarkDefinitions;
use App\Services\Academics\ReleasedMarks;
use App\Services\Growth\TopicGraph;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExamCoachAccess
{
    public function availableStaff(User $actor): bool
    {
        if (! app(ModuleGate::class)->allows('exam_coach')) {
            return false;
        }
        try {
            $fresh = $this->actor($actor);

            return in_array('Teacher', app(ProfilePolicy::class)->roles($fresh), true);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function actor(User $actor, bool $write = false): User
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->isActive(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required || PlatformStaffIdentity::where('user_id', $fresh->id)->where('active', true)->exists(), 403);
        app(ApprovalService::class)->assertAccess($fresh);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('exam_coach') : $gate->allows('exam_coach'), 403);

        return $fresh;
    }

    public function staff(User $actor, int $id, bool $write = false): TeachingGroup
    {
        $fresh = $this->actor($actor, $write);
        $group = app(AcademicAuthority::class)->group($fresh, $id, true);
        $this->current($group);

        return $group;
    }

    public function learner(User $actor): Students
    {
        $fresh = $this->actor($actor);
        abort_unless(in_array('Student', app(ProfilePolicy::class)->roles($fresh), true)
            && app(CurrentLearnerMembership::class)->users()->whereKey($fresh->id)->exists(), 404);
        $person = Students::where('user_id', $fresh->id)->firstOrFail();
        app(ReleasedMarks::class)->learner($fresh, (int) $person->id);
        abort_unless(app(ApprovalService::class)->approved($person), 403);

        return $person;
    }

    public function learnerGroup(User $actor, int $id, bool $write = false): TeachingGroup
    {
        $this->actor($actor, $write);
        $learner = $this->learner($actor);
        abort_unless(in_array($id, app(ContentPolicy::class)->learnerGroups($actor, (int) $learner->id), true), 404);
        $group = TeachingGroup::findOrFail($id);
        $this->current($group);

        return $group;
    }

    private function current(TeachingGroup $group): void
    {
        $today = app(AcademicAuthority::class)->today();
        abort_unless($group->active && $group->subject()->exists() && (int) $group->session_year_id === (int) schoolSetting('session_year')
            && substr((string) $group->year?->start_date, 0, 10) <= $today && substr((string) $group->year?->end_date, 0, 10) >= $today, 404);
    }

    /** Only published source nodes for the group's current curriculum terms. */
    public function topics(TeachingGroup $group): array
    {
        $plans = app(MarkDefinitions::class)->plans($group);

        return app(TopicGraph::class)->nodes((int) $group->subject_id)->filter(function ($node) use ($group, $plans) {
            $source = $node->source;

            return (int) $node->grade_level_id === (int) $group->grade_level_id && $source !== null
                && $plans->contains(fn ($plan) => (int) $plan->curriculum_plan_id === (int) $source->curriculum_plan_id && (int) $plan->term_id === (int) $source->term_id);
        })->mapWithKeys(fn ($node) => [(int) $node->id => ['id' => (int) $node->id, 'name' => $node->name]])->all();
    }

    public function frameworks(TeachingGroup $group): array
    {
        $values = app(MarkDefinitions::class)->plans($group)->map(fn ($plan) => $plan->curriculum->standard_snapshot['framework'] ?? 'school')->unique()->values()->all();

        return $values === [] ? ['school'] : $values;
    }
}
