<?php

declare(strict_types=1);

namespace App\Models\Shared;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * One school's link to a shared record (engineering/specs/platform/shared-records.md):
 * its role there and whether it has accepted. Each school reads only its own
 * rows; SharedRecords reads the other schools' rows of one record, and only
 * their school, role and state.
 */
class SharedParticipant extends Model
{
    use BelongsToSchool;

    public const INVITED = 'invited';

    public const ACCEPTED = 'accepted';

    public const DECLINED = 'declined';

    protected $table = 'shared_participants';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['record_id' => 'integer', 'revision' => 'integer', 'decided_at' => 'datetime'];
    }
}
