<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking\Rules;

use App\Services\Learning\Marking\Expression;
use App\Services\Learning\Marking\LearnerAnswer;
use App\Services\Learning\Marking\Numbers;
use App\Services\Learning\Marking\Outcome;
use App\Services\Learning\Marking\SubjectProfile;
use App\Services\Learning\Marking\Units;

/**
 * Consistent accuracy (follow-through): when an earlier value mark of the
 * question was lost, a consistent-accuracy mark whose own value is wrong may
 * still be earned by working correctly from the learner's earlier answer. In
 * code that means a line "calculation = result" whose calculation is plain
 * arithmetic, uses a value the learner stated on an earlier line, and gives
 * the stated result to its own precision. Anything less certain goes to a teacher.
 */
final class FollowThrough
{
    /** @param list<array> $points the question's points @param array<int, Outcome> $earlier outcomes so far, by index */
    public function evaluate(array $point, Outcome $direct, LearnerAnswer $answer, SubjectProfile $profile, array $points, array $earlier): Outcome
    {
        if (! $this->earlierError($points, $earlier)) {
            return $direct;
        }
        if (! $profile->followsThrough()) {
            return Outcome::needsTeacher(__('An earlier answer was wrong. In :subject a teacher decides consistent accuracy marks.', ['subject' => $profile->label()]), 0.3);
        }
        if (($point['rule']['kind'] ?? null) !== 'exact_value') {
            return Outcome::needsTeacher(__('An earlier answer was wrong. A teacher checks whether this step follows through from it.'), 0.3);
        }
        $stated = [];
        foreach (array_keys($answer->lines) as $line) {
            $stated[$line] = array_column($answer->values($line), 'value');
        }
        foreach ($answer->order() as $line) {
            $sides = $answer->sides($line);
            $result = $answer->values($line);
            $result = end($result);
            if (count($sides) < 2 || $result === false) {
                continue;
            }
            $carried = [];
            foreach ($stated as $before => $values) {
                if ($before < $line) {
                    array_push($carried, ...$values);
                }
            }
            foreach (array_slice($sides, 0, -1) as $side) {
                $tree = Expression::parse($side, 'symbols');
                $value = $tree === null ? null : Expression::constant($tree);
                if ($value === null || ! $this->uses($side, $carried)) {
                    continue;
                }
                $precision = 0.5 * 10 ** (-max(-12, min(12, $result['decimals'])));
                if (! Numbers::within($value, $result['value'], $precision)) {
                    continue;
                }
                $unit = $point['rule']['unit'] ?? null;
                if ($unit !== null && $profile->requiresUnits() && ! in_array(Units::compare($unit, $result['unit'])['status'], ['same', 'converted'], true)) {
                    return Outcome::notAwarded(__('The step follows through from the earlier answer, but its unit is missing or wrong.'), 0.7, $line);
                }

                return Outcome::awarded(__('Follow-through: line :line is worked correctly from the learner\'s own earlier answer.', ['line' => (string) ($line + 1)]), 0.7, $line, 'follow_through');
            }
        }

        return Outcome::needsTeacher(__('An earlier answer was wrong. A teacher checks whether this step follows through from it.'), 0.3);
    }

    private function earlierError(array $points, array $earlier): bool
    {
        foreach ($earlier as $i => $outcome) {
            $kind = $points[$i]['rule']['kind'] ?? null;
            if ($outcome->status === Outcome::NOT_AWARDED && in_array($points[$i]['type'], ['answer', 'accuracy', 'consistent_accuracy'], true) && in_array($kind, ['exact_value', 'expression'], true)) {
                return true;
            }
        }

        return false;
    }

    /** Whether the calculation uses a value stated on an earlier line. @param list<float> $carried */
    private function uses(string $side, array $carried): bool
    {
        foreach (Numbers::all($side) as $number) {
            foreach ($carried as $value) {
                if (Numbers::within(abs($number['value']), abs($value), 0.0)) {
                    return true;
                }
            }
        }

        return false;
    }
}
