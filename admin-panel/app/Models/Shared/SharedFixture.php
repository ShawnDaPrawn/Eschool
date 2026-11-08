<?php

declare(strict_types=1);

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Model;

/**
 * A fixture between schools: the facts every accepted participant shares.
 * Global (no school_id) and naming no school: schools are linked through
 * their own shared_participants rows. Read and written only through
 * App\Services\Activities\Fixtures, after SharedRecords checks the school
 * takes part (SharedRecordsBoundaryTest).
 */
class SharedFixture extends Model
{
    public const RECORD = 'fixture';

    protected $table = 'shared_fixtures';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['on_date' => 'date', 'host_is_home' => 'boolean', 'host_score' => 'integer', 'guest_score' => 'integer',
            'result_confirmed_at' => 'datetime', 'result_revision' => 'integer', 'revision' => 'integer', 'shared_competition_id' => 'integer'];
    }
}
