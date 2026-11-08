<?php

declare(strict_types=1);

namespace App\Services\Learning\Tests;

/**
 * The kinds of practice test and their bounds. A quick quiz is a learner's
 * own short check on one to three topics, timed by its questions' expected
 * time. A cycle test is set by a teacher for a group, with a time limit and
 * a window. Mock exams are the next kind (built from structured past paper
 * memos and the subject marking engine); they are refused until then.
 */
final class TestKinds
{
    public const QUICK_QUIZ = 'quick_quiz';

    public const CYCLE_TEST = 'cycle_test';

    /** Planned: learners sit mock exams on their own, separate from teacher-set tests. */
    public const MOCK_EXAM = 'mock_exam';

    public const AVAILABLE = [self::QUICK_QUIZ, self::CYCLE_TEST];

    /** @return array{topics: array{0: int, 1: int}, questions: array{0: int, 1: int}, minutes: array{0: int, 1: int}|null, window_days: int|null} */
    public static function limits(string $kind): array
    {
        abort_if($kind === self::MOCK_EXAM, 422, __('Mock exams are not available yet.'));

        return match ($kind) {
            self::QUICK_QUIZ => ['topics' => [1, 3], 'questions' => [3, 10], 'minutes' => null, 'window_days' => null],
            self::CYCLE_TEST => ['topics' => [1, 20], 'questions' => [5, 40], 'minutes' => [5, 180], 'window_days' => 31],
            default => abort(422),
        };
    }
}
