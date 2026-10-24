<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\AssessmentMark;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Term;
use Illuminate\Support\Collection;

/** One learner/subject context across dated groups; no duplicate official result. */
class MarkInputs
{
    public function groupIds(TeachingGroup $group): Collection
    {
        return TeachingGroup::query()->where('subject_id', $group->subject_id)->where('grade_level_id', $group->grade_level_id)
            ->where('session_year_id', $group->session_year_id)->pluck('id');
    }

    public function memberships(TeachingGroup $group, ?int $learnerId = null): Collection
    {
        return TeachingGroupMembership::query()->whereIn('teaching_group_id', $this->groupIds($group))
            ->when($learnerId !== null, fn ($q) => $q->where('learner_id', $learnerId))->orderBy('starts_on')->orderBy('id')->get();
    }

    public function prepare(TeachingGroup $group, ?int $termId, ?array $learnerIds = null): array
    {
        $members = $this->memberships($group);
        if ($learnerIds !== null) {
            $members = $members->whereIn('learner_id', $learnerIds)->values();
        }
        $all = app(MarkDefinitions::class)->plans($group);
        $selected = $termId === null ? $all : collect([app(MarkDefinitions::class)->term($group, $termId)]);
        $policy = $termId === null ? $all->sortByDesc(fn ($p) => $p->curriculum->version)->first()?->curriculum : $selected->first()->curriculum;
        $roots = $selected->flatMap(fn ($p) => $p->tasks)->map(fn ($t) => (int) ($t->source_task_id ?? $t->id))->all();
        $marks = AssessmentMark::query()->with('questions')->whereIn('learner_id', $members->pluck('learner_id')->unique())->whereIn('assessment_task_id', $roots)->get()
            ->groupBy('learner_id')->map(fn ($rows) => $rows->keyBy('assessment_task_id'));

        $calendar = Term::query()->where('session_year_id', $group->session_year_id)->forGrade((int) GradeLevel::findOrFail($group->grade_level_id)->sort_order)->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();

        return ['members' => $members, 'selected' => $selected, 'policy' => $policy, 'marks' => $marks, 'calendar_term_ids' => $calendar];
    }

    public function forLearner(TeachingGroup $group, int $learnerId, ?int $termId, array $overrides = [], ?array $prepared = null): array
    {
        $prepared ??= $this->prepare($group, $termId, [$learnerId]);
        $members = $prepared['members']->where('learner_id', $learnerId)->values();
        abort_if($members->isEmpty(), 404);
        $selected = $prepared['selected'];
        $policy = $prepared['policy'];
        $marks = $prepared['marks']->get($learnerId, collect());
        $tasks = [];
        $definitions = [];
        foreach ($selected as $plan) {
            $definitions[] = ['id' => (int) $plan->id, 'curriculum_id' => (int) $plan->curriculum->id, 'version' => $plan->curriculum->version, 'fingerprint' => $plan->curriculum->fingerprint];
            foreach ($plan->tasks as $task) {
                $root = (int) ($task->source_task_id ?? $task->id);
                $mark = $marks->get($root);
                $entry = $overrides[$root] ?? ($mark === null ? null : ['mark_units' => $mark->mark_units, 'disposition' => $mark->disposition, 'revision' => $mark->revision, 'mark_id' => (int) $mark->id, 'status' => $mark->status, 'question_marks' => $mark->questions->map(fn ($q) => ['canonical_question_id' => (int) $q->assessment_question_id, 'mark_units' => $q->mark_units])->all()]);
                $due = $task->due_on?->toDateString() ?? $plan->term->end_date->toDateString();
                $cover = $members->first(fn ($m) => $m->starts_on->toDateString() <= $due && $m->ends_on->toDateString() >= $due);
                if ($entry === null) {
                    $first = $members->first();
                    $last = $members->max(fn ($m) => $m->ends_on->toDateString());
                    $state = $due > $last ? 'excused' : ($due < $first->starts_on->toDateString() ? ($first->mark_rule === 'excused' ? 'excused' : 'catch_up') : 'missing');
                    if ($cover === null && $due >= $first->starts_on->toDateString() && $due <= $last) {
                        $state = 'excused';
                    }
                    $entry = ['mark_units' => null, 'disposition' => $state, 'revision' => 0, 'mark_id' => null];
                }
                $tasks[] = ['id' => (int) $task->id, 'canonical_task_id' => $root, 'name' => $task->name, 'term_id' => (int) $plan->term_id,
                    'component' => $task->component, 'annual_component' => $task->annual_component,
                    'weight_bp' => $task->weight_bp, 'annual_weight_bp' => $task->annual_weight_bp, 'max_mark_units' => $task->max_mark_units] + $entry;
            }
        }
        $components = $termId === null ? ($policy?->annual_components ?? []) : $selected->first()->components;

        return ['subject_id' => (int) $group->subject_id, 'grade_level_id' => (int) $group->grade_level_id, 'session_year_id' => (int) $group->session_year_id,
            'learner_id' => $learnerId, 'term_id' => $termId, 'period_key' => $termId === null ? 'year' : 'term:'.$termId,
            'calendar_term_ids' => $prepared['calendar_term_ids'], 'policy_id' => $policy?->id, 'definitions' => $definitions, 'components' => $components,
            'rounding' => $policy?->rounding ?? 'half_up', 'absent_policy' => $policy?->absent_policy ?? 'incomplete',
            'memberships' => $members->map(fn ($m) => ['id' => (int) $m->id, 'revision' => $m->revision, 'starts_on' => $m->starts_on->toDateString(), 'ends_on' => $m->ends_on->toDateString(), 'mark_rule' => $m->mark_rule])->all(),
            'tasks' => $tasks];
    }

    public function calculate(array $inputs): array
    {
        $result = app(MarkCalculator::class)->calculate($inputs['components'], $inputs['tasks'], $inputs['rounding'], $inputs['absent_policy'], $inputs['term_id'] === null);
        if ($inputs['term_id'] === null && array_diff($inputs['calendar_term_ids'], array_unique(array_column($inputs['tasks'], 'term_id'))) !== []) {
            $result['percentage_bp'] = $result['rating_level'] = null;
            $result['complete'] = false;
            $result['provisional'] = true;
            $result['warnings'][] = 'annual_terms_unpublished';
        }

        return $result;
    }
}
