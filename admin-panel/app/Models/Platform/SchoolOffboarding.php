<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One offboarding of a school that is leaving (Action Plan SA.11): states,
 * dates, hashes and counts only. engineering/specs/platform/offboarding.md.
 *
 * @property int $id
 * @property int $school_id
 * @property string $state
 * @property Carbon $delete_on
 * @property Carbon $receipt_deadline
 * @property array<string, mixed>|null $dry_run_report
 * @property Carbon|null $erasure_not_before
 * @property Carbon|null $erasure_started_at
 * @property array<string, mixed>|null $erasure_progress
 * @property Carbon|null $erased_at
 */
class SchoolOffboarding extends Model
{
    use BelongsToSchool;

    public const PREPARING = 'preparing';

    public const FAILED = 'failed';

    public const READY = 'ready';

    public const RECEIVED = 'received';

    public const ARCHIVED = 'archived';

    public const CANCELLED = 'cancelled';

    /** Two approvals given and the cooling-off period over: the school's rows are being erased, in batches. */
    public const ERASING = 'erasing';

    /** Erased, with a certificate. Final. */
    public const ERASED = 'erased';

    protected $table = 'school_offboardings';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return [
            'delete_on' => 'date', 'receipt_deadline' => 'date', 'export_ready_at' => 'datetime', 'last_downloaded_at' => 'datetime',
            'received_at' => 'datetime', 'archived_at' => 'datetime', 'cancelled_at' => 'datetime', 'legal_hold_at' => 'datetime',
            'deletion_requested_at' => 'datetime', 'deletion_approved_at' => 'datetime', 'dry_run_at' => 'datetime',
            'erasure_not_before' => 'datetime', 'erasure_started_at' => 'datetime', 'erasure_heartbeat_at' => 'datetime', 'erased_at' => 'datetime',
            'erasure_progress' => 'array', 'erasure_runs' => 'integer',
            'legal_hold' => 'boolean', 'dry_run_report' => 'array', 'download_count' => 'integer', 'table_count' => 'integer',
            'row_count' => 'integer', 'artifact_bytes' => 'integer',
        ];
    }
}
