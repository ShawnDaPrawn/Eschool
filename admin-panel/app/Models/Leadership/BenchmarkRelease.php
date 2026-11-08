<?php

declare(strict_types=1);

namespace App\Models\Leadership;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The published figures of one comparison group for one weekly period and
 * figure (Action Plan 5.5): lower quartile, median and upper quartile only.
 * Platform-owned: names no school and holds no school's own value.
 * engineering/specs/leadership/benchmarking.md.
 *
 * @property int $id
 * @property Carbon $period_end
 * @property string $group_key
 * @property string $metric
 * @property string $status
 * @property Carbon $basis_period_end
 * @property int $lower_quartile_bp
 * @property int $median_bp
 * @property int $upper_quartile_bp
 * @property Carbon $computed_at
 */
class BenchmarkRelease extends Model
{
    public const RELEASED = 'released';

    public const HELD = 'held';

    protected $table = 'benchmark_releases';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['period_end' => 'date', 'basis_period_end' => 'date', 'lower_quartile_bp' => 'integer', 'median_bp' => 'integer',
            'upper_quartile_bp' => 'integer', 'computed_at' => 'datetime'];
    }
}
