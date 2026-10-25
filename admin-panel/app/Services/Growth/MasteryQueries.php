<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Academics\MarkRelease;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Subject;
use App\Models\User;
use App\Policies\Growth\MasteryPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Modules\ModuleGate;

class MasteryQueries
{
    public function configuration(User $actor, ?int $yearId = null): array
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($gate->allows('mastery'), 403);
        $fresh = app(AcademicAuthority::class)->staff($actor);
        $policy = app(ProfilePolicy::class);
        $all = $policy->permission($fresh, 'marks-view-all') || $policy->permission($fresh, 'marks-release');
        $query = $all ? TeachingGroup::query() : app(AcademicAuthority::class)->groups($fresh);
        $yearId ??= (int) schoolSetting('session_year');
        SessionYear::query()->findOrFail($yearId);
        $groups = $query->with('subject', 'gradeLevel', 'year')->whereHas('subject')->where('session_year_id', $yearId)->where('active', true)->orderBy('name')->limit(501)->get();
        abort_if($groups->count() > 500, 422, 'Filter the school group catalogue before loading mastery.');
        abort_unless($all || $policy->permission($fresh, 'curriculum-publish') || in_array('Teacher', $policy->roles($fresh), true) || $groups->isNotEmpty(), 403);

        return ['session_year_id' => $yearId, 'can_manage' => $policy->permission($fresh, 'curriculum-publish'), 'settings' => app(TopicGraph::class)->settings(),
            'groups' => $groups->map(fn ($g) => ['id' => (int) $g->id, 'name' => $g->name, 'subject_id' => (int) $g->subject_id,
                'subject_name' => $g->subject->name, 'grade_level_id' => (int) $g->grade_level_id, 'grade_name' => $g->gradeLevel->name,
                'session_year_id' => (int) $g->session_year_id, 'year_name' => $g->year->name])->all()];
    }

    public function roster(User $actor, int $groupId, int $page = 1): array
    {
        app(MasteryPolicy::class)->staff($actor, $groupId);
        $today = app(AcademicAuthority::class)->today();
        $ids = TeachingGroupMembership::query()->where('teaching_group_id', $groupId)->whereDate('starts_on', '<=', $today)
            ->whereDate('ends_on', '>=', $today)->select('learner_id');
        $learners = Students::query()->with('user')->whereIn('id', $ids)->orderBy('id')->paginate(100, ['*'], 'page', max(1, $page));

        app(MasteryPolicy::class)->staff($actor, $groupId);

        return ['group_id' => $groupId, 'current_page' => $learners->currentPage(), 'last_page' => $learners->lastPage(), 'total' => $learners->total(),
            'learners' => $learners->getCollection()->map(fn ($learner) => ['learner_id' => (int) $learner->id,
                'display_name' => $learner->user === null ? __('Archived learner') : trim($learner->user->first_name.' '.$learner->user->last_name)])->all()];
    }

    public function subjects(User $actor, int $learnerId): array
    {
        app(MasteryPolicy::class)->family($actor, $learnerId);
        $today = app(AcademicAuthority::class)->today();
        $groupIds = TeachingGroupMembership::query()->where('learner_id', $learnerId)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('teaching_group_id');
        $current = TeachingGroup::query()->whereIn('id', $groupIds)->where('active', true)->pluck('subject_id')->all();
        $released = MarkRelease::query()->whereHas('entries', fn ($q) => $q->where('learner_id', $learnerId))->pluck('subject_id')->all();
        $subjects = Subject::query()->whereIn('id', array_values(array_unique([...$current, ...$released])))->orderBy('name')->limit(501)->get();
        abort_if($subjects->count() > 500, 422, 'The subject list is too large. Contact your school to review allocations.');
        app(MasteryPolicy::class)->family($actor, $learnerId);

        return ['learner_id' => $learnerId, 'subjects' => $subjects->map(fn ($subject) => ['id' => (int) $subject->id, 'name' => $subject->name])->all()];
    }

    public function graph(User $actor, int $subjectId, ?int $groupId): array
    {
        $policy = app(ProfilePolicy::class);
        $fresh = app(AcademicAuthority::class)->staff($actor);
        if (! $policy->permission($fresh, 'curriculum-publish')) {
            abort_unless($groupId !== null, 403);
            $group = app(MasteryPolicy::class)->staff($fresh, $groupId);
            abort_unless((int) $group->subject_id === $subjectId, 404);
        }

        return app(TopicGraph::class)->data($subjectId);
    }
}
