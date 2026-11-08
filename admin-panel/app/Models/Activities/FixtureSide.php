<?php

declare(strict_types=1);

namespace App\Models\Activities;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * One school's own side of a shared fixture: its activity group, its team
 * label, whether it shares its team sheet, its private notes and its own
 * report of the score. Never read by another school except the label, the
 * sharing switch and the score report, through Fixtures.
 */
class FixtureSide extends Model
{
    use BelongsToSchool;

    protected $table = 'shared_fixture_sides';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['sheet_shared' => 'boolean', 'notes' => 'encrypted', 'reported_host_score' => 'integer', 'reported_guest_score' => 'integer',
            'reported_at' => 'datetime', 'revision' => 'integer', 'activity_group_id' => 'integer', 'shared_fixture_id' => 'integer'];
    }
}
