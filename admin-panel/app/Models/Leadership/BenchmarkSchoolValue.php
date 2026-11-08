<?php

declare(strict_types=1);

namespace App\Models\Leadership;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One school's own school-level figure for one weekly period (Action Plan
 * 5.5): its comparison group and whether it was counted in that group's
 * published figures. Never a learner. Written only by the weekly snapshot.
 * engineering/specs/leadership/benchmarking.md.
 *
 * @property int $id
 * @property Carbon $period_end
 * @property string $metric
 * @property string|null $group_key
 * @property int|null $value_bp
 * @property bool $contributed
 * @property Carbon $computed_at
 */
class BenchmarkSchoolValue extends Model
{
    use BelongsToSchool;

    protected $table = 'benchmark_school_values';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['period_end' => 'date', 'value_bp' => 'integer', 'contributed' => 'boolean', 'computed_at' => 'datetime'];
    }
}
