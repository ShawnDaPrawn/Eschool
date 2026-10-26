<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\ContentVersion;
use App\Models\Academics\TeachingGroup;
use App\Models\Learning\TeachingDraft;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\MarkDefinitions;
use App\Services\Growth\TopicGraph;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class TeachingDraftQueries
{
    public function available(User $actor, string $module = 'teaching_assistant'): bool
    {
        try {
            return $this->configuration($actor, $module)['groups'] !== [];
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function configuration(User $actor, string $module = 'teaching_assistant'): array
    {
        $access = app(TeachingDraftAccess::class);
        $fresh = $access->actor($actor, $module);
        $today = app(AcademicAuthority::class)->today();
        $groups = TeachingGroup::where('active', true)->where('session_year_id', schoolSetting('session_year'))->whereHas('subject')
            ->whereHas('allocations', fn ($q) => $q->whereIn('teacher_id', app(AcademicAuthority::class)->teachers($fresh))
                ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today))->with('subject')->orderBy('name')->limit(201)->get();
        abort_if($groups->count() > 200, 422);
        $items = [];
        foreach ($groups as $group) {
            $access->group($fresh, (int) $group->id, module: $module);
            $items[] = ['id' => (int) $group->id, 'name' => $group->name, 'subject_name' => $group->subject->name];
        }
        $access->actor($fresh, $module);

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $fresh->id, 'today' => $today,
            'writable' => app(ModuleGate::class)->writable($module), 'groups' => $items];
    }

    public function choices(User $actor, int $groupId, string $module = 'teaching_assistant'): array
    {
        $access = app(TeachingDraftAccess::class);
        $group = $access->group($actor, $groupId, module: $module);
        $plans = app(MarkDefinitions::class)->plans($group);
        $tasks = [];
        foreach ($plans as $plan) {
            foreach ($plan->tasks as $task) {
                $tasks[] = ['id' => (int) $task->id, 'name' => $task->name, 'term_id' => (int) $plan->term_id];
            }
        }
        $sources = $topics = [];
        if ($module === 'teaching_assistant') {
            $versions = ContentVersion::where('state', 'published')->whereHas('item', fn ($q) => $q->where('active', true)
                ->where('subject_id', $group->subject_id)->where('grade_level_id', $group->grade_level_id)->where('session_year_id', $group->session_year_id)
                ->whereColumn('published_version_id', 'content_versions.id')->whereHas('audienceGroups', fn ($g) => $g->where('teaching_group_id', $groupId)))
                ->latest('id')->limit(201)->get(['id']);
            abort_if($versions->count() > 200, 422);
            foreach ($versions as $version) {
                try {
                    $row = $access->source($actor, $group, (int) $version->id);
                    $sources[] = ['id' => (int) $row->id, 'name' => (string) ($row->metadata['title'] ?? __('Published lesson'))];
                } catch (HttpExceptionInterface|ModelNotFoundException) { /* Not this teacher's authorized source. */
                }
            }
            app(TopicGraph::class)->sync((int) $group->subject_id);
            $nodes = app(TopicGraph::class)->nodes((int) $group->subject_id)->filter(fn ($node) => (int) $node->grade_level_id === (int) $group->grade_level_id
                && $plans->contains(fn ($plan) => (int) $plan->curriculum_plan_id === (int) $node->source?->curriculum_plan_id && (int) $plan->term_id === (int) $node->source?->term_id));
            abort_if($nodes->count() > 200, 422);
            $topics = $nodes->map(fn ($node) => ['id' => (int) $node->id, 'name' => $node->name])->values()->all();
        }
        $access->group($actor, $groupId, module: $module);
        foreach ($sources as $source) {
            $access->source($actor, $group, $source['id']);
        }

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id, 'group_id' => $groupId,
            'sources' => $sources, 'topics' => $topics, 'tasks' => $tasks];
    }

    public function learners(User $actor, int $groupId, int $page = 1): array
    {
        $access = app(TeachingDraftAccess::class);
        $group = $access->group($actor, $groupId);
        $ids = $access->learners($group);
        $people = Students::whereIn('id', $ids)->with('user')->orderBy('id')->paginate(25, ['id', 'user_id'], 'page', max(1, $page));
        $items = $people->map(fn ($person) => ['id' => (int) $person->id, 'name' => $person->user->full_name, 'approved' => app(ApprovalService::class)->approved($person)])->all();
        $freshGroup = $access->group($actor, $groupId);
        $freshIds = $access->learners($freshGroup);
        sort($ids);
        sort($freshIds);
        abort_unless($ids === $freshIds, 409, 'The group changed. Refresh its learners.');

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id, 'group_id' => $groupId,
            'items' => $items, 'page' => $people->currentPage(), 'last_page' => $people->lastPage()];
    }

    public function drafts(User $actor, int $groupId, int $page = 1): array
    {
        app(TeachingDraftAccess::class)->group($actor, $groupId);
        $rows = TeachingDraft::where('author_id', $actor->id)->where('teaching_group_id', $groupId)->whereNull('purged_at')->where('created_at', '>', now('UTC')->subDays(90))
            ->latest('created_at')->orderByDesc('id')->paginate(25, ['id', 'school_id', 'author_id', 'teaching_group_id', 'operation_id', 'kind', 'state', 'revision', 'content_version_id', 'learner_id', 'assessment_task_id'], 'page', max(1, $page));
        $items = $rows->map(fn ($row) => app(TeachingDrafts::class)->metadata($row))->all();
        app(TeachingDraftAccess::class)->group($actor, $groupId);

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id, 'group_id' => $groupId,
            'items' => $items, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()];
    }

    public function receipt(User $actor, string $operation): array
    {
        app(TeachingDraftAccess::class)->actor($actor);
        $row = TeachingDraft::where('author_id', $actor->id)->where('operation_id', $operation)->select('id')->firstOrFail();

        return app(TeachingDrafts::class)->view($actor, $row->id);
    }
}
