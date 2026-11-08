<?php

declare(strict_types=1);

namespace App\Models\Activities;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * A learner on one school's team sheet for a fixture. The learner id never
 * leaves the school; only the sheet label and position are shown to the other
 * school, and only when the school chose to share its sheet.
 */
class FixtureSelection extends Model
{
    use BelongsToSchool;

    protected $table = 'shared_fixture_selections';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['learner_id' => 'integer', 'shared_fixture_side_id' => 'integer'];
    }
}
