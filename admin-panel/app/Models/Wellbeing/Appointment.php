<?php

declare(strict_types=1);

namespace App\Models\Wellbeing;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use BelongsToSchool;

    protected $table = 'wellbeing_appointments';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['starts_at' => UtcInstant::class, 'ends_at' => UtcInstant::class, 'location' => 'encrypted', 'notes' => 'encrypted', 'revision' => 'integer'];
    }
}
