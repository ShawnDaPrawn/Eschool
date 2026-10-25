<?php

declare(strict_types=1);

namespace App\Models\Wellbeing;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use BelongsToSchool;

    protected $table = 'wellbeing_appointments';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'location' => 'encrypted', 'notes' => 'encrypted', 'revision' => 'integer'];
    }
}
