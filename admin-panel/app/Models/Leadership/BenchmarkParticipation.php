<?php

declare(strict_types=1);

namespace App\Models\Leadership;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Whether this school takes part in anonymous benchmarking (Action Plan 5.5).
 * One row per school, written only by SchoolBenchmarks::choose().
 * engineering/specs/leadership/benchmarking.md.
 *
 * @property int $id
 * @property bool $opted_in
 * @property int|null $changed_by
 * @property Carbon $changed_at
 * @property Carbon|null $left_at
 */
class BenchmarkParticipation extends Model
{
    use BelongsToSchool;

    protected $table = 'benchmark_participations';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['opted_in' => 'boolean', 'changed_at' => 'datetime', 'left_at' => 'datetime'];
    }
}
