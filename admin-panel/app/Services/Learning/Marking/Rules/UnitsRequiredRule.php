<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking\Rules;

use App\Services\Learning\Marking\LearnerAnswer;
use App\Services\Learning\Marking\Outcome;
use App\Services\Learning\Marking\Units;

/** units_required: the final answer (the lowest line that states a value) has the memo's unit or an equivalent SI unit. */
final class UnitsRequiredRule
{
    public function evaluate(array $point, LearnerAnswer $answer): Outcome
    {
        foreach ($answer->order() as $line) {
            $outcomes = [];
            foreach ($answer->values($line) as $value) {
                $check = Units::compare((string) $point['rule']['unit'], $value['unit']);
                $outcomes[] = match ($check['status']) {
                    'same', 'converted' => Outcome::awarded(__('The answer has the right unit.'), $answer->isFinal($line) ? 0.9 : 0.75, $line),
                    'missing' => Outcome::notAwarded(__('The answer has no unit.'), 0.85, $line),
                    'wrong' => Outcome::notAwarded(__('The answer\'s unit is not the memo\'s.'), 0.85, $line),
                    'unknown' => Outcome::needsTeacher(__('The memo\'s unit is not one marking knows, so a teacher checks the unit.'), 0.4, $line),
                    default => Outcome::needsTeacher(__('The unit could not be read. A teacher checks it.'), 0.4, $line),
                };
            }
            if ($outcomes !== []) {
                return Outcome::best($outcomes);
            }
        }

        return Outcome::notAwarded(__('No answer value was found.'), 0.9);
    }
}
