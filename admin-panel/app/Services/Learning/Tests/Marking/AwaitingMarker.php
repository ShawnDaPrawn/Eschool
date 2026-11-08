<?php

declare(strict_types=1);

namespace App\Services\Learning\Tests\Marking;

use InvalidArgumentException;

/**
 * Typed and photographed answers (Action Plan 3.11 question types, 3.16
 * marking engine) are kept, bounded, but not marked: they wait for their
 * marker and count neither towards the score nor as mastery evidence.
 */
final class AwaitingMarker implements TestAnswerMarker
{
    public function check(array $definition, string $answer): void
    {
        if (mb_strlen($answer) > 4000 || ! mb_check_encoding($answer, 'UTF-8') || str_contains($answer, "\0")) {
            throw new InvalidArgumentException('Keep your answer shorter.');
        }
    }

    public function mark(array $definition, string $answer): TestMark
    {
        $this->check($definition, $answer);

        return TestMark::awaiting();
    }
}
