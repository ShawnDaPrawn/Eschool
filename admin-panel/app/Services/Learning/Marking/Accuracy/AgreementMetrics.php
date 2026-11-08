<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking\Accuracy;

/**
 * Agreement between the marking engine and teachers (Action Plan 3.18), over
 * one subject's evaluation scripts. Pure: fed one mark point and one script
 * total at a time, then asked for the result.
 *
 * - A mark point agrees exactly when the engine awarded what the teacher did,
 *   and within tolerance when the difference is at most the point tolerance.
 *   A mark point the engine sends to a teacher never agrees: it is counted as
 *   a disagreement (and not awarded in the script total), because a learner
 *   would not have received it.
 * - A script total agrees within tolerance when the difference is at most the
 *   script tolerance, a percentage of the script's maximum.
 * - Mean absolute difference and systematic bias (the mean signed difference,
 *   engine minus teacher) are in marks; bias is also a percentage of the mean
 *   script maximum.
 * - A run passes when it has at least the minimum number of scripts, both
 *   agreement shares reach their thresholds and the bias is within its limit.
 *
 * Marks are in hundredths (mark units), as structured memos store them.
 */
final class AgreementMetrics
{
    public const STATS_KEPT = 500;

    /** @var array<string, int> */
    private array $points = [];

    /** @var array<string, array<string, int>> */
    private array $byType = [];

    /** @var array<string, array<string, int>> */
    private array $byRule = [];

    /** @var array<string, int> */
    private array $scripts = ['count' => 0, 'exact' => 0, 'within' => 0, 'abs' => 0, 'signed' => 0, 'max' => 0];

    /** @var array<string, array<string, mixed>> */
    private array $stats = [];

    /**
     * @param  array{min_scripts: int, point_marks: int|float, script_percent: int|float, point_agreement_percent: int|float, script_agreement_percent: int|float, max_bias_percent: int|float}  $settings
     */
    public function __construct(private readonly array $settings) {}

    /** The settings, read from config('marking.gate'), as a run keeps them. */
    public static function settings(): array
    {
        $gate = (array) config('marking.gate', []);

        return [
            'min_scripts' => max(1, (int) ($gate['min_scripts'] ?? 200)),
            'point_marks' => max(0, (float) ($gate['tolerance']['point_marks'] ?? 0)),
            'script_percent' => max(0, (float) ($gate['tolerance']['script_percent'] ?? 0)),
            'point_agreement_percent' => min(100, max(0, (float) ($gate['thresholds']['point_agreement_percent'] ?? 100))),
            'script_agreement_percent' => min(100, max(0, (float) ($gate['thresholds']['script_agreement_percent'] ?? 100))),
            'max_bias_percent' => max(0, (float) ($gate['thresholds']['max_bias_percent'] ?? 0)),
            'owner_confirmed' => ($gate['owner_confirmed'] ?? false) === true,
        ];
    }

    /**
     * One mark point of one script.
     *
     * @param  array{paper: string, question: string, index: int, earns: string, type: string, rule: string, mark_units: int}  $meta
     * @param  array{awarded_units: int, status: string, reason: string}  $engine  the engine's row for the point
     */
    public function point(string $key, array $meta, array $engine, int $teacherUnits): void
    {
        $pending = $engine['status'] === 'needs_teacher';
        $diff = (int) $engine['awarded_units'] - $teacherUnits;
        $exact = ! $pending && $diff === 0;
        $within = ! $pending && abs($diff) <= (int) round($this->settings['point_marks'] * 100);
        $observation = ['count' => 1, 'exact' => (int) $exact, 'within' => (int) $within, 'pending' => (int) $pending,
            'abs' => abs($diff), 'signed' => $diff, 'over' => (int) (! $pending && $diff > 0), 'under' => (int) (! $pending && $diff < 0)];
        $this->points = self::add($this->points, $observation);
        $this->byType[$meta['type']] = self::add($this->byType[$meta['type']] ?? [], $observation);
        $this->byRule[$meta['rule']] = self::add($this->byRule[$meta['rule']] ?? [], $observation);

        $stat = $this->stats[$key] ?? $meta + ['count' => 0, 'within' => 0, 'over' => 0, 'under' => 0, 'pending' => 0, 'reasons' => []];
        $stat['count']++;
        $stat['within'] += $observation['within'];
        $stat['over'] += $observation['over'];
        $stat['under'] += $observation['under'];
        $stat['pending'] += $observation['pending'];
        if (! $within) {
            $reason = mb_substr((string) $engine['reason'], 0, 200);
            if (isset($stat['reasons'][$reason]) || count($stat['reasons']) < 5) {
                $stat['reasons'][$reason] = ($stat['reasons'][$reason] ?? 0) + 1;
            }
        }
        $this->stats[$key] = $stat;
    }

