<?php

declare(strict_types=1);

namespace App\Models\Wellbeing;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use BelongsToSchool;

    protected $table = 'wellbeing_events';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['snapshot' => 'encrypted:array'];
    }
}
