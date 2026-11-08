<?php

declare(strict_types=1);

namespace App\Models\Emergency;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** One entry in the school's drill log: planned and actual times, type and notes. */
class EmergencyDrill extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'emergency_drills';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'planned_at' => UtcInstant::class, 'started_at' => UtcInstant::class, 'ended_at' => UtcInstant::class];
    }
}
