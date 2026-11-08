<?php

declare(strict_types=1);

namespace App\Services\Leadership\Benchmarking;

use App\Models\Growth\MasteryGrowthSnapshot;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolAccount;
use App\Models\Platform\SchoolHealthSnapshot;
use App\Models\SessionYear;
use App\Models\Term;
use App\Services\Growth\MasteryScore;
use App\Services\Leadership\LeadershipDashboard;
use App\Services\Leadership\MasteryImpact;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Saas\PlatformSchoolAccounts;
use App\Services\Platform\Stages\SchoolStages;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use LogicException;

/**
 * One school's own school-level figures for a weekly period (Action Plan 5.5),
 * and the comparison group it belongs to. Runs inside that school's context
 * only: every query is limited to the school, and the result is three numbers
 * and a group key, never a learner.
 * engineering/specs/leadership/benchmarking.md.
 */
class BenchmarkMetrics
{
    public const ATTENDANCE = 'attendance';

    public const RESULTS = 'results_movement';

    public const REACH = 'reach';

    public const METRICS = [self::ATTENDANCE, self::RESULTS, self::REACH];

    /**
     * @return array{group_key: ?string, values: array<string, ?int>}
     */
    public function forSchool(string $periodEnd): array
    {
        $context = app(CurrentSchool::class);
        if (! $context->isSet() || $context->isAcrossSchools()) {
            throw new LogicException('Benchmark figures are worked out inside one school.');
        }

        return [
            'group_key' => $this->groupKey($periodEnd),
            'values' => [
                self::ATTENDANCE => $this->attendance($periodEnd),
                self::RESULTS => $this->resultsMovement($periodEnd),
                self::REACH => $this->reach($periodEnd),
            ],
        ];
    }

    /** sector:stage+stage:band, or null when the school cannot be placed. */
    public function groupKey(string $periodEnd): ?string
    {
        $sector = SchoolAccount::query()->value('school_type');
        if (! is_string($sector) || ! in_array($sector, PlatformSchoolAccounts::TYPES, true)) {
            return null;
        }
        $stages = app(SchoolStages::class);
        $byLearner = [];
        foreach (Enrolment::with('homeroom.class')->onDate($periodEnd)->orderBy('id')->get() as $enrolment) {
            $byLearner[(int) $enrolment->learner_id] ??= $stages->forGrade($enrolment->homeroom?->class?->grade_level_id);
        }
        $learners = count($byLearner);
        $placed = array_filter($byLearner, fn ($stage) => $stage !== null);
        if ($learners === 0 || $placed === []) {
            return null;
        }
        $counts = array_count_values($placed);
        $share = max(1, (int) config('benchmarking.stage_share_bp', 1000));
        $mix = [];
        foreach (array_keys(SchoolStages::STAGES) as $stage) {
            if (($counts[$stage] ?? 0) * 10000 >= $share * count($placed)) {
                $mix[] = $stage;
            }
        }
        $band = self::band($learners);

        return $mix === [] || $band === null ? null : $sector.':'.implode('+', $mix).':'.$band;
    }

    /** The index of the learner band a school of this size is in. */
    public static function band(int $learners): ?int
    {
        $band = null;
        foreach (array_values((array) config('benchmarking.learner_bands', [1])) as $index => $from) {
            if ($learners >= (int) $from) {
                $band = $index;
            }
        }

        return $band;
    }

    /** Recorded attendance over the days before the period end, as the leadership dashboard counts it. */
    private function attendance(string $periodEnd): ?int
    {
        $year = SessionYear::query()->whereDate('start_date', '<=', $periodEnd)->whereDate('end_date', '>=', $periodEnd)
            ->orderByDesc('start_date')->orderByDesc('id')->first();
        if ($year === null) {
            return null;
        }
        $days = max(1, (int) config('benchmarking.attendance_days', 28));
        $start = max(CarbonImmutable::parse($periodEnd)->subDays($days - 1)->toDateString(), CarbonImmutable::parse($year->start_date)->toDateString());
        $enrolments = Enrolment::with('homeroom.class')->where('session_year_id', $year->id)->whereDate('starts_on', '<=', $periodEnd)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $start))->orderBy('id')->get();
        $events = app(LeadershipDashboard::class)->attendance((int) $year->id, $start, $periodEnd, $enrolments);
        $recorded = array_sum(array_column($events, 'recorded_days'));
        $present = array_sum(array_column($events, 'present_half_days'));
        if ($recorded < max(1, (int) config('benchmarking.min_attendance_learner_days', 100))) {
            return null;
        }

        return min(10000, intdiv($present * 10000 + $recorded, $recorded * 2));
    }

    /**
     * The share of learner-topics measured at the start of the term and now
     * that rose a mastery level (the mastery impact report's "rose").
     */
    private function resultsMovement(string $periodEnd): ?int
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        if (! $gate->allows('mastery')) {
            return null;
        }
        $term = Term::query()->whereNotNull('start_date')->whereNotNull('end_date')->whereDate('start_date', '<=', $periodEnd)
            ->orderByRaw('CASE WHEN end_date >= ? THEN 0 ELSE 1 END', [$periodEnd])->orderByDesc('start_date')->orderByDesc('id')->first();
        if ($term === null) {
            return null;
        }
        $row = MasteryGrowthSnapshot::query()->where('term_id', $term->id)->where('audience', MasteryImpact::AUDIENCE)
            ->where('policy_version', MasteryScore::VERSION)->whereNotNull('start_level')->whereNotNull('end_level')
            ->selectRaw('COUNT(*) AS measured, SUM(CASE WHEN end_level > start_level THEN 1 ELSE 0 END) AS rose')->first();
        $measured = (int) ($row?->getAttribute('measured') ?? 0);
        if ($measured < max(1, (int) config('benchmarking.min_results_measured', 20))) {
            return null;
        }

        return intdiv((int) $row->getAttribute('rose') * 10000, $measured);
    }

    /** Learners with a reachable guardian, from the school's own nightly health figures. */
    private function reach(string $periodEnd): ?int
    {
        $age = max(0, (int) config('benchmarking.reach_max_age_days', 7));
        $row = SchoolHealthSnapshot::query()->whereDate('snapshot_date', '<=', $periodEnd)
            ->whereDate('snapshot_date', '>=', CarbonImmutable::parse($periodEnd)->subDays($age)->toDateString())
            ->orderByDesc('snapshot_date')->first(['learners', 'reached_learners']);
        if ($row === null || $row->learners < max(1, (int) config('benchmarking.min_reach_learners', 10))) {
            return null;
        }

        return min(10000, intdiv($row->reached_learners * 10000, $row->learners));
    }
}
