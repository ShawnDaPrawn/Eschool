<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking\Rules;

use App\Services\Learning\Marking\LearnerAnswer;
use App\Services\Learning\Marking\Outcome;

/**
 * significant_figures: the final answer is given to the memo's number of
 * significant figures. Trailing zeros of a whole number (1200) are ambiguous:
 * the mark is awarded with low confidence when the count is possible.
 */
final class SignificantFiguresRule
{
    public function evaluate(array $point, LearnerAnswer $answer): Outcome
    {
        $figures = (int) $point['rule']['figures'];
        foreach ($answer->order() as $line) {
            $value = $answer->values($line)[0] ?? null;
            if ($value === null) {
                continue;
            }
            if ($value['sig_min'] <= $figures && $figures <= $value['sig_max']) {
                return $value['sig_min'] === $value['sig_max']
                    ? Outcome::awarded(__('The answer is given to the required number of significant figures.'), 0.9, $line)
                    : Outcome::awarded(__('The answer may be given to the required number of significant figures: its trailing zeros are ambiguous.'), 0.6, $line);
            }

            return Outcome::notAwarded(__('The answer is not given to the required number of significant figures.'), 0.85, $line);
        }

        return Outcome::notAwarded(__('No answer value was found.'), 0.9);
    }
}
