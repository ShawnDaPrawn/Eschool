<?php

declare(strict_types=1);

namespace App\Models\Emergency;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** A child released to a verified guardian or authorised collector during an emergency. Never changed. */
class EmergencyRelease extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'emergency_releases';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['collector_name', 'notes'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('An emergency release is never changed.'));
        static::deleting(fn () => throw new \LogicException('An emergency release is never deleted.'));
    }

    protected function casts(): array
    {
        return ['collector_name' => 'encrypted', 'notes' => 'encrypted', 'released_at' => UtcInstant::class];
    }
}
