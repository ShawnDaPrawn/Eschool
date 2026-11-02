<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Academics\AssessmentMark;
use App\Models\Academics\AssessmentQuestion;
use App\Models\Academics\MarkReleaseEntry;
use App\Models\Growth\MasteryEvidence;
use App\Models\Growth\MasteryTopic;
use App\Models\Platform\AuditLog;
use App\Services\Academics\AcademicNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** Reconstruct mutable mark projections from their retained, school-scoped history. */
class MasteryHistory
{
    public function before(int $learnerId, int $subjectId, CarbonImmutable $before): Collection
    {
        $rows = MasteryEvidence::query()->where('learner_id', $learnerId)->where('subject_id', $subjectId)
            ->whereNotIn('source_type', ['question_mark', 'released_question_mark'])->where('observed_at', '<', $before)
            ->get(['id', 'topic_id', 'audience', 'achieved_units', 'maximum_units', 'observed_at']);
        $topics = MasteryTopic::where('subject_id', $subjectId)->get()->keyBy('topic_key');
        $markIds = AssessmentMark::withTrashed()->where('learner_id', $learnerId)
            ->whereHas('group', fn ($q) => $q->withTrashed()->where('subject_id', $subjectId))->pluck('id');
        // AuditLog intentionally has no global tenant scope: constrain it explicitly.
        $marks = AuditLog::where('school_id', app(CurrentSchool::class)->id())
            ->where('subject_type', (new AssessmentMark)->getMorphClass())->whereIn('subject_id', $markIds)
            ->whereIn('action', ['academics.mark_captured', 'academics.mark_moderated'])->where('created_at', '<', $before)
            ->orderByDesc('created_at')->orderByDesc('id')->get()->unique('subject_id');
        foreach ($marks as $mark) {
            $entry = $mark->after ?? [];
            $questions = array_map(fn ($q) => ['canonical_question_id' => $q['canonical_question_id'],
                'mark_units' => $q['mark'] === null ? null : AcademicNumbers::units($q['mark'])], $entry['question_marks'] ?? []);
            $this->append($rows, $topics, $entry, $questions, 'staff', $mark->created_at, (int) $mark->id);
        }
        $entries = MarkReleaseEntry::where('learner_id', $learnerId)->whereHas('release', fn ($q) => $q
            ->where('subject_id', $subjectId)->where('created_at', '<', $before))
            ->with('snapshot', 'release')->orderByDesc('id')->get();
        $periods = [];
        $seen = [];
        foreach ($entries as $entry) {
            $period = $entry->release->period_key;
            if (isset($periods[$period])) {
                continue;
            }
            $periods[$period] = true;
            foreach ($entry->snapshot->inputs['tasks'] ?? [] as $task) {
                $canonical = $task['canonical_task_id'];
                if (isset($seen[$canonical])) {
                    continue;
                }
                $seen[$canonical] = true;
                $this->append($rows, $topics, $task, $task['question_marks'] ?? [], 'family', $entry->release->created_at, (int) $entry->id);
            }
        }

        return $rows->sortByDesc(fn ($row) => [$row->observed_at->getTimestamp(), $row->id])->groupBy('topic_id');
    }

    private function append(Collection $rows, Collection $topics, array $entry, array $marks, string $audience, mixed $observed, int $id): void
    {
        if (! in_array($entry['disposition'] ?? null, ['recorded', 'catch_up'], true)) {
            return;
        }
        $questions = AssessmentQuestion::withTrashed()->where('assessment_task_id', $entry['canonical_task_id'])->get()->keyBy('id');
        foreach ($marks as $mark) {
            $question = $questions->get($mark['canonical_question_id']);
            $score = $mark['mark_units'] ?? null;
            if ($question === null || ! is_int($score) || $score < 0 || $score > $question->max_mark_units) {
                continue;
            }
            foreach (array_unique($question->topic_keys ?? []) as $key) {
                $topic = $topics->get($key);
                if ($topic !== null) {
                    $rows->push((object) ['id' => $id, 'topic_id' => $topic->id, 'audience' => $audience,
                        'achieved_units' => $score, 'maximum_units' => $question->max_mark_units, 'observed_at' => $observed]);
                }
            }
        }
    }
}
