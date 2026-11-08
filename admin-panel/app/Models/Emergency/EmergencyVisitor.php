<?php

declare(strict_types=1);

namespace App\Models\Emergency;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * A visitor signed in at the gate. Personal details are encrypted and are
 * erased after the school's retention period (EmergencyVisitors::purge).
 */
class EmergencyVisitor extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'emergency_visitors';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['name', 'phone', 'organisation', 'reason', 'host'];

    protected function casts(): array
    {
        return ['name' => 'encrypted', 'phone' => 'encrypted', 'organisation' => 'encrypted', 'reason' => 'encrypted', 'host' => 'encrypted', 'revision' => 'integer',
            'signed_in_at' => UtcInstant::class, 'signed_out_at' => UtcInstant::class, 'redacted_at' => UtcInstant::class];
    }
}
