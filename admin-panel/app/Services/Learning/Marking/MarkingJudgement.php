<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking;

use Illuminate\Container\Attributes\Bind;

/**
 * The seam for judgement (Action Plan 3.16): method and explanation marks that
 * no memo rule can decide. It is switched off: DisabledMarkingJudgement makes
 * no model call and leaves the mark for a teacher. A future implementation must
 * go through the AI gateway with its own qualified feature, and the engine
 * still never lets it award a mark on its own (MarkingEngine turns an award
 * into "needs a teacher").
 */
#[Bind(DisabledMarkingJudgement::class)]
interface MarkingJudgement
{
    /** @param array{earns: string, type: string, mark_units: int, rule: ?array} $point @param list<string> $evidence what the rules found */
    public function judge(array $point, LearnerAnswer $answer, SubjectProfile $profile, array $evidence): Outcome;
}
