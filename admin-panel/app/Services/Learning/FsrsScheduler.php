<?php

declare(strict_types=1);

namespace App\Services\Learning;

use InvalidArgumentException;

/**
 * Deterministic FSRS memory model, with daily (not minute-step) practice reviews.
 * Reference: fsrs4anki tag v4.5.0, commit 8180871d9ef7e93943a60829533e46ab6d785ef4.
 * Copyright (c) 2022 open-spaced-repetition, MIT; see engineering/licenses/fsrs4anki-MIT.txt.
 * Fuzz is disabled for reproducible, auditable scheduling. No official marks change.
 */
final class FsrsScheduler
{
    public const VERSION = 'fsrs4anki-4.5.0-8180871-daily-v1';

    private const W = [0.4, 0.6, 2.4, 5.8, 4.93, 0.94, 0.86, 0.01, 1.49, 0.14, 0.94, 2.18, 0.05, 0.34, 1.26, 0.29, 2.61];

    /** Ratings: 1 again, 2 hard, 3 good, 4 easy. Dates are school-local domain inputs. */
    public function review(?array $memory, int $rating, int $elapsedDays, float $retention = 0.9, int $maximumDays = 365): array
    {
        if ($rating < 1 || $rating > 4) {
            throw new InvalidArgumentException('An FSRS rating must be 1 through 4.');
        }

        return $this->choices($memory, $elapsedDays, $retention, $maximumDays)[$rating];
    }

    public function choices(?array $memory, int $elapsedDays, float $retention = 0.9, int $maximumDays = 365): array
    {
        if ($elapsedDays < 0 || $elapsedDays > 36500 || ! is_finite($retention) || $retention <= 0 || $retention >= 1 || $maximumDays < 1 || $maximumDays > 36500) {
            throw new InvalidArgumentException('Invalid FSRS elapsed days, retention or interval bound.');
        }
        $difficulty = $stability = $retrievability = null;
        if ($memory !== null) {
            $difficulty = $memory['difficulty'] ?? null;
            $stability = $memory['stability_days'] ?? null;
            if (! (is_int($difficulty) || is_float($difficulty)) || ! (is_int($stability) || is_float($stability))
                || ! is_finite((float) $difficulty) || ! is_finite((float) $stability) || $difficulty < 1 || $difficulty > 10 || $stability < 0.01 || $stability > 36500) {
                throw new InvalidArgumentException('Invalid FSRS memory state.');
            }
            $retrievability = pow(1 + $elapsedDays / (9 * $stability), -1);
        }
        $choices = [];
        foreach ([1, 2, 3, 4] as $rating) {
            if ($memory === null) {
                $d = $this->difficulty(self::W[4] - self::W[5] * ($rating - 3));
                $s = self::W[$rating - 1];
            } else {
                $d = $this->difficulty(self::W[7] * self::W[4] + (1 - self::W[7]) * ($difficulty - self::W[6] * ($rating - 3)));
                $s = $rating === 1
                    ? min(self::W[11] * pow($d, -self::W[12]) * (pow($stability + 1, self::W[13]) - 1) * exp((1 - $retrievability) * self::W[14]), $stability)
                    : $stability * (1 + exp(self::W[8]) * (11 - $d) * pow($stability, -self::W[9]) * (exp((1 - $retrievability) * self::W[10]) - 1)
                        * ($rating === 2 ? self::W[15] : 1) * ($rating === 4 ? self::W[16] : 1));
                $s = round($s, 2, PHP_ROUND_HALF_UP);
            }
            $s = max(0.01, min(36500, $s));
            $days = $rating === 1 ? 1 : max(1, min($maximumDays, (int) round($s * 9 * (1 / $retention - 1), 0, PHP_ROUND_HALF_UP)));
            $choices[$rating] = ['version' => self::VERSION, 'rating' => $rating, 'difficulty' => $d, 'stability_days' => $s,
                'retrievability_before' => $retrievability, 'interval_days' => $days];
        }
        // Preserve the FSRS rating ordering; the application interval cap wins
        // when it is too small to represent separate adjacent daily intervals.
        $choices[2]['interval_days'] = min($choices[2]['interval_days'], $choices[3]['interval_days']);
        $choices[3]['interval_days'] = min($maximumDays, max($choices[3]['interval_days'], $choices[2]['interval_days'] + 1));
        $choices[4]['interval_days'] = min($maximumDays, max($choices[4]['interval_days'], $choices[3]['interval_days'] + 1));

        return $choices;
    }

    private function difficulty(float $difficulty): float
    {
        return min(10.0, max(1.0, round($difficulty, 2, PHP_ROUND_HALF_UP)));
    }
}
