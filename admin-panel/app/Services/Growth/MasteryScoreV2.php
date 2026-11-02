<?php

declare(strict_types=1);

namespace App\Services\Growth;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Mastery model v2 (Action Plan 3.8): a pure, deterministic calculation with
 * no database or clock access. The caller supplies the evidence rows (newest
 * first), the moment to score at and the learner's practice-memory
 * stabilities for the topic. Like recent20-v1 it is learning guidance, not a
 * calibrated probability or a promotion rule.
 *
 * For each of the newest `window` rows i (rank i = 0 for the newest):
 *   p_i = achieved_units / maximum_units                 the answer's result
 *   t_i = whole days from observed_at to as_of           its age
 *   S   = (sum(own stabilities) + n0 * S0) / (count + n0)  topic memory stability
 *   a_i = 1 / (1 + t_i / (9 * S))                        FSRS forgetting curve
 *   c_i = source weight (marks 1, practice and tutor 0.5)
 *   r_i = recency_ratio ^ i
 *   d_i = 1 + strength * (1 - 2 * f_i)                   f_i = item success rate
 *         across the school in the same audience; 1 when fewer than
 *         `difficulty_min_responses` different learners answered it
 *   w_i = c_i * r_i * d_i                                no time term: see below
 *   raw = sum(w_i * p_i) / sum(w_i)
 *   E   = sum(c_i * a_i)                                  fresh weighted evidence
 *   score      = raw                          when raw <= midpoint m
 *              = m + (raw - m) * E / (E + ks)  otherwise
 *   confidence = E / (E + kc)
 * Time enters only through E. raw does not depend on as_of, and E never grows
 * as as_of moves later, so for the same evidence the score never rises with
 * time and a score at or below the midpoint never changes. (Freshness inside
 * w_i would break this: as all answers age, a_old / a_new tends to 1, so an
 * old success would gain weight against a recent failure.)
 * Level thresholds are recent20-v1's: 85%, 70%, 50% and 25% of score_bp.
 * No rows means no score (level and score_bp null), never zero.
 */
final class MasteryScoreV2
{
    public const VERSION = 'weighted-decay-v2';

    private array $parameters;

    public function __construct(array $parameters = [])
    {
        $this->parameters = self::validated(array_replace_recursive((array) config('mastery.v2', []), $parameters));
    }

    public function parameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param  array<int, array{achieved_units:int, maximum_units:int, source_type:string, observed_at:DateTimeInterface|string, facility_bp?:?int, facility_responses?:int}>  $rows  newest first
     * @param  array<int, float|int>  $stabilities  the learner's practice-memory stability days on the topic
     */
    public function calculate(array $rows, DateTimeInterface $asOf, array $stabilities = []): array
    {
        $p = $this->parameters;
        $stability = $this->stability($stabilities);
        $weighted = $weights = $evidence = 0.0;
        $count = 0;
        foreach (array_slice(array_values($rows), 0, $p['window']) as $rank => $row) {
            $score = $row['achieved_units'] ?? null;
            $total = $row['maximum_units'] ?? null;
            if (! is_int($score) || ! is_int($total) || $total < 1 || $total > 100000000 || $score < 0 || $score > $total) {
                throw new InvalidArgumentException('Invalid bounded mastery evidence.');
            }
            $source = (float) ($p['source_weights'][$row['source_type'] ?? ''] ?? $p['default_source_weight']);
            $fresh = 1 / (1 + $this->ageDays($row['observed_at'] ?? null, $asOf) / (9 * $stability));
            $weight = $source * ($p['recency_ratio'] ** $rank) * $this->difficulty($row);
            $weighted += $weight * $score / $total;
            $weights += $weight;
            $evidence += $source * $fresh;
            $count++;
        }
        if ($count === 0) {
            return ['level' => null, 'score_bp' => null, 'confidence_bp' => 0, 'evidence_count' => 0, 'policy_version' => self::VERSION];
        }
        $raw = $weighted / $weights;
        $midpoint = $p['midpoint_bp'] / 10000;
        $mastery = $raw <= $midpoint ? $raw : $midpoint + ($raw - $midpoint) * $evidence / ($evidence + $p['score_evidence_half']);
        $percentage = self::basisPoints($mastery);
        $level = match (true) {
            $percentage >= 8500 => 4, $percentage >= 7000 => 3, $percentage >= 5000 => 2, $percentage >= 2500 => 1, default => 0,
        };

        return ['level' => $level, 'score_bp' => $percentage, 'confidence_bp' => self::basisPoints($evidence / ($evidence + $p['confidence_evidence_half'])),
            'evidence_count' => $count, 'policy_version' => self::VERSION];
    }

