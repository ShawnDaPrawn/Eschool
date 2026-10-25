<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Academics\AssessmentTask;
use App\Models\Academics\CurriculumTopic;
use App\Models\Academics\MarkReleaseEntry;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\MasteryRecord;
use App\Models\Subject;
use App\Models\User;
use App\Policies\Growth\MasteryPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\MarkDefinitions;
use App\Services\Platform\Tenancy\CurrentSchool;

class MasteryService
{
    public function view(User $actor, int $learnerId, int $subjectId, ?int $groupId = null): array
    {
        $schoolId = app(CurrentSchool::class)->id();
        Subject::query()->findOrFail($subjectId);
        $audience = $groupId === null ? 'family' : 'staff';
        $contexts = [];
        if ($groupId !== null) {
            $group = app(MasteryPolicy::class)->staff($actor, $groupId, $learnerId);
            abort_unless((int) $group->subject_id === $subjectId, 404);
            $contexts[] = $group;
        } else {
            app(MasteryPolicy::class)->family($actor, $learnerId);
            $today = app(AcademicAuthority::class)->today();
            $groupIds = TeachingGroupMembership::query()->where('learner_id', $learnerId)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)
                ->whereHas('group', fn ($q) => $q->where('active', true)->where('subject_id', $subjectId))->pluck('teaching_group_id');
            $contexts = TeachingGroup::query()->whereIn('id', $groupIds)->get()->all();
            $enrolled = $contexts !== [];
            $released = MarkReleaseEntry::query()->where('learner_id', $learnerId)->whereHas('release', fn ($q) => $q->where('subject_id', $subjectId))->exists();
            abort_unless($enrolled || $released, 404);
        }
        $graph = app(TopicGraph::class)->data($subjectId);
        $keys = [];
        foreach ($contexts as $context) {
            foreach (app(MarkDefinitions::class)->plans($context) as $plan) {
                $keys = array_merge($keys, CurriculumTopic::query()->where('curriculum_plan_id', $plan->curriculum_plan_id)->where('term_id', $plan->term_id)->pluck('key')->all());
            }
        }
        if ($contexts === []) {
            $releasedEntries = MarkReleaseEntry::query()->where('learner_id', $learnerId)->whereHas('release', fn ($q) => $q->where('subject_id', $subjectId))->with('snapshot')->latest('id')->limit(100)->get();
            $taskIds = $releasedEntries->flatMap(fn ($entry) => array_column($entry->snapshot->inputs['tasks'] ?? [], 'id'))->unique()->all();
            foreach (AssessmentTask::withTrashed()->with(['questions' => fn ($query) => $query->withTrashed()])->whereIn('id', $taskIds)->get() as $task) {
                $keys = array_merge($keys, $task->topic_keys ?? [], $task->questions->flatMap(fn ($question) => $question->topic_keys ?? [])->all());
            }
        }
        $targetIds = array_column(array_values(array_filter($graph['nodes'], fn ($node) => in_array($node['topic_key'], $keys, true))), 'id');
        $visible = array_fill_keys($targetIds, true);
        $adjacency = [];
        foreach ($graph['links'] as $link) {
            $adjacency[$link['topic_id']][] = $link['prerequisite_id'];
        }
        $pending = $targetIds;
        while ($pending !== []) {
            $id = array_pop($pending);
            foreach ($adjacency[$id] ?? [] as $next) {
                if (! isset($visible[$next])) {
                    $visible[$next] = true;
                    $pending[] = $next;
                }
            }
        }
        $graph['nodes'] = array_values(array_filter($graph['nodes'], fn ($node) => isset($visible[$node['id']])));
        $enabled = $audience === 'staff' || $graph['settings']['family_marks_enabled'];
        $records = $enabled ? MasteryRecord::query()->where('learner_id', $learnerId)->where('subject_id', $subjectId)->where('audience', $audience)
            ->whereIn('topic_id', array_column($graph['nodes'], 'id'))->get()->keyBy('topic_id') : collect();
        $topics = [];
        foreach ($graph['nodes'] as $node) {
            $record = $records->get($node['id']);
            $topics[] = $node + ['level' => $record?->level, 'confidence_bp' => $record?->confidence_bp ?? 0,
                'evidence_count' => $record?->evidence_count ?? 0, 'computed_at' => $record?->computed_at?->toISOString()];
        }

        abort_unless(app(CurrentSchool::class)->id() === $schoolId, 403);
        abort_if($audience === 'family' && $enabled && ! app(TopicGraph::class)->settings()['family_marks_enabled'], 403, 'Mastery visibility changed. Reload before reading.');
        if ($groupId !== null) {
            app(MasteryPolicy::class)->staff($actor, $groupId, $learnerId);
        } else {
            app(MasteryPolicy::class)->family($actor, $learnerId);
        }

        return ['learner_id' => $learnerId, 'subject_id' => $subjectId, 'audience' => $audience, 'marks_visible' => $enabled,
            'policy_version' => MasteryScore::VERSION, 'topics' => $topics, 'target_topic_ids' => $targetIds, 'priorities' => $this->roots($topics, $graph['links'], $targetIds)];
    }

    /** A supervisor supplies an authorized group; family calls remain own-child only. */
    public function priorities(User $actor, int $learnerId, int $subjectId, ?int $groupId = null): array
    {
        return $this->view($actor, $learnerId, $subjectId, $groupId)['priorities'];
    }

    public function roots(array $topics, array $links, ?array $targetIds = null): array
    {
        $nodes = array_column($topics, null, 'id');
        $edges = [];
        foreach ($links as $link) {
            if (isset($nodes[$link['topic_id']], $nodes[$link['prerequisite_id']])) {
                $edges[$link['topic_id']][] = $link['prerequisite_id'];
            }
        }
        if ($targetIds !== null) {
            $topics = array_values(array_filter($topics, fn ($topic) => in_array($topic['id'], $targetIds, true)));
        }
        usort($topics, fn ($a, $b) => ($a['level'] ?? -1) <=> ($b['level'] ?? -1) ?: $a['confidence_bp'] <=> $b['confidence_bp'] ?: $a['id'] <=> $b['id']);
        $found = [];
        foreach ($topics as $topic) {
            if (($topic['level'] ?? -1) >= 3) {
                continue;
            }
            $stack = [[$topic['id']]];
            $visited = [];
            while ($stack !== []) {
                $path = array_pop($stack);
                $id = $path[array_key_last($path)];
                if (isset($visited[$id]) || count($path) > 100) {
                    continue;
                }
                $visited[$id] = true;
                $weak = array_values(array_filter($edges[$id] ?? [], fn ($next) => ($nodes[$next]['level'] ?? -1) < 3 && ! in_array($next, $path, true)));
                if ($weak === []) {
                    $node = $nodes[$id];
                    $found[$id] ??= ['topic_id' => $id, 'name' => $node['name'], 'level' => $node['level'], 'confidence_bp' => $node['confidence_bp'],
                        'reason' => $node['level'] === null ? 'not_yet_assessed' : 'prerequisite_gap', 'path' => $path];
                } else {
                    foreach ($weak as $next) {
                        $stack[] = [...$path, $next];
                    }
                }
                if (count($found) >= 3) {
                    break 2;
                }
            }
        }

        return array_values($found);
    }
}
