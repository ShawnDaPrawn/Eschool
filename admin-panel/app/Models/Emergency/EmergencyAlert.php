<?php

declare(strict_types=1);

namespace App\Models\Emergency;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** A critical alert or lockdown: prepared, confirmed (sent), then cleared or cancelled. */
class EmergencyAlert extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'emergency_alerts';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['is_drill' => 'boolean', 'revision' => 'integer', 'recipient_count' => 'integer', 'prepared_at' => UtcInstant::class, 'activated_at' => UtcInstant::class,
            'all_clear_at' => UtcInstant::class, 'cancelled_at' => UtcInstant::class];
    }
}
