<?php

declare(strict_types=1);

namespace App\Models\Leadership;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One completed weekly benchmarking snapshot (Action Plan 5.5): its period end
 * and when it completed. Platform-owned; names no school. The latest run is
 * the current week, even when every group was too small to publish.
 * engineering/specs/leadership/benchmarking.md.
 *
 * @property int $id
 * @property Carbon $period_end
 * @property Carbon $completed_at
 */
class BenchmarkRun extends Model
{
    protected $table = 'benchmark_runs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['period_end' => 'date', 'completed_at' => 'datetime'];
    }
}
