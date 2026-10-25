<?php

declare(strict_types=1);

namespace App\Models\Wellbeing;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class IncidentThread extends Model
{
    use BelongsToSchool;

    protected $table = 'incident_threads';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['body' => 'encrypted', 'internal' => 'boolean'];
    }
}
