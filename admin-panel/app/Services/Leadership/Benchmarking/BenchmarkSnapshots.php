<?php

declare(strict_types=1);

namespace App\Services\Leadership\Benchmarking;

use App\Models\Leadership\BenchmarkParticipation;
use App\Models\Leadership\BenchmarkRelease;
use App\Models\Leadership\BenchmarkRun;
use App\Models\Leadership\BenchmarkSchoolValue;
use App\Models\Platform\School;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * The weekly benchmarking snapshot (Action Plan 5.5), platform code.
 *
 * 1. Each participating school's own figures are worked out inside that
 *    school (BenchmarkMetrics) and kept as its own rows.
 * 2. For each comparison group and figure, the median and quartiles are
 *    published only when at least k schools have the figure (k-anonymity).
 * 3. Differencing guard: when the group's schools changed by fewer than
 *    min_membership_change since its last published figures, those figures
 *    are kept unchanged ("held") instead of publishing new ones, so nobody
 *    can work out one school's value from the difference between two weeks.
 * 4. A published week is final: running it again never changes or replaces
 *    its figures, nor the members they came from, so a rerun cannot be used
 *    to publish the same week twice with one school more or less.
 *
 * No query here returns a learner; the published rows name no school.
 * engineering/specs/leadership/benchmarking.md.
 */
class BenchmarkSnapshots
{
    public const TIMEZONE = 'Africa/Johannesburg';

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /** k, never below 3 whatever the configuration says. */
    public static function minimumGroupSize(): int
    {
        return max(3, (int) config('benchmarking.min_group_size', 5));
    }

    public static function minimumChange(): int
    {
        return max(2, (int) config('benchmarking.min_membership_change', 3));
    }

    /** The Sunday that ends the last complete week. */
    public static function defaultPeriod(): string
    {
        return CarbonImmutable::now(self::TIMEZONE)->startOfWeek(CarbonImmutable::MONDAY)->subDay()->toDateString();
    }

    /**
     * @return array{period_end: string, schools: int, failed: int, released: int, held: int, withheld: int}
     */
    public function run(?string $periodEnd = null): array
    {
        $period = $this->period($periodEnd);
        $participants = $this->currentSchool->acrossSchools(fn () => BenchmarkParticipation::acrossSchools()
            ->join('schools', 'schools.id', '=', 'benchmark_participations.school_id')
            ->where('benchmark_participations.opted_in', true)->where('schools.status', '!=', School::ARCHIVED)
            ->orderBy('benchmark_participations.school_id')->pluck('benchmark_participations.school_id')
            ->map(fn ($id) => (int) $id)->all());
        // Running a week again recomputes only what was not published: the week's
        // figures and the rows of the schools counted in them stay as they were.
        $this->currentSchool->acrossSchools(function () use ($period): void {
            BenchmarkSchoolValue::acrossSchools()->whereDate('period_end', $period)->where('contributed', false)->delete();
        });
        $failed = 0;
        $now = now();
        foreach ($participants as $id) {
            try {
                $figures = $this->currentSchool->run($id, fn () => app(BenchmarkMetrics::class)->forSchool($period));
            } catch (Throwable $e) {
                $failed++;
                Log::error('Benchmark figures failed', ['school_id' => $id, 'exception_class' => $e::class]);

                continue;
            }
            $this->currentSchool->run($id, function () use ($figures, $period, $now): void {
                $kept = BenchmarkSchoolValue::query()->whereDate('period_end', $period)->pluck('metric')->all();
                foreach (array_diff(BenchmarkMetrics::METRICS, $kept) as $metric) {
                    $value = $figures['values'][$metric] ?? null;
                    BenchmarkSchoolValue::query()->create(['period_end' => $period, 'metric' => $metric, 'group_key' => $figures['group_key'],
                        'value_bp' => $value === null ? null : max(0, min(10000, (int) $value)), 'contributed' => false, 'computed_at' => $now]);
                }
            });
        }
        $counts = $this->currentSchool->acrossSchools(fn () => DB::transaction(fn () => $this->release($period, $now)));
        BenchmarkRun::query()->updateOrCreate(['period_end' => $period], ['completed_at' => $now]);

        return ['period_end' => $period, 'schools' => count($participants) - $failed, 'failed' => $failed] + $counts;
    }

