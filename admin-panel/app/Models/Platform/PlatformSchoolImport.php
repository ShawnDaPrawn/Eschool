<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An onboarding import Navigate Ed Operations run on a school's behalf
 * (Action Plan SA.11). The file and its preview are personal information:
 * both are encrypted, and both are wiped once the import is finished.
 * Written only by App\Services\Platform\Saas\PlatformSchoolImports.
 * engineering/specs/platform/hq-imports.md.
 *
 * @property int $id
 * @property int $school_id
 * @property string $kind
 * @property string $status
 * @property int|null $requested_by
 * @property int|null $approver_id
 * @property string|null $contents
 * @property list<array<string, mixed>>|null $preview
 * @property string $file_sha256
 * @property string $fingerprint
 * @property array<string, string> $mapping
 * @property array<string, mixed> $options
 * @property array<string, int> $counts
 * @property int $rows
 * @property string|null $approved_sha256
 * @property string|null $decision_reason
 * @property Carbon|null $decided_at
 * @property int|null $applied_by
 * @property Carbon|null $applied_at
 * @property array<string, int>|null $result
 * @property int|null $closed_by
 * @property string|null $close_reason
 * @property Carbon $expires_at
 * @property Carbon|null $created_at
 */
class PlatformSchoolImport extends Model
{
    use BelongsToSchool;

    public const AWAITING = 'awaiting_approval';

    public const APPROVED = 'approved';

    public const APPLIED = 'applied';

    public const DECLINED = 'declined';

    public const CANCELLED = 'cancelled';

    /** Stopped because the file or the school's records changed after the preview. */
    public const VOID = 'void';

    public const EXPIRED = 'expired';

    /** States in which the file is still held and the import can go on. */
    public const OPEN = [self::AWAITING, self::APPROVED];

    protected $table = 'platform_school_imports';

    protected $guarded = ['id', 'school_id'];

    /** Never in arrays or JSON: the file and its rows are personal information. */
    protected $hidden = ['contents', 'preview'];

    protected function casts(): array
    {
        return [
            'contents' => 'encrypted',
            'preview' => 'encrypted:array',
            'mapping' => 'array',
            'options' => 'array',
            'counts' => 'array',
            'result' => 'array',
            'rows' => 'integer',
            'requested_by' => 'integer',
            'approver_id' => 'integer',
            'applied_by' => 'integer',
            'closed_by' => 'integer',
            'decided_at' => 'datetime',
            'applied_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }
}
