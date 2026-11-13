<?php

declare(strict_types=1);

namespace App\Models\Activities;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * One adjudicator a school named for an academic competition: one of its own
 * staff, or someone from outside entered by name. Never shown to another school.
 */
class AcademicAdjudicator extends Model
{
    use BelongsToSchool;

    protected $table = 'academic_adjudicators';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['shared_fixture_id' => 'integer', 'user_id' => 'integer', 'added_by' => 'integer'];
    }
}
