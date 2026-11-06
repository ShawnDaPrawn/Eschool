<?php

declare(strict_types=1);

namespace App\Models\Emergency;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** The receipt of one exact emergency operation, so a retry returns it instead of acting again. */
class EmergencyOperation extends Model
{
    use BelongsToSchool;

    protected $table = 'emergency_operations';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['receipt'];

    protected function casts(): array
    {
        return ['receipt' => 'encrypted:array'];
    }
}
