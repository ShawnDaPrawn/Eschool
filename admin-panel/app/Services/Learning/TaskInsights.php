<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\AssessmentMark;
use App\Models\Academics\QuestionMark;
use App\Models\Academics\TeachingGroup;
use App\Models\Learning\TaskInsight;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\AcademicNumbers;
use App\Services\Academics\MarkDefinitions;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/** Deterministic question evidence; generated explanations are separate teacher-owned drafts. */
class TaskInsights
{
    public function read(User $actor, int $groupId, int $taskId): array
    {
        $access = app(TeachingDraftAccess::class);
        $access->group($actor, $groupId, module: 'test_insights');
        $result = $this->snapshot($groupId, $taskId);
        $access->group($actor, $groupId, module: 'test_insights');

        return $result;
    }

    public function snapshot(int $groupId, int $taskId): array
    {
        return DB::transaction(function () use ($groupId, $taskId) {
            $school = app(CurrentSchool::class);
            abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating(), 403);
            School::active()->lockForUpdate()->findOrFail($school->id());
            $gate = app(ModuleGate::class);
            $gate->forget();
            abort_unless($gate->allows('test_insights'), 403);
            $group = TeachingGroup::findOrFail($groupId);
            $today = app(AcademicAuthority::class)->today();
            abort_unless($group->active && (int) $group->session_year_id === (int) schoolSetting('session_year')
                && substr((string) $group->year?->start_date, 0, 10) <= $today && substr((string) $group->year?->end_date, 0, 10) >= $today, 404);
            $task = app(MarkDefinitions::class)->task($group, $taskId);
            $root = app(MarkDefinitions::class)->canonical($task);
            $roster = app(TeachingDraftAccess::class)->learners($group);
            $questions = $root->questions->sortBy('id')->values();
            abort_if($questions->count() > 100, 422, 'This task exceeds the bounded analysis size.');
            $marks = AssessmentMark::where('assessment_task_id', $root->id)->whereIn('learner_id', $roster)
                ->where('disposition', 'recorded')->whereIn('status', ['captured', 'moderated', 'locked'])->select('id');
            $observed = QuestionMark::whereIn('assessment_mark_id', $marks)->whereIn('assessment_question_id', $questions->pluck('id'))
                ->join('assessment_questions as insight_question', 'insight_question.id', '=', 'question_marks.assessment_question_id')
                ->where('insight_question.school_id', $school->id())
                ->whereNotNull('question_marks.mark_units')->where('question_marks.mark_units', '>=', 0)
                ->whereColumn('question_marks.mark_units', '<=', 'insight_question.max_mark_units')
                ->selectRaw('question_marks.assessment_question_id, COUNT(*) AS evidence_count, SUM(CASE WHEN question_marks.mark_units < insight_question.max_mark_units THEN 1 ELSE 0 END) AS missed_count, SUM(question_marks.mark_units) AS achieved_units')
                ->groupBy('question_marks.assessment_question_id')->get()->keyBy('assessment_question_id');
            $items = [];
            foreach ($questions as $question) {
                $stats = $observed->get($question->id);
                $count = (int) ($stats?->evidence_count ?? 0);
                $items[] = ['question_id' => (int) $question->id, 'number' => (string) $question->number,
                    'topic_keys' => $question->topic_keys ?? [], 'max_mark' => AcademicNumbers::decimal((int) $question->max_mark_units),
                    'evidence_count' => $count, 'unknown_count' => max(0, count($roster) - $count),
                    'missed_count' => (int) ($stats?->missed_count ?? 0),
                    'average_mark' => $count === 0 ? null : AcademicNumbers::decimal(intdiv((int) $stats->achieved_units + intdiv($count, 2), $count))];
            }
            usort($items, fn ($a, $b) => $b['missed_count'] <=> $a['missed_count'] ?: $a['question_id'] <=> $b['question_id']);
            $summary = ['group_id' => $groupId, 'task_id' => $taskId, 'canonical_task_id' => (int) $root->id,
                'learner_count' => count($roster), 'has_question_evidence' => collect($items)->sum('evidence_count') > 0,
                'questions' => $items];
            $fingerprint = hash('sha256', json_encode([$roster, $summary], JSON_THROW_ON_ERROR));
            $snapshot = TaskInsight::where('teaching_group_id', $groupId)->where('assessment_task_id', $taskId)->first();
            if ($snapshot === null || ! hash_equals($snapshot->fingerprint, $fingerprint)) {
                $snapshot = TaskInsight::updateOrCreate(['teaching_group_id' => $groupId, 'assessment_task_id' => $taskId], ['fingerprint' => $fingerprint, 'summary' => $summary]);
            }

            return ['id' => (int) $snapshot->id, 'fingerprint' => $fingerprint, 'computed_at' => $snapshot->updated_at->toIso8601String(), 'summary' => $summary];
        });
    }

    public function group(int $id): void
    {
        $group = TeachingGroup::findOrFail($id);
        foreach (app(MarkDefinitions::class)->plans($group) as $plan) {
            foreach ($plan->tasks as $task) {
                $this->snapshot($id, (int) $task->id);
            }
        }
    }
}
