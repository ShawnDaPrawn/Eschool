<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking;

/** Judgement is off (no qualified provider): every method or explanation mark without a deciding rule waits for a teacher. */
final class DisabledMarkingJudgement implements MarkingJudgement
{
    public function judge(array $point, LearnerAnswer $answer, SubjectProfile $profile, array $evidence): Outcome
    {
        $reason = $point['type'] === 'explanation'
            ? __('An explanation needs a teacher\'s judgement. Automatic judgement is switched off.')
            : __('This method mark needs a teacher\'s judgement. Automatic judgement is switched off.');

        return Outcome::needsTeacher($evidence === [] ? $reason : $reason.' '.implode(' ', $evidence), 0.1);
    }
}
