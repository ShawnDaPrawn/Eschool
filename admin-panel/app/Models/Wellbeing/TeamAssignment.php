<?php

declare(strict_types=1);

namespace App\Models\Wellbeing;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TeamAssignment extends Model
{
    use BelongsToSchool;

    protected $table = 'wellbeing_team_assignments';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'starts_on' => 'immutable_date', 'ends_on' => 'immutable_date', 'reason' => 'encrypted', 'revision' => 'integer'];
    }
}
