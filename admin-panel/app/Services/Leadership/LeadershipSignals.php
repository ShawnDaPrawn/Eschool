<?php

declare(strict_types=1);

namespace App\Services\Leadership;

use App\Models\User;
use App\Services\Growth\RiskMetrics;

/** Aggregate-only integration point for risk and discipline. No clinical bodies. */
class LeadershipSignals
{
    /**
     * @param  array<int, list<int>>  $learnersByGrade  Dated enrolments at period end.
     * @return array<int, array{academic_risk: ?int, discipline: ?int}>
     */
    public function counts(User $actor, int $year, string $start, string $end, array $learnersByGrade): array
    {
        $risk = app(RiskMetrics::class)->forPrincipal($actor, $year, $start, $end, $learnersByGrade);
        $result = [];
        foreach (array_keys($learnersByGrade) as $grade) {
            $result[$grade] = ['academic_risk' => $risk['grades'][$grade]['academic_risk'] ?? null,
                'discipline' => null];
        }

        return $result;
    }
}
