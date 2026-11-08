<?php

declare(strict_types=1);

namespace App\Services\Leadership\Benchmarking;

use App\Models\Leadership\BenchmarkParticipation;
use App\Models\Leadership\BenchmarkRelease;
use App\Models\Leadership\BenchmarkSchoolValue;
use App\Models\User;
use App\Services\Leadership\LeadershipAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * What a school's leaders see of anonymous benchmarking (Action Plan 5.5),
 * and their choice to take part.
 *
 * Reciprocity: a school that does not take part sees no comparison at all.
 * A school that takes part sees its own figures next to the published
 * median and quartiles of its own comparison group, and only once its own
 * figures have been worked out since it joined. No other school is named,
 * counted or shown. engineering/specs/leadership/benchmarking.md.
 */
class SchoolBenchmarks
{
    public function read(User $actor): array
    {
        $this->authorise($actor);
        $school = app(CurrentSchool::class)->id();
        $participation = BenchmarkParticipation::query()->first();
        $result = ['participating' => (bool) $participation?->opted_in, 'rejoin_on' => $this->rejoinOn($participation),
            'minimum_group_size' => BenchmarkSnapshots::minimumGroupSize(), 'period_end' => null, 'group' => null, 'metrics' => []];
        if ($participation === null || ! $participation->opted_in) {
            return $this->recheck($actor, $school, $result);
        }
        // Only figures worked out after the school (last) joined.
        $period = BenchmarkSchoolValue::query()->where('computed_at', '>=', $participation->changed_at)->max('period_end');
        if ($period === null) {
            return $this->recheck($actor, $school, $result);
        }
        $period = substr((string) $period, 0, 10);
        $rows = BenchmarkSchoolValue::query()->whereDate('period_end', $period)->get()->keyBy('metric');
        $group = $rows->first()?->group_key;
        $result['period_end'] = $period;
        $result['group'] = self::describe($group);
        foreach (BenchmarkMetrics::METRICS as $metric) {
            $own = $rows->get($metric)?->value_bp;
            $release = $group === null ? null : BenchmarkRelease::query()->whereDate('period_end', $period)->where('group_key', $group)->where('metric', $metric)->first();
            $comparison = $release === null ? null : ['lower_quartile_bp' => $release->lower_quartile_bp, 'median_bp' => $release->median_bp,
                'upper_quartile_bp' => $release->upper_quartile_bp, 'as_of' => $release->basis_period_end->toDateString(), 'held' => $release->status === BenchmarkRelease::HELD];
            $result['metrics'][$metric] = ['own_bp' => $own === null ? null : BenchmarkSnapshots::rounded($own), 'comparison' => $comparison,
                'position' => $own === null || $comparison === null ? null : self::position(BenchmarkSnapshots::rounded($own), $comparison)];
        }

        return $this->recheck($actor, $school, $result);
    }

    /** Join or leave. Leaving is always allowed; joining again waits rejoin_after_days. */
    public function choose(User $actor, bool $participate): BenchmarkParticipation
    {
        $this->authorise($actor);

        return DB::transaction(function () use ($actor, $participate): BenchmarkParticipation {
            $row = BenchmarkParticipation::query()->lockForUpdate()->first();
            if ($row !== null && $row->opted_in === $participate) {
                return $row;
            }
            $rejoin = $this->rejoinOn($row);
            if ($participate && $rejoin !== null) {
                throw ValidationException::withMessages(['participate' => __('Your school left benchmarking recently. It can join again from :date.', ['date' => $rejoin])]);
            }
            $before = $row === null ? [] : ['opted_in' => $row->opted_in];
            $row ??= new BenchmarkParticipation;
            $row->fill(['opted_in' => $participate, 'changed_by' => $actor->id, 'changed_at' => now(),
                'left_at' => $participate ? $row->left_at : now()])->save();
            Audit::record($participate ? 'benchmarking_joined' : 'benchmarking_left', $row, $before, ['opted_in' => $participate]);

            return $row;
        });
    }

    /** sector:stages:band as words, for the school's own group only. */
    public static function describe(?string $group): ?array
    {
        if ($group === null || preg_match('/^([a-z_]+):([a-z_+]+):(\d+)$/', $group, $m) !== 1) {
            return null;
        }
        $bands = array_values((array) config('benchmarking.learner_bands', [1]));
        $index = (int) $m[3];
        $from = (int) ($bands[$index] ?? 0);
        $to = isset($bands[$index + 1]) ? (int) $bands[$index + 1] - 1 : null;

        return ['sector' => $m[1], 'stages' => explode('+', $m[2]), 'learners_from' => $from, 'learners_to' => $to];
    }

    /** Where the school's own figure falls: a band, never a rank. */
    public static function position(int $own, array $comparison): string
    {
        return match (true) {
            $own < $comparison['lower_quartile_bp'] => 'lower_quarter',
            $own > $comparison['upper_quartile_bp'] => 'upper_quarter',
            default => 'middle_half',
        };
    }

    public function authorise(User $actor): void
    {
        app(LeadershipAuthority::class)->principal($actor, (int) schoolSetting('session_year'));
    }

    private function rejoinOn(?BenchmarkParticipation $row): ?string
    {
        if ($row === null || $row->opted_in || $row->left_at === null) {
            return null;
        }
        $from = $row->left_at->copy()->addDays(max(0, (int) config('benchmarking.rejoin_after_days', 90)));

        return $from->isFuture() ? $from->toDateString() : null;
    }

    /** Authority and school are checked again after reading, as on the leadership dashboard. */
    private function recheck(User $actor, int $school, array $result): array
    {
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);
        $this->authorise($actor);

        return $result;
    }
}
