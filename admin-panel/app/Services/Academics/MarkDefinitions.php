<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\AssessmentPlan;
use App\Models\Academics\AssessmentTask;
use App\Models\Academics\CurriculumPlan;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Term;
use Illuminate\Support\Collection;

/** Select published definitions independently per term; canonical IDs never cross years. */
class MarkDefinitions
{
    public function plans(TeachingGroup $group): Collection
    {
        return AssessmentPlan::query()->where('state', 'published')->whereHas('curriculum', fn ($q) => $q->where('state', 'published')
            ->where('subject_id', $group->subject_id)->where('grade_level_id', $group->grade_level_id)->where('session_year_id', $group->session_year_id))
            ->with('curriculum', 'term', 'tasks.questions')->get()->groupBy('term_id')
            ->map(fn ($versions) => $versions->sortByDesc(fn ($p) => $p->curriculum->version)->first())->values();
    }

    public function term(TeachingGroup $group, int $termId): AssessmentPlan
    {
        $grade = GradeLevel::query()->findOrFail($group->grade_level_id);
        Term::query()->where('session_year_id', $group->session_year_id)->forGrade((int) $grade->sort_order)->findOrFail($termId);
        $plan = $this->plans($group)->firstWhere('term_id', $termId);
        abort_if($plan === null, 409, __('Publish an assessment plan for this term before capturing marks.'));

        return $plan;
    }

    public function task(TeachingGroup $group, int $taskId): AssessmentTask
    {
        $task = AssessmentTask::query()->with('assessment')->whereHas('assessment.curriculum', fn ($q) => $q->where('subject_id', $group->subject_id)
            ->where('grade_level_id', $group->grade_level_id)->where('session_year_id', $group->session_year_id))->findOrFail($taskId);
        $plan = $this->term($group, (int) $task->assessment->term_id);
        $current = $plan->tasks->firstWhere('id', $taskId);
        abort_if($current === null, 409, __('Assessment definitions changed. Reload before saving marks.'));
        $current->setRelation('assessment', $plan);

        return $current;
    }

    public function canonical(AssessmentTask $task): AssessmentTask
    {
        $root = AssessmentTask::withTrashed()->findOrFail($task->source_task_id ?? $task->id);
        $definition = AssessmentPlan::withTrashed()->findOrFail($root->assessment_plan_id);
        $original = CurriculumPlan::withTrashed()->findOrFail($definition->curriculum_plan_id);
        $current = $task->assessment->curriculum;
        abort_unless((int) $definition->term_id === (int) $task->assessment->term_id && (int) $original->subject_id === (int) $current->subject_id
            && (int) $original->grade_level_id === (int) $current->grade_level_id && (int) $original->session_year_id === (int) $current->session_year_id, 409, __('Invalid task provenance. Review the published definitions.'));
        $root->setRelation('questions', $root->questions()->withTrashed()->get());
        $old = $root->questions->mapWithKeys(fn ($q) => [$q->number => $q->max_mark_units])->all();
        $new = $task->questions->mapWithKeys(fn ($q) => [$q->number => $q->max_mark_units])->all();
        ksort($old);
        ksort($new);
        abort_unless($root->max_mark_units === $task->max_mark_units && $old === $new, 409, __('Task maxima changed without compatible provenance.'));

        return $root;
    }
}
