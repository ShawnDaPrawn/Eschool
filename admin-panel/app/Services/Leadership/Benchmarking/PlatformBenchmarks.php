<?php

declare(strict_types=1);

namespace App\Services\Leadership\Benchmarking;

use App\Models\Leadership\BenchmarkRelease;
use App\Models\Leadership\BenchmarkRun;
use App\Models\User;
use App\Services\Platform\Saas\PlatformSchoolHealth;
use App\Services\Platform\Saas\PlatformStaffAccess;

/**
 * HQ's view of anonymous benchmarking (Action Plan 5.5): the same published
 * medians and quartiles that schools see, for every comparison group, and
 * nothing else. It reads only benchmark_releases, which names no school; no
 * school's own figure, membership or count is shown.
 * engineering/specs/leadership/benchmarking.md.
 */
class PlatformBenchmarks
{
    public function overview(?User $actor): array
    {
        $this->authorise($actor);
        // The latest completed week, even when it published nothing (every group too small):
        // HQ then shows no groups, as schools do, never an older week's figures.
        $period = BenchmarkRun::query()->max('period_end');
        $period = $period === null ? null : substr((string) $period, 0, 10);
        $groups = [];
        if ($period !== null) {
            foreach (BenchmarkRelease::query()->whereDate('period_end', $period)->orderBy('group_key')->orderBy('metric')->get() as $release) {
                $groups[$release->group_key] ??= ['group' => SchoolBenchmarks::describe($release->group_key), 'metrics' => []];
                $groups[$release->group_key]['metrics'][$release->metric] = ['lower_quartile_bp' => $release->lower_quartile_bp, 'median_bp' => $release->median_bp,
                    'upper_quartile_bp' => $release->upper_quartile_bp, 'as_of' => $release->basis_period_end->toDateString(), 'held' => $release->status === BenchmarkRelease::HELD];
            }
        }
        $this->authorise($actor);

        return ['period_end' => $period, 'groups' => array_values($groups), 'minimum_group_size' => BenchmarkSnapshots::minimumGroupSize()];
    }

    public function authorise(?User $actor): void
    {
        abort_unless(app(PlatformStaffAccess::class)->can($actor, PlatformSchoolHealth::ABILITY), 403);
    }
}
