<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One school's health figures for one of its days (Action Plan SA.10),
 * written only by PlatformSchoolHealth::snapshot() (the nightly
 * platform:school-health command) and read only from /saas. Counts and shares
 * only, never a person. engineering/specs/platform/hq-school-health.md.
 *
 * @property int $id
 * @property int $school_id
 * @property Carbon $snapshot_date
 * @property int $guardians
 * @property int $active_guardians
 * @property int $learners
 * @property int $reached_learners
 * @property int $flag_count
 * @property array<string, mixed> $metrics
 * @property list<array<string, mixed>> $flags
 * @property Carbon $computed_at
 */
class SchoolHealthSnapshot extends Model
{
    use BelongsToSchool;

    protected $table = 'school_health_snapshots';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'guardians' => 'integer',
            'active_guardians' => 'integer',
            'learners' => 'integer',
            'reached_learners' => 'integer',
            'flag_count' => 'integer',
            'metrics' => 'array',
            'flags' => 'array',
            'computed_at' => 'datetime',
        ];
    }
}
