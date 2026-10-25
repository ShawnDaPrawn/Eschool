<?php

declare(strict_types=1);

namespace App\Models\Wellbeing;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class CaseAssignment extends Model
{
    use BelongsToSchool;

    protected $table = 'support_case_assignments';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'starts_on' => 'immutable_date', 'ends_on' => 'immutable_date', 'reason' => 'encrypted', 'revision' => 'integer'];
    }
}
