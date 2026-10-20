<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OnlineExamQuestionAnswer;
use App\Models\OnlineExamQuestionChoice;
use App\Models\OnlineExamQuestionOption;
use App\Models\OnlineExamStudentAnswer;
use Illuminate\Validation\ValidationException;

/**
 * Online exam scoring, shared by every place that shows online exam marks.
 *
 * A question is answered correctly only when the options the student picked
 * are exactly its correct options, so picking every option scores nothing.
 *
 * Note on ids: online_exam_student_answers.question_id holds the exam's
 * question choice id (online_exam_question_choices.id), not the question id.
 */
class OnlineExamScoringService
{
    /**
     * @param  iterable<int|string>  $selectedOptionIds
     * @param  iterable<int|string>  $correctOptionIds
     */
    public static function isCorrect(iterable $selectedOptionIds, iterable $correctOptionIds): bool
    {
        $correct = self::normalize($correctOptionIds);

        return $correct !== [] && self::normalize($selectedOptionIds) === $correct;
    }

    /**
     * Score one student's attempt at an online exam.
     *
     * @return array{
     *     total_questions: int,
     *     correct_choice_ids: list<int>,
     *     incorrect_choice_ids: list<int>,
     *     correct_question_ids: list<int>,
     *     obtained_marks: int|float,
     *     total_marks: int|float
     * }
     *   correct_choice_ids / incorrect_choice_ids are online_exam_question_choices ids
     *   (incorrect includes unanswered); correct_question_ids are online_exam_questions ids.
     */
    public function score(int $studentId, int $onlineExamId): array
    {
        $choices = OnlineExamQuestionChoice::where('online_exam_id', $onlineExamId)
            ->orderBy('id')
            ->get(['id', 'question_id', 'marks']);

        $correctOptions = OnlineExamQuestionAnswer::whereIn('question_id', $choices->pluck('question_id')->unique())
            ->get(['question_id', 'answer'])
            ->groupBy('question_id');

        $selectedOptions = OnlineExamStudentAnswer::where(['student_id' => $studentId, 'online_exam_id' => $onlineExamId])
            ->get(['question_id', 'option_id'])
            ->groupBy('question_id');

        $result = [
            'total_questions' => $choices->count(),
            'correct_choice_ids' => [],
            'incorrect_choice_ids' => [],
            'correct_question_ids' => [],
            'obtained_marks' => 0,
            'total_marks' => 0,
        ];

        foreach ($choices as $choice) {
            $result['total_marks'] += $choice->marks ?? 0;

            $selected = $selectedOptions->get($choice->id, collect())->pluck('option_id');
            $correct = $correctOptions->get($choice->question_id, collect())->pluck('answer');

            if (self::isCorrect($selected, $correct)) {
                $result['correct_choice_ids'][] = (int) $choice->id;
                $result['correct_question_ids'][] = (int) $choice->question_id;
                $result['obtained_marks'] += $choice->marks ?? 0;
            } else {
                $result['incorrect_choice_ids'][] = (int) $choice->id;
            }
        }
        $result['correct_question_ids'] = array_values(array_unique($result['correct_question_ids']));

        return $result;
    }

    /**
     * Check a submission against the exam's questions and their options.
     *
     * Each entry is ['question_id' => choice id, 'option_id' => [option ids]].
     * The app sends unanswered questions with option id 0 (or none); those are
     * left out.
     *
     * @return array<int, list<int>> choice id => the option ids picked
     *
     * @throws ValidationException when a question isn't in the exam, an option
     *                             isn't one of its question's options, or anything repeats
     */
    public function validateSubmission(int $onlineExamId, array $answersData): array
    {
        $choices = OnlineExamQuestionChoice::where('online_exam_id', $onlineExamId)->get(['id', 'question_id'])->keyBy('id');
        $optionQuestion = OnlineExamQuestionOption::whereIn('question_id', $choices->pluck('question_id')->unique())
            ->pluck('question_id', 'id');

        $answers = [];
        foreach ($answersData as $answer) {
            $choiceId = is_array($answer) ? ($answer['question_id'] ?? null) : null;
            if (! is_numeric($choiceId) || ! $choices->has((int) $choiceId) || array_key_exists((int) $choiceId, $answers)) {
                throw ValidationException::withMessages(['answers_data' => trans('invalid_question_id')]);
            }
            $choice = $choices->get((int) $choiceId);

            $optionIds = [];
            foreach ((array) ($answer['option_id'] ?? []) as $optionId) {
                if (! is_numeric($optionId)) {
                    throw ValidationException::withMessages(['answers_data' => 'Invalid option selected.']);
                }
                if ((int) $optionId === 0) {
                    continue;
                }
                if (in_array((int) $optionId, $optionIds, true)
                    || (int) ($optionQuestion->get((int) $optionId) ?? 0) !== (int) $choice->question_id) {
                    throw ValidationException::withMessages(['answers_data' => 'Invalid option selected.']);
                }
                $optionIds[] = (int) $optionId;
            }

            // Kept even when unanswered, so the same question can't be sent twice.
            $answers[(int) $choiceId] = $optionIds;
        }

        return array_filter($answers, fn (array $optionIds) => $optionIds !== []);
    }

    /**
     * @return list<int>
     */
    private static function normalize(iterable $ids): array
    {
        $normalized = [];
        foreach ($ids as $id) {
            if ((int) $id > 0) {
                $normalized[(int) $id] = (int) $id;
            }
        }
        sort($normalized);

        return $normalized;
    }
}
