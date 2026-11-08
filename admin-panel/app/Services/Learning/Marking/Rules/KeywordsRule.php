<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking\Rules;

use App\Services\Learning\Marking\AnswerText;
use App\Services\Learning\Marking\LearnerAnswer;
use App\Services\Learning\Marking\Outcome;

/**
 * keywords: every term the memo lists appears in the answer (any of a term's
 * alternatives, ignoring case, accents and a plural s or es). Presence is not
 * understanding, so for an explanation mark this is only evidence for a teacher.
 */
final class KeywordsRule
{
    /** @return array{found: int, total: int} */
    public function count(array $rule, LearnerAnswer $answer): array
    {
        $text = ' '.AnswerText::words($answer->text()).' ';
        $found = 0;
        foreach ($rule['terms'] as $alternatives) {
            foreach ($alternatives as $alternative) {
                $words = AnswerText::words((string) $alternative);
                if ($words !== '' && preg_match('/ '.preg_quote($words, '/').'(?:s|es)? /', $text)) {
                    $found++;

                    break;
                }
            }
        }

        return ['found' => $found, 'total' => count($rule['terms'])];
    }

    public function evaluate(array $point, LearnerAnswer $answer): Outcome
    {
        $count = $this->count($point['rule'], $answer);
        if ($count['found'] === $count['total']) {
            return Outcome::awarded(__('Every term the memo needs is in the answer.'), 0.85);
        }

        return Outcome::notAwarded(__(':missing of the :total terms the memo needs are not in the answer.', ['missing' => (string) ($count['total'] - $count['found']), 'total' => (string) $count['total']]), 0.75);
    }

    /** What the terms show, for a teacher judging an explanation. */
    public function evidence(array $rule, LearnerAnswer $answer): string
    {
        $count = $this->count($rule, $answer);

        return __('The answer has :found of the :total terms the memo lists.', ['found' => (string) $count['found'], 'total' => (string) $count['total']]);
    }
}