    /** One script's totals: the engine's awarded marks, the teacher's and the script's maximum. */
    public function script(int $engineUnits, int $teacherUnits, int $maxUnits): void
    {
        $diff = $engineUnits - $teacherUnits;
        $this->scripts['count']++;
        $this->scripts['exact'] += (int) ($diff === 0);
        $this->scripts['within'] += (int) (abs($diff) * 100 <= $this->settings['script_percent'] * $maxUnits + 1e-9);
        $this->scripts['abs'] += abs($diff);
        $this->scripts['signed'] += $diff;
        $this->scripts['max'] += $maxUnits;
    }

    /**
     * @return array{passed: bool, failures: list<string>, metrics: array<string, mixed>, point_stats: list<array<string, mixed>>, point_count: int, script_count: int}
     */
    public function result(): array
    {
        $points = $this->points + ['count' => 0, 'exact' => 0, 'within' => 0, 'pending' => 0, 'abs' => 0, 'signed' => 0];
        $scripts = $this->scripts;
        $failures = [];
        if ($scripts['count'] < $this->settings['min_scripts']) {
            $failures[] = 'too_few_scripts';
        }
        if ($points['count'] === 0 || $points['within'] * 100 < $this->settings['point_agreement_percent'] * $points['count'] - 1e-9) {
            $failures[] = 'point_agreement';
        }
        if ($scripts['count'] === 0 || $scripts['within'] * 100 < $this->settings['script_agreement_percent'] * $scripts['count'] - 1e-9) {
            $failures[] = 'script_agreement';
        }
        if ($scripts['max'] === 0 || abs($scripts['signed']) * 100 > $this->settings['max_bias_percent'] * $scripts['max'] + 1e-9) {
            $failures[] = 'bias';
        }

        $metrics = [
            'points' => self::summary($points),
            'scripts' => ['count' => $scripts['count'], 'exact_percent' => self::percent($scripts['exact'], $scripts['count']),
                'within_percent' => self::percent($scripts['within'], $scripts['count']), 'mean_abs_marks' => self::marks($scripts['abs'], $scripts['count']),
                'bias_marks' => self::marks($scripts['signed'], $scripts['count']), 'bias_percent' => self::percent($scripts['signed'], $scripts['max']),
                'mean_max_marks' => self::marks($scripts['max'], $scripts['count'])],
            'by_type' => array_map(self::summary(...), self::sorted($this->byType)),
            'by_rule' => array_map(self::summary(...), self::sorted($this->byRule)),
        ];

        return ['passed' => $failures === [], 'failures' => $failures, 'metrics' => $metrics, 'point_stats' => $this->pointStats(),
            'point_count' => $points['count'], 'script_count' => $scripts['count']];
    }

    /** Every mark point measured, worst agreement first, at most STATS_KEPT. */
    private function pointStats(): array
    {
        $rows = [];
        foreach ($this->stats as $key => $stat) {
            arsort($stat['reasons']);
            $rows[] = array_diff_key($stat, ['reasons' => true]) + ['key' => $key, 'agreement_percent' => self::percent($stat['within'], $stat['count']),
                'reason' => array_key_first($stat['reasons'])];
        }
        usort($rows, fn (array $a, array $b) => [$a['within'] * $b['count'], $b['count'], $a['key']] <=> [$b['within'] * $a['count'], $a['count'], $b['key']]);

        return array_slice($rows, 0, self::STATS_KEPT);
    }

    private static function add(array $bucket, array $observation): array
    {
        foreach ($observation as $field => $value) {
            $bucket[$field] = ($bucket[$field] ?? 0) + $value;
        }

        return $bucket;
    }

    private static function summary(array $bucket): array
    {
        $count = (int) ($bucket['count'] ?? 0);

        return ['count' => $count, 'exact_percent' => self::percent((int) ($bucket['exact'] ?? 0), $count),
            'within_percent' => self::percent((int) ($bucket['within'] ?? 0), $count), 'needs_teacher_percent' => self::percent((int) ($bucket['pending'] ?? 0), $count),
            'mean_abs_marks' => self::marks((int) ($bucket['abs'] ?? 0), $count), 'bias_marks' => self::marks((int) ($bucket['signed'] ?? 0), $count)];
    }

    private static function sorted(array $buckets): array
    {
        ksort($buckets);

        return $buckets;
    }

    private static function percent(int $part, int $whole): ?float
    {
        return $whole === 0 ? null : round($part * 100 / $whole, 2);
    }

    /** A mean in marks (units are hundredths). */
    private static function marks(int $units, int $count): ?float
    {
        return $count === 0 ? null : round($units / 100 / $count, 3);
    }
}