    /** @return array{released: int, held: int, withheld: int} */
    private function release(string $period, mixed $now): array
    {
        $k = self::minimumGroupSize();
        $m = self::minimumChange();
        $counts = ['released' => 0, 'held' => 0, 'withheld' => 0];
        $rows = BenchmarkSchoolValue::acrossSchools()->whereDate('period_end', $period)->whereNotNull('group_key')->whereNotNull('value_bp')
            ->orderBy('group_key')->orderBy('metric')->get(['school_id', 'metric', 'group_key', 'value_bp']);
        $published = BenchmarkRelease::query()->whereDate('period_end', $period)->lockForUpdate()->get()
            ->keyBy(fn (BenchmarkRelease $release) => $release->group_key.'|'.$release->metric);
        foreach ($rows->groupBy(fn (BenchmarkSchoolValue $row) => $row->group_key.'|'.$row->metric) as $key => $set) {
            $group = (string) $set->first()->group_key;
            $metric = (string) $set->first()->metric;
            $existing = $published->get($key);
            if ($existing !== null) {
                // Already published for this week: final, whoever joined or left since.
                $counts[$existing->status === BenchmarkRelease::HELD ? 'held' : 'released']++;

                continue;
            }
            $members = $set->map(fn (BenchmarkSchoolValue $row) => (int) $row->getAttribute('school_id'))->unique()->sort()->values()->all();
            if (count($members) < $k) {
                // Too few schools: nothing is published, and nothing older is shown either.
                $counts['withheld']++;

                continue;
            }
            $previous = BenchmarkRelease::query()->where('group_key', $group)->where('metric', $metric)->whereDate('period_end', '<', $period)
                ->orderByDesc('period_end')->lockForUpdate()->first();
            if ($previous !== null) {
                $before = BenchmarkSchoolValue::acrossSchools()->whereDate('period_end', $previous->basis_period_end->toDateString())
                    ->where('group_key', $group)->where('metric', $metric)->where('contributed', true)
                    ->pluck('school_id')->map(fn ($id) => (int) $id)->all();
                $changed = count(array_diff($members, $before)) + count(array_diff($before, $members));
                if ($changed > 0 && $changed < $m) {
                    BenchmarkRelease::query()->create(['period_end' => $period, 'group_key' => $group, 'metric' => $metric, 'status' => BenchmarkRelease::HELD,
                        'basis_period_end' => $previous->basis_period_end->toDateString(), 'lower_quartile_bp' => $previous->lower_quartile_bp,
                        'median_bp' => $previous->median_bp, 'upper_quartile_bp' => $previous->upper_quartile_bp, 'computed_at' => $now]);
                    $counts['held']++;

                    continue;
                }
            }
            $values = $set->map(fn (BenchmarkSchoolValue $row) => (int) $row->value_bp)->sort()->values()->all();
            BenchmarkRelease::query()->create(['period_end' => $period, 'group_key' => $group, 'metric' => $metric, 'status' => BenchmarkRelease::RELEASED,
                'basis_period_end' => $period, 'lower_quartile_bp' => self::rounded(self::quantile($values, 1, 4)),
                'median_bp' => self::rounded(self::quantile($values, 1, 2)), 'upper_quartile_bp' => self::rounded(self::quantile($values, 3, 4)), 'computed_at' => $now]);
            BenchmarkSchoolValue::acrossSchools()->whereDate('period_end', $period)->where('group_key', $group)->where('metric', $metric)
                ->whereIn('school_id', $members)->update(['contributed' => true]);
            $counts['released']++;
        }

        return $counts;
    }

    /**
     * The p = num/den quantile of sorted integers, interpolated between the
     * two nearest values (the usual "type 7" definition), in whole numbers.
     *
     * @param  list<int>  $sorted
     */
    public static function quantile(array $sorted, int $num, int $den): int
    {
        $n = count($sorted);
        $scaled = ($n - 1) * $num;
        $index = intdiv($scaled, $den);
        $remainder = $scaled % $den;
        if ($remainder === 0 || $index + 1 >= $n) {
            return $sorted[$index];
        }

        return $sorted[$index] + intdiv(($sorted[$index + 1] - $sorted[$index]) * $remainder * 2 + $den, 2 * $den);
    }

    /** Rounded half up to the published step (whole percentage points by default). */
    public static function rounded(int $bp): int
    {
        $step = max(1, (int) config('benchmarking.round_bp', 100));

        return min(10000, intdiv($bp + intdiv($step, 2), $step) * $step);
    }

    private function period(?string $periodEnd): string
    {
        if ($periodEnd === null || $periodEnd === '') {
            return self::defaultPeriod();
        }
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $periodEnd, self::TIMEZONE);
        $latest = BenchmarkRun::query()->max('period_end') ?? BenchmarkRelease::query()->max('period_end');
        if ($date === false || $date->format('Y-m-d') !== $periodEnd || ! $date->isSunday() || $periodEnd > self::defaultPeriod()
            || ($latest !== null && $periodEnd < substr((string) $latest, 0, 10))) {
            // Periods end on a Sunday that has passed, and history is never rewritten behind a later week.
            throw ValidationException::withMessages(['period' => __('Choose a past Sunday no earlier than the latest benchmark week.')]);
        }

        return $periodEnd;
    }
}
