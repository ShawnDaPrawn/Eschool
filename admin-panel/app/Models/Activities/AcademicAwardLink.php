<?php

declare(strict_types=1);

namespace App\Models\Activities;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** An award the school presented to one of its own entrants for an academic competition (participation or placing). */
class AcademicAwardLink extends Model
{
    use BelongsToSchool;

    protected $table = 'academic_award_links';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['shared_fixture_id' => 'integer', 'learner_id' => 'integer', 'award_presentation_id' => 'integer', 'linked_by' => 'integer'];
    }
}
