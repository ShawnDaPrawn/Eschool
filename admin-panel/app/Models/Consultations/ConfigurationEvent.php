<?php

declare(strict_types=1);

namespace App\Models\Consultations;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ConfigurationEvent extends Model
{
    use BelongsToSchool;

    protected $table = 'consultation_configuration_events';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['snapshot' => 'encrypted:array'];
    }
}
