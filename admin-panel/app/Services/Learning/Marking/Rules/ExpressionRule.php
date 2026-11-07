<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking\Rules;

use App\Services\Learning\Marking\Expression;
use App\Services\Learning\Marking\LearnerAnswer;
use App\Services\Learning\Marking\Outcome;
use App\Services\Learning\Marking\SubjectProfile;

/**
 * expression: the memo's expression or equation. An equation (with =) is looked
 * for in every line, as a formula or a stated result; an expression is compared
 * with what the answer states (the final line first), or, for a method mark,
 * with any part of the working. Subjects that accept equivalent forms compare by
 * value (Expression::equivalent); others compare the written form.
 */
final class ExpressionRule
{
    public function evaluate(array $point, LearnerAnswer $answer, SubjectProfile $profile): Outcome
    {
        $memo = (string) $point['rule']['value'];
        $style = $profile->identifiers();
        $method = $point['type'] === 'method';
        if (str_contains($memo, '=')) {
            $equation = Expression::equation($memo, $style);
            if ($equation === null) {
                return Outcome::needsTeacher(__('The memo\'s expression could not be read, so a teacher decides this mark.'), 0.2);
            }
            $vocabulary = array_values(array_unique([...Expression::variables($equation[0]), ...Expression::variables($equation[1])]));
            foreach ($answer->order() as $line) {
                $sides = $answer->sides($line);
                for ($k = 1; $k < count($sides); $k++) {
                    $written = $sides[0].'='.$sides[$k];
                    $same = Expression::written($written) === Expression::written($memo);
                    $candidate = $same || ! $profile->acceptsEquivalentForms() ? null : Expression::equation($written, $style, $vocabulary);
                    if ($same || ($candidate !== null && Expression::equationsEquivalent($equation, $candidate))) {
                        return Outcome::awarded($method
                            ? __('The formula from the memo is written on line :line.', ['line' => (string) ($line + 1)])
                            : __('The equation on line :line matches the memo.', ['line' => (string) ($line + 1)]), $same ? 0.95 : 0.9, $line);
                    }
                }
            }

            return Outcome::notAwarded($method ? __('The formula from the memo is not in the working.') : __('No equation in the answer matches the memo.'), 0.75);
        }
        $tree = Expression::parse($memo, $style);
        if ($tree === null) {
            return Outcome::needsTeacher(__('The memo\'s expression could not be read, so a teacher decides this mark.'), 0.2);
        }
        $vocabulary = Expression::variables($tree);
        $outcomes = [];
        foreach ($answer->order() as $line) {
            $final = $answer->isFinal($line);
            foreach ($method ? $answer->sides($line) : $answer->results($line) as $candidate) {
                if (Expression::written($candidate) === Expression::written($memo)) {
                    $outcomes[] = Outcome::awarded($final || $method ? __('The answer matches the memo.') : __('A value on line :line matches the memo, but it is not the final answer.', ['line' => (string) ($line + 1)]),
                        $final || $method ? 0.95 : 0.8, $line);

                    continue;
                }
                if (! $profile->acceptsEquivalentForms()) {
                    continue;
                }
                $given = Expression::parse($candidate, $style, $vocabulary);
                if ($given === null || ! Expression::equivalent($tree, $given)) {
                    continue;
                }
                $outcomes[] = $profile->asksForForm((string) $point['earns'])
                    ? Outcome::needsTeacher(__('The answer is equivalent to the memo, but the mark asks for a particular form. A teacher checks the form.'), 0.4, $line)
                    : Outcome::awarded(__('The answer is equivalent to the memo\'s answer.'), $final || $method ? 0.9 : 0.75, $line);
            }
        }

        return Outcome::best($outcomes) ?? Outcome::notAwarded(__('The answer does not match the memo.'), 0.8);
    }
}
