<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One POPIA data subject request that reached Navigate Ed, owned by the
 * school it concerns (Action Plan SA.11). The requester's details and the
 * school's note are encrypted at rest. engineering/specs/platform/data-requests.md.
 *
 * @property int $id
 * @property int $school_id
 * @property string $kind
 * @property string $status
 * @property string $channel
 * @property string $requester_role
 * @property string $requester_name
 * @property string $requester_contact
 * @property string $details
 * @property Carbon $received_on
 * @property Carbon $due_on
 * @property ?Carbon $passed_at
 * @property ?int $passed_by
 * @property ?string $outcome
 * @property ?string $outcome_note
 * @property ?Carbon $answered_at
 * @property ?int $answered_by
 * @property ?Carbon $closed_at
 * @property ?int $closed_by
 * @property ?string $closing_note
 * @property ?int $logged_by
 */
class DataRequest extends Model
{
    use BelongsToSchool;

    protected $table = 'platform_data_requests';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['requester_name', 'requester_contact', 'details', 'outcome_note', 'closing_note'];

    protected function casts(): array
    {
        return [
            'requester_name' => 'encrypted', 'requester_contact' => 'encrypted', 'details' => 'encrypted', 'outcome_note' => 'encrypted', 'closing_note' => 'encrypted',
            'received_on' => 'date', 'due_on' => 'date', 'passed_at' => 'datetime', 'answered_at' => 'datetime', 'closed_at' => 'datetime',
        ];
    }
}
