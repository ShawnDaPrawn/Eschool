<?php

declare(strict_types=1);

namespace App\Services\Growth;

/** Versioned recent-evidence heuristic, not a calibrated probability/promotion rule. */
class MasteryScore
{
    public const VERSION = 'recent20-v1';

    public function calculate(array $rows): array
    {
        $earned = $maximum = $count = 0;
        foreach (array_slice($rows, 0, 20) as $row) {
            $score = $row['achieved_units'] ?? null;
            $total = $row['maximum_units'] ?? null;
            if (! is_int($score) || ! is_int($total) || $total < 1 || $total > 100000000 || $score < 0 || $score > $total) {
                throw new \InvalidArgumentException('Invalid bounded mastery evidence.');
            }
            $earned += $score;
            $maximum += $total;
            $count++;
        }
        $percentage = $count === 0 ? null : intdiv($earned * 10000 + intdiv($maximum, 2), $maximum);
        $level = $percentage === null ? null : match (true) {
            $percentage >= 8500 => 4, $percentage >= 7000 => 3, $percentage >= 5000 => 2, $percentage >= 2500 => 1, default => 0,
        };

        return ['level' => $level, 'score_bp' => $percentage, 'confidence_bp' => min(10000, $count * 2000), 'evidence_count' => $count, 'policy_version' => self::VERSION];
    }
}
