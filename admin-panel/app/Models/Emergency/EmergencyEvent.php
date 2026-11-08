<?php

declare(strict_types=1);

namespace App\Models\Emergency;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * The emergency audit trail: who prepared, confirmed, cleared or cancelled an
 * alert, who marked a roll call and who released a child. Append only; the
 * details never hold a visitor's or collector's personal details.
 */
class EmergencyEvent extends Model
{
    use BelongsToSchool;

    public const UPDATED_AT = null;

    protected $table = 'emergency_events';

    protected $guarded = ['id', 'school_id'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('The emergency audit trail is append only.'));
        static::deleting(fn () => throw new \LogicException('The emergency audit trail is append only.'));
    }

    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => UtcInstant::class];
    }
}
