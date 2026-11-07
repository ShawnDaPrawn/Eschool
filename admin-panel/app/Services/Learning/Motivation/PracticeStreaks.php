<?php

declare(strict_types=1);

namespace App\Services\Learning\Motivation;

use App\Models\Learning\PracticeDailySet;
use App\Services\Academics\AcademicAuthority;
use Carbon\CarbonImmutable;

/**
 * Practice streaks, done kindly (Action Plan 3.13).
 *
 * A practice day is a day on which the learner completed at least one daily
 * practice set (any subject). A run counts practice days. Only a missed
 * school day can break it: weekends, term breaks, holidays and school
 * closures never do, and practising on one of them still counts. One missed
 * school day a week (Monday to Sunday) is a rest day that keeps the run going;
 * a second missed school day in the same week ends it. Today never breaks a
 * run, because the day is not over. Best run is the longest run in the last
 * year. Streaks belong to one learner and are never ranked or compared.
 * engineering/specs/learning/practice-motivation.md.
 */
class PracticeStreaks
{
    /**
     * peak_days is the longest run reached on or after $peakFrom (today by default).
     *
     * @return array{current_days: int, best_days: int, rest_day_available: bool, peak_days: int}
     */
    public function summary(int $learnerId, ?string $today = null, ?string $peakFrom = null): array
    {
        $today ??= app(AcademicAuthority::class)->today();
        $end = CarbonImmutable::parse($today);
        $start = $end->subDays((int) config('motivation.streak.lookback_days', 366) - 1);
        $peakFrom ??= $today;
        $practised = PracticeDailySet::query()->where('learner_id', $learnerId)->whereNotNull('completed_at')
            ->whereBetween('completed_on', [$start->toDateString(), $today])->distinct()->pluck('completed_on')
            ->map(fn ($date) => $date->toDateString())->flip();
        $school = app(SchoolDays::class)->between($start->toDateString(), $today);
        $allowance = max(0, (int) config('motivation.streak.rest_days_per_week', 1));
        $run = $best = $peak = 0;
        $rests = [];
        foreach ($school as $date => $schoolDay) {
            if (isset($practised[$date])) {
                $run++;
            } elseif ($schoolDay && $date !== $today) {
                $week = CarbonImmutable::parse($date)->startOfWeek()->toDateString();
                if ($run > 0 && ($rests[$week] ?? 0) < $allowance) {
                    $rests[$week] = ($rests[$week] ?? 0) + 1;
                } else {
                    $run = 0;
                }
            }
            $best = max($best, $run);
            if ($date >= $peakFrom) {
                $peak = max($peak, $run);
            }
        }

        return ['current_days' => $run, 'best_days' => $best, 'rest_day_available' => ($rests[$end->startOfWeek()->toDateString()] ?? 0) < $allowance, 'peak_days' => $peak];
    }

    public function current(int $learnerId): int
    {
        return $this->summary($learnerId)['current_days'];
    }

    /** Distinct practice days in the school-local week (Monday to Sunday) that starts on $monday, up to $until. */
    public function daysInWeek(int $learnerId, string $monday, ?string $until = null): int
    {
        $until ??= CarbonImmutable::parse($monday)->addDays(6)->toDateString();

        return PracticeDailySet::query()->where('learner_id', $learnerId)->whereNotNull('completed_at')
            ->whereBetween('completed_on', [$monday, $until])->distinct()->count('completed_on');
    }
}
