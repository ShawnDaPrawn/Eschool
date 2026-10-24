<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\TeachingGroupMembership;
use App\Models\Students;
use App\Models\User;
use App\Policies\Academics\MarksPolicy;

class MarkQueries
{
    /** Own-group roster only; supporting groups share one canonical task record. */
    public function matrix(User $actor, int $groupId, int $termId, int $page = 1, int $perPage = 100): array
    {
        $group = app(MarksPolicy::class)->group($actor, $groupId, 'read');
        $plan = app(MarkDefinitions::class)->term($group, $termId);
        $ids = TeachingGroupMembership::query()->where('teaching_group_id', $groupId)->distinct()->pluck('learner_id')->all();
        $pagination = Students::query()->with('user')->whereIn('id', $ids)->orderBy('id')->paginate(min(200, max(1, $perPage)), ['*'], 'page', max(1, $page));
        $learners = $pagination->getCollection();
        $ids = $learners->pluck('id')->map(fn ($id) => (int) $id)->all();
        $prepared = app(MarkInputs::class)->prepare($group, $termId, $ids);
        $marks = $prepared['marks'];
        $rows = [];
        foreach ($learners as $learner) {
            $input = app(MarkInputs::class)->forLearner($group, (int) $learner->id, $termId, [], $prepared);
            $rows[] = ['learner_id' => (int) $learner->id, 'student_number' => $learner->student_number,
                'display_name' => $learner->user === null ? __('Archived learner') : trim($learner->user->first_name.' '.$learner->user->last_name),
                'marks' => ($marks->get((int) $learner->id, collect()))->values()->map(fn ($mark) => app(MarkRecording::class)->data($mark))->all(),
                'result' => app(MarkInputs::class)->calculate($input)];
        }
        $locked = $plan->term->marks_lock_on !== null && app(AcademicAuthority::class)->today() >= $plan->term->marks_lock_on->toDateString();

        return ['group_id' => $groupId, 'subject_id' => (int) $group->subject_id, 'grade_level_id' => (int) $group->grade_level_id,
            'session_year_id' => (int) $group->session_year_id, 'term_id' => $termId, 'assessment_plan_id' => (int) $plan->id, 'version' => (int) $plan->curriculum->version,
            'current_page' => $pagination->currentPage(), 'last_page' => $pagination->lastPage(), 'total' => $pagination->total(), 'locked' => $locked, 'marks_lock_on' => $plan->term->marks_lock_on?->toDateString(),
            'tasks' => $plan->tasks->map(function ($task) {
                $root = app(MarkDefinitions::class)->canonical($task);
                $rootQuestions = $root->questions->keyBy('number');

                return ['id' => (int) $task->id, 'canonical_task_id' => (int) $root->id, 'name' => $task->name, 'max_mark' => AcademicNumbers::decimal($task->max_mark_units),
                    'component' => $task->component, 'weight_bp' => $task->weight_bp,
                    'questions' => $task->questions->map(fn ($q) => ['id' => (int) $q->id, 'canonical_question_id' => (int) $rootQuestions->get($q->number)->id, 'number' => $q->number, 'max_mark' => AcademicNumbers::decimal($q->max_mark_units)])->all()];
            })->all(), 'learners' => $rows];
    }
}
