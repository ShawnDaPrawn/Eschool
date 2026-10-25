<?php

declare(strict_types=1);

namespace App\Models\Wellbeing;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class IncidentReport extends Model
{
    use BelongsToSchool;

    protected $table = 'incident_reports';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['anonymous' => 'boolean', 'body' => 'encrypted', 'identified_at' => 'immutable_datetime', 'revision' => 'integer'];
    }
}