    /** Topic memory stability: the learner's own FSRS stabilities blended with the default. */
    public function stability(array $stabilities): float
    {
        $sum = 0.0;
        $count = 0;
        foreach ($stabilities as $days) {
            if ((is_int($days) || is_float($days)) && is_finite((float) $days) && $days >= 0.01 && $days <= 36500) {
                $sum += $days;
                $count++;
            }
        }
        $prior = $this->parameters['stability_prior_count'];

        return ($sum + $prior * $this->parameters['default_stability_days']) / ($count + $prior);
    }

    private function difficulty(array $row): float
    {
        $facility = $row['facility_bp'] ?? null;
        if (! is_int($facility) || $facility < 0 || $facility > 10000 || (int) ($row['facility_responses'] ?? 0) < $this->parameters['difficulty_min_responses']) {
            return 1.0;
        }

        return 1 + $this->parameters['difficulty_strength'] * (1 - 2 * $facility / 10000);
    }

    private function ageDays(mixed $observed, DateTimeInterface $asOf): int
    {
        if (is_string($observed)) {
            $observed = new DateTimeImmutable($observed);
        }
        if (! $observed instanceof DateTimeInterface) {
            throw new InvalidArgumentException('Mastery evidence needs its observed time.');
        }

        return max(0, intdiv($asOf->getTimestamp() - $observed->getTimestamp(), 86400));
    }

    private static function basisPoints(float $share): int
    {
        return (int) round($share * 10000, 0, PHP_ROUND_HALF_UP);
    }

    private static function validated(array $p): array
    {
        $number = fn (string $key, float $min, float $max) => isset($p[$key]) && (is_int($p[$key]) || is_float($p[$key])) && $p[$key] >= $min && $p[$key] <= $max;
        $valid = isset($p['window']) && is_int($p['window']) && $p['window'] >= 1 && $p['window'] <= 200
            && $number('recency_ratio', 0.01, 1) && $number('default_source_weight', 0.01, 10)
            && $number('difficulty_strength', 0, 0.99) && isset($p['difficulty_min_responses']) && is_int($p['difficulty_min_responses']) && $p['difficulty_min_responses'] >= 1
            && $number('default_stability_days', 0.01, 36500) && isset($p['stability_prior_count']) && is_int($p['stability_prior_count']) && $p['stability_prior_count'] >= 1
            && isset($p['midpoint_bp']) && is_int($p['midpoint_bp']) && $p['midpoint_bp'] >= 0 && $p['midpoint_bp'] <= 10000
            && $number('score_evidence_half', 0, 1000) && $number('confidence_evidence_half', 0.01, 1000) && is_array($p['source_weights'] ?? null);
        foreach ($p['source_weights'] ?? [] as $weight) {
            $valid = $valid && (is_int($weight) || is_float($weight)) && $weight >= 0.01 && $weight <= 10;
        }
        if (! $valid) {
            throw new InvalidArgumentException('Invalid mastery v2 parameters.');
        }

        return ['window' => $p['window'], 'recency_ratio' => (float) $p['recency_ratio'], 'source_weights' => array_map('floatval', $p['source_weights']),
            'default_source_weight' => (float) $p['default_source_weight'], 'difficulty_strength' => (float) $p['difficulty_strength'],
            'difficulty_min_responses' => $p['difficulty_min_responses'], 'default_stability_days' => (float) $p['default_stability_days'],
            'stability_prior_count' => $p['stability_prior_count'], 'midpoint_bp' => $p['midpoint_bp'],
            'score_evidence_half' => (float) $p['score_evidence_half'], 'confidence_evidence_half' => (float) $p['confidence_evidence_half']];
    }
}
