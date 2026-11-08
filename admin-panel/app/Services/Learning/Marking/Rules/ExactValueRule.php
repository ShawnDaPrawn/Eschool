<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking\Rules;

use App\Services\Learning\Marking\Directions;
use App\Services\Learning\Marking\LearnerAnswer;
use App\Services\Learning\Marking\Numbers;
use App\Services\Learning\Marking\Outcome;
use App\Services\Learning\Marking\SubjectProfile;
use App\Services\Learning\Marking\Units;

/**
 * exact_value: the memo's value within its tolerance, in the memo's unit or an
 * equivalent SI unit (converted). Answer, accuracy and consistent-accuracy marks
 * look at the values the answer states (the final line first); method marks
 * look for the value anywhere in the working (a substitution).
 */
final class ExactValueRule
{
    public function evaluate(array $point, LearnerAnswer $answer, SubjectProfile $profile): Outcome
    {
        $rule = $point['rule'];
        $expected = (float) $rule['value'];
        $tolerance = (float) ($rule['tolerance'] ?? '0');
        if ($point['type'] === 'method') {
            $signed = null;
            foreach ($answer->numbers() as $number) {
                if (Numbers::within($number['value'], $expected, $tolerance)) {
                    return Outcome::awarded(__('The value from the memo appears in the working (line :line).', ['line' => (string) ($number['line'] + 1)]), 0.85, $number['line']);
                }
                // A substitution may carry the learner's sign convention (g = -9,8).
                $signed ??= Numbers::within(abs($number['value']), abs($expected), $tolerance) ? $number : null;
            }
            if ($signed !== null) {
                return Outcome::awarded(__('The value from the memo appears in the working with the opposite sign (line :line).', ['line' => (string) ($signed['line'] + 1)]), 0.75, $signed['line']);
            }

            return Outcome::notAwarded(__('The value from the memo does not appear in the working.'), 0.8);
        }
        $directions = $profile->requiresDirection() ? Directions::in($point['earns']) : [];
        $direction = count($directions) === 1 ? $directions[0] : null;
        $memoDecimals = strlen(substr(strrchr((string) $rule['value'], '.') ?: '.', 1));
        $outcomes = [];
        $seen = false;
        foreach ($answer->order() as $line) {
            foreach ($answer->values($line) as $value) {
                $seen = true;
                $outcomes[] = $this->candidate($value, $expected, $tolerance, $memoDecimals, $rule['unit'] ?? null, $direction, $profile, $answer->isFinal($line));
            }
        }

        return Outcome::best($outcomes) ?? ($seen
            ? Outcome::notAwarded(__('The answer does not match the memo.'), 0.85)
            : Outcome::notAwarded(__('No answer value was found.'), 0.9));
    }

    private function candidate(array $value, float $expected, float $tolerance, int $memoDecimals, ?string $unit, ?string $direction, SubjectProfile $profile, bool $final): ?Outcome
    {
        $units = $unit === null || trim($unit) === '' ? ['status' => 'none', 'ratio' => 1.0] : Units::compare($unit, $value['unit']);
        $ratio = in_array($units['status'], ['same', 'converted'], true) ? $units['ratio'] : 1.0;
        $match = $this->matches($value, $value['value'] * $ratio, $expected, $tolerance, $memoDecimals, $ratio, $profile);
        $flipped = false;
        if ($match === null && $direction !== null) {
            // A sign may stand for the direction: the magnitude matches but the direction needs a check.
            $match = $this->matches(['decimals' => $value['decimals'], 'value' => -$value['value']] + $value, -$value['value'] * $ratio, $expected, $tolerance, $memoDecimals, $ratio, $profile);
            $flipped = $match !== null;
        }
        if ($match === null) {
            return null;
        }
        $line = $value['line'];
        switch ($units['status']) {
            case 'missing':
                if ($profile->requiresUnits()) {
                    return Outcome::notAwarded(__('The value is right, but the unit is missing. In :subject the unit is part of the answer.', ['subject' => $profile->label()]), 0.85, $line);
                }
                break;
            case 'wrong':
                return Outcome::notAwarded(__('The value is right, but the unit is not the memo\'s.'), 0.85, $line);
            case 'unreadable':
                return Outcome::needsTeacher(__('The value is right, but the unit could not be read. A teacher checks it.'), 0.4, $line);
            case 'unknown':
                return Outcome::needsTeacher(__('The memo\'s unit is not one marking knows, so a teacher checks the unit.'), 0.4, $line);
        }
        if ($direction !== null) {
            $stated = Directions::in($value['rest']);
            if ($stated === []) {
                return Outcome::needsTeacher(__('The value is right, but no direction is stated. A teacher checks the direction or sign.'), 0.4, $line);
            }
            if (! in_array($direction, $stated, true) || count($stated) > 1) {
                return Outcome::notAwarded(__('The value is right, but the direction is not the memo\'s.'), 0.85, $line);
            }
            if ($flipped) {
                return Outcome::needsTeacher(__('The value has a sign as well as a direction. A teacher checks the direction.'), 0.4, $line);
            }
        }
        $confidence = ($final ? 0.95 : 0.8) - ($match === 'rounded' ? 0.1 : 0.0) - ($units['status'] === 'converted' ? 0.05 : 0.0);
        $reason = match (true) {
            ! $final => __('A value on line :line matches the memo, but it is not the final answer.', ['line' => (string) ($line + 1)]),
            $match === 'rounded' => __('The answer matches the memo, correctly rounded.'),
            $units['status'] === 'converted' => __('The answer matches the memo once its unit is converted.'),
            default => __('The answer matches the memo.'),
        };

        return Outcome::awarded($reason, $confidence, $line);
    }

    /** 'exact' within the tolerance, 'rounded' under the subject's rounding convention, or null. */
    private function matches(array $value, float $converted, float $expected, float $tolerance, int $memoDecimals, float $ratio, SubjectProfile $profile): ?string
    {
        if (Numbers::within($converted, $expected, $tolerance)) {
            return 'exact';
        }
        $minimum = $profile->roundingDecimals();
        if ($minimum === null || $value['decimals'] < $minimum) {
            return null;
        }
        // Rounded from the memo's value, in the learner's unit, to the learner's places.
        if (Numbers::roundsTo($value['value'], $expected / $ratio, $value['decimals'])) {
            return 'rounded';
        }
        // More places than the memo, rounding to the memo's value.
        if ($ratio === 1.0 && $value['decimals'] > $memoDecimals && Numbers::roundsTo($expected, $value['value'], $memoDecimals)) {
            return 'rounded';
        }

        return null;
    }
}
