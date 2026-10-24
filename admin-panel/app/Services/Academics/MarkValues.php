<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\AssessmentTask;
use Illuminate\Validation\ValidationException;

class MarkValues
{
    public function normalise(AssessmentTask $task, AssessmentTask $root, array $row, string $field): array
    {
        $disposition = $row['disposition'];
        $note = isset($row['note']) ? trim($row['note']) : null;
        if (in_array($disposition, ['absent', 'excused', 'catch_up'], true) && ($note === null || $note === '')) {
            throw ValidationException::withMessages([$field.'.note' => __('Record a reason for absence, exemption or catch-up work.')]);
        }
        $noScore = in_array($disposition, ['absent', 'excused', 'missing'], true);
        $value = $row['mark'] === null ? null : AcademicNumbers::units($row['mark'], $field.'.mark', $task->max_mark_units);
        $submitted = collect($row['question_marks']);
        if ($submitted->pluck('question_id')->unique()->count() !== $submitted->count()) {
            throw ValidationException::withMessages([$field.'.question_marks' => __('Submit each question once.')]);
        }
        $current = $task->questions->keyBy('id');
        $canonical = $root->questions->keyBy('number');
        $questions = [];
        foreach ($root->questions as $question) {
            $questions[(int) $question->id] = null;
        }
        foreach ($submitted as $input) {
            $question = $current->get((int) $input['question_id']);
            if ($question === null || $canonical->get($question->number) === null) {
                throw ValidationException::withMessages([$field.'.question_marks' => __('Choose questions from this published task.')]);
            }
            $units = $input['mark'] === null ? null : AcademicNumbers::units($input['mark'], $field.'.question_marks', $question->max_mark_units);
            if ($noScore && $units !== null) {
                throw ValidationException::withMessages([$field.'.disposition' => __('An absent, excused or missing task cannot also contain a score.')]);
            }
            $questions[(int) $canonical->get($question->number)->id] = $units;
        }
        if ($noScore && $value !== null) {
            throw ValidationException::withMessages([$field.'.mark' => __('Clear the score for an absent, excused or missing task.')]);
        }
        if ($current->isNotEmpty()) {
            if (! $noScore && $submitted->count() !== $current->count()) {
                throw ValidationException::withMessages([$field.'.question_marks' => __('Include every question, using null for an uncaptured question.')]);
            }
            $total = in_array(null, $questions, true) ? null : array_sum($questions);
            if ($value !== null && $value !== $total) {
                throw ValidationException::withMessages([$field.'.mark' => __('The task score must match its question scores.')]);
            }
            $value = $noScore ? null : $total;
        } elseif ($submitted->isNotEmpty()) {
            throw ValidationException::withMessages([$field.'.question_marks' => __('This task has no question breakdown.')]);
        }
        ksort($questions);

        return ['learner_id' => (int) $row['learner_id'], 'mark_units' => $value, 'disposition' => $disposition,
            'note' => $note === '' ? null : $note, 'question_units' => $questions, 'expected_revision' => (int) $row['expected_revision']];
    }
}
