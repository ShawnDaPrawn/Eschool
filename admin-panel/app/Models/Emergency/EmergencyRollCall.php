<?php

declare(strict_types=1);

namespace App\Models\Emergency;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** Whether one learner is accounted for during one alert. No row means unaccounted. */
class EmergencyRollCall extends Model
{
    use BelongsToSchool;

    protected $table = 'emergency_roll_calls';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'marked_at' => UtcInstant::class];
    }
}
