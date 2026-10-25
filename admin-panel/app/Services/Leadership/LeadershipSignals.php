<?php

declare(strict_types=1);

namespace App\Services\Leadership;

use App\Models\User;

/** Aggregate-only integration point for risk and discipline. No clinical bodies. */
class LeadershipSignals
{
    /**
     * @param  array<int, list<int>>  $learnersByGrade  Dated enrolments at period end.
     * @return array<int, array{academic_risk: ?int, discipline: ?int}>
     */
    public function counts(User $actor, int $year, string $start, string $end, array $learnersByGrade): array
    {
        return array_fill_keys(array_keys($learnersByGrade), ['academic_risk' => null, 'discipline' => null]);
    }
}
