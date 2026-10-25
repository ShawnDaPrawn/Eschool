<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\ContentVersion;
use App\Models\Academics\TeachingGroup;
use App\Models\Learning\PracticeDailySet;
use App\Models\Students;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\MarkDefinitions;
use App\Services\Growth\TopicGraph;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PracticeChoices
{
    public function availableStaff(User $actor): bool
    {
        try {
            return $this->access($actor)['staff_groups'] !== [];
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function access(User $actor): array
    {
        $fresh = app(PracticeAccess::class)->actor($actor);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        $today = app(AcademicAuthority::class)->today();
        $staff = [];
        if (in_array('Teacher', $roles, true)) {
            $groups = TeachingGroup::where('active', true)->whereHas('subject')->where('session_year_id', (int) schoolSetting('session_year'))
                ->whereHas('allocations', fn ($q) => $q->whereIn('teacher_id', app(AcademicAuthority::class)->teachers($fresh))->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today))
                ->with('subject')->orderBy('name')->limit(201)->get();
            abort_if($groups->count() > 200, 422);
            foreach ($groups as $group) {
                app(PracticeAccess::class)->staff($fresh, (int) $group->id);
                $staff[] = ['id' => (int) $group->id, 'name' => $group->name, 'subject_name' => $group->subject->name];
            }
        }
        $learner = null;
        $family = [];
        if (in_array('Student', $roles, true)) {
            $person = Students::where('user_id', $fresh->id)->firstOrFail();
            $learner = (int) $person->id;
            $ids = app(ContentPolicy::class)->learnerGroups($fresh, $learner);
            foreach (TeachingGroup::whereIn('id', $ids)->whereHas('subject')->with('subject')->orderBy('name')->get() as $group) {
                app(PracticeAccess::class)->learner($fresh, $learner, (int) $group->id);
                $set = PracticeDailySet::where('learner_id', $learner)->where('teaching_group_id', $group->id)->where('practice_on', $today)->first();
                $family[] = ['id' => (int) $group->id, 'name' => $group->name, 'subject_name' => $group->subject->name, 'set_id' => $set === null ? null : (int) $set->id];
            }
        }
        app(PracticeAccess::class)->actor($fresh);

        return ['reader_id' => (int) $fresh->id, 'learner_id' => $learner, 'practice_on' => $today,
            'writable' => app(ModuleGate::class)->writable('daily_practice'), 'staff_groups' => $staff, 'learner_groups' => $family];
    }

    public function staff(User $actor, int $groupId): array
    {
        $group = app(PracticeAccess::class)->staff($actor, $groupId);
        $plans = app(MarkDefinitions::class)->plans($group);
        if (app(ModuleGate::class)->writable('daily_practice')) {
            app(TopicGraph::class)->sync((int) $group->subject_id);
        }
        $topics = app(TopicGraph::class)->nodes((int) $group->subject_id)->filter(fn ($node) => (int) $node->grade_level_id === (int) $group->grade_level_id
            && $plans->contains(fn ($p) => (int) $p->curriculum_plan_id === (int) $node->source?->curriculum_plan_id && (int) $p->term_id === (int) $node->source?->term_id));
        $versions = ContentVersion::where('state', 'published')->whereHas('item', fn ($q) => $q->where('active', true)->where('subject_id', $group->subject_id)
            ->whereColumn('published_version_id', 'content_versions.id')->whereHas('audienceGroups', fn ($g) => $g->where('teaching_group_id', $groupId)))
            ->orderByDesc('id')->limit(501)->get(['id', 'content_item_id']);
        abort_if($versions->count() > 500, 422);
        $sources = [];
        foreach ($versions as $version) {
            try {
                $authorized = app(PracticeAccess::class)->source($actor, $group, (int) $version->id);
                $sources[] = ['id' => (int) $version->id, 'name' => (string) ($authorized->metadata['title'] ?? __('Published lesson'))];
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                continue;
            }
        }
        app(PracticeAccess::class)->staff($actor, $groupId);

        return ['group_id' => $groupId, 'topics' => $topics->map(fn ($topic) => ['id' => (int) $topic->id, 'name' => $topic->name])->values()->all(),
            'sources' => $sources, 'writable' => app(ModuleGate::class)->writable('daily_practice')];
    }
}
