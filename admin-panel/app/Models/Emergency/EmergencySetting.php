<?php

declare(strict_types=1);

namespace App\Models\Emergency;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** The school's emergency settings: how long visitor details are kept. */
class EmergencySetting extends Model
{
    use BelongsToSchool;

    protected $table = 'emergency_settings';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['visitor_retention_days' => 'integer', 'revision' => 'integer'];
    }
}
