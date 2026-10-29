<?php

declare(strict_types=1);

namespace App\Services\Academics\Homework;

use App\Models\Assignment;

/**
 * The mark a teacher gives when accepting homework: a whole number from zero
 * to the assignment's total (when it has one). A rejected submission carries no
 * mark. The learner and their parents see "mark/total" with the feedback, and
 * the learner's report lists every marked assignment.
 */
class HomeworkMarks
{
    /** The error to show, or null when the mark may be saved. */
    public function problem(Assignment $assignment, int|string $status, mixed $points): ?string
    {
        if ((int) $status !== 1 || $points === null || $points === '') {
            return null;
        }

        if (! is_numeric($points) || (float) $points != (int) $points) {
            return __('The mark must be a whole number.');
        }

        $mark = (int) $points;
        if ($mark < 0) {
            return __('The mark cannot be negative.');
        }

        if ($assignment->points !== null && $assignment->points !== '' && $mark > (int) $assignment->points) {
            return __('The mark cannot be more than the total of :total.', ['total' => (int) $assignment->points]);
        }

        return null;
    }

    /** The mark to store: null unless the submission is accepted with a mark. */
    public function stored(int|string $status, mixed $points): ?int
    {
        return (int) $status === 1 && $points !== null && $points !== '' ? (int) $points : null;
    }
}
