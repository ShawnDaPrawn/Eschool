<?php

declare(strict_types=1);

namespace App\Models\Activities;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * One school's adjudicators' result for an academic competition: a draft
 * until it is submitted, when it becomes the school's report. The entrants'
 * own scores and places and the written reasons never leave the school.
 */
class AcademicResult extends Model
{
    use BelongsToSchool;

    public const DRAFT = 'draft';

    public const SUBMITTED = 'submitted';

    protected $table = 'academic_results';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['shared_fixture_id' => 'integer', 'host_score' => 'integer', 'guest_score' => 'integer', 'entrants' => 'encrypted:array', 'reasons' => 'encrypted',
            'recorded_by' => 'integer', 'submitted_at' => 'datetime', 'revision' => 'integer'];
    }
}
