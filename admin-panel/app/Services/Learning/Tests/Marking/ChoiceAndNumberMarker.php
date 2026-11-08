<?php

declare(strict_types=1);

namespace App\Services\Learning\Tests\Marking;

use App\Services\Learning\PracticeAnswers;

/** The existing automatic marking: an offered option, or an exact decimal (PracticeAnswers). */
final class ChoiceAndNumberMarker implements TestAnswerMarker
{
    public function check(array $definition, string $answer): void
    {
        $this->mark($definition, $answer);
    }

    public function mark(array $definition, string $answer): TestMark
    {
        return TestMark::marked(app(PracticeAnswers::class)->correct($definition, $answer));
    }
}
