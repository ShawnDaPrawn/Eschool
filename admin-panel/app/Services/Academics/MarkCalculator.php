<?php

declare(strict_types=1);

namespace App\Services\Academics;

use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

/** Pure exact weighted arithmetic. Only final displayed boundaries are rounded. */
class MarkCalculator
{
    public function calculate(array $components, array $tasks, string $rounding = 'half_up', string $absentPolicy = 'incomplete', bool $annual = false): array
    {
        $mode = $this->mode($rounding);
        if (! in_array($absentPolicy, ['zero', 'incomplete'], true)) {
            throw new InvalidArgumentException('Unknown absent policy.');
        }
        $warnings = [];
        $complete = true;
        $grouped = [];
        $taskResults = [];
        $totalComponentWeight = 0;
        foreach ($components as $name => $weight) {
            if (! is_string($name) || ! is_int($weight) || $weight < 0 || $weight > 10000) {
                throw new InvalidArgumentException('Invalid component weight.');
            }
            $totalComponentWeight += $weight;
        }
        if ($totalComponentWeight !== 10000) {
            $complete = false;
            $warnings[] = 'component_weights_incomplete';
        }
        foreach ($tasks as $task) {
            $component = $task[$annual ? 'annual_component' : 'component'] ?? null;
            $weight = $task[$annual ? 'annual_weight_bp' : 'weight_bp'] ?? 0;
            if (! is_int($weight) || $weight < 0 || $weight > 10000 || ! is_int($task['max_mark_units']) || $task['max_mark_units'] < 1) {
                throw new InvalidArgumentException('Invalid task definition.');
            }
            $disposition = $task['disposition'] ?? 'missing';
            if (! in_array($disposition, ['recorded', 'missing', 'absent', 'excused', 'catch_up'], true)) {
                throw new InvalidArgumentException('Unknown mark disposition.');
            }
            $units = $task['mark_units'] ?? null;
            if ($units !== null && (! is_int($units) || $units < 0 || $units > $task['max_mark_units'])) {
                throw new InvalidArgumentException('Mark outside task maximum.');
            }
            $fraction = $disposition === 'excused' ? null : ($disposition === 'absent' ? ($absentPolicy === 'zero' ? BigRational::of(0) : null)
                : ($units === null ? null : BigRational::of($units)->dividedBy($task['max_mark_units'])));
            $taskResults[] = ['task_id' => (int) $task['id'], 'percentage_bp' => $fraction === null ? null : $this->basisPoints($fraction, $mode),
                'disposition' => $disposition, 'complete' => $fraction !== null, 'weight_bp' => $weight];
            if ($weight === 0) {
                continue;
            }
            if (! is_string($component) || ! array_key_exists($component, $components)) {
                $complete = false;
                $warnings[] = 'unknown_task_component';

                continue;
            }
            $grouped[$component][] = ['weight' => $weight, 'fraction' => $fraction, 'excused' => $disposition === 'excused'];
        }
        $runningWeighted = $coverageWeighted = BigRational::of(0);
        $runningWeights = 0;
        $componentResults = [];
        foreach ($components as $name => $componentWeight) {
            if ($componentWeight === 0) {
                continue;
            }
            $declared = $excused = $known = 0;
            $weighted = BigRational::of(0);
            foreach ($grouped[$name] ?? [] as $task) {
                $declared += $task['weight'];
                if ($task['excused']) {
                    $excused += $task['weight'];

                    continue;
                }
                if ($task['fraction'] !== null) {
                    $known += $task['weight'];
                    $weighted = $weighted->plus($task['fraction']->multipliedBy($task['weight']));
                }
            }
            $eligible = $declared - $excused;
            $definitionComplete = $declared === 10000;
            $componentComplete = $definitionComplete && $eligible > 0 && $known === $eligible;
            if (! $componentComplete) {
                $complete = false;
            }
            if (! $definitionComplete) {
                $warnings[] = 'task_weights_incomplete:'.$name;
            }
            if ($eligible === 0) {
                $warnings[] = 'no_eligible_tasks:'.$name;
            }
            $running = $known > 0 ? $weighted->dividedBy($known) : null;
            if ($running !== null) {
                $runningWeighted = $runningWeighted->plus($running->multipliedBy($componentWeight));
                $runningWeights += $componentWeight;
            }
            $expectedEligible = max(10000, $declared) - $excused;
            $coverage = $expectedEligible > 0 ? BigRational::of($known)->dividedBy($expectedEligible) : BigRational::of(0);
            $coverageWeighted = $coverageWeighted->plus($coverage->multipliedBy($componentWeight));
            $componentResults[$name] = ['weight_bp' => $componentWeight, 'declared_task_weight_bp' => $declared, 'excused_task_weight_bp' => $excused,
                'known_task_weight_bp' => $known, 'running_bp' => $running === null ? null : $this->basisPoints($running, $mode),
                'percentage_bp' => $componentComplete ? $this->basisPoints($running, $mode) : null, 'complete' => $componentComplete];
        }
        $running = $runningWeights > 0 ? $runningWeighted->dividedBy($runningWeights) : null;
        $runningBp = $running === null ? null : $this->basisPoints($running, $mode);
        $coverageBp = $this->basisPoints($coverageWeighted->dividedBy(max(10000, $totalComponentWeight)), $mode);
        $official = $complete && $running !== null ? $runningBp : null;

        return ['percentage_bp' => $official, 'running_bp' => $runningBp, 'coverage_bp' => $coverageBp,
            'rating_level' => $official === null ? null : $this->rating($official), 'running_rating_level' => $runningBp === null ? null : $this->rating($runningBp),
            'complete' => $official !== null, 'provisional' => $official === null, 'components' => $componentResults, 'tasks' => $taskResults,
            'warnings' => array_values(array_unique($warnings)), 'rounding' => $rounding, 'absent_policy' => $absentPolicy];
    }

    public function percentage(int $units, int $maximum, string $rounding = 'half_up'): int
    {
        if ($maximum <= 0 || $units < 0 || $units > $maximum) {
            throw new InvalidArgumentException('Mark outside task maximum.');
        }

        return $this->basisPoints(BigRational::of($units)->dividedBy($maximum), $this->mode($rounding));
    }

    public function rating(int $basisPoints): int
    {
        if ($basisPoints < 0 || $basisPoints > 10000) {
            throw new InvalidArgumentException('Percentage outside rating range.');
        }

        return match (true) {
            $basisPoints < 3000 => 1, $basisPoints < 4000 => 2, $basisPoints < 5000 => 3,
            $basisPoints < 6000 => 4, $basisPoints < 7000 => 5, $basisPoints < 8000 => 6, default => 7
        };
    }

    private function mode(string $rounding): RoundingMode
    {
        return match ($rounding) {
            'half_up' => RoundingMode::HalfUp, 'half_even' => RoundingMode::HalfEven, 'floor' => RoundingMode::Floor,
            default => throw new InvalidArgumentException('Unknown rounding policy.')
        };
    }

    private function basisPoints(BigRational $fraction, RoundingMode $mode): int
    {
        return $fraction->multipliedBy(10000)->toScale(0, $mode)->toInt();
    }
}
