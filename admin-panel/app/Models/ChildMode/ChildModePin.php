<?php

declare(strict_types=1);

namespace App\Models\ChildMode;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A child's child-mode PIN at one school (Action Plan 4.2): a hash only,
 * never the PIN. Not Auditable: the audit log records that a PIN was set or
 * changed (ChildModePins), never the hash.
 *
 * @property int $id
 * @property int $learner_id
 * @property string $pin_hash
 * @property int $version
 * @property int $set_by
 * @property int $failed_attempts
 * @property Carbon|null $locked_until
 */
class ChildModePin extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $hidden = ['pin_hash'];

    protected function casts(): array
    {
        return ['version' => 'integer', 'failed_attempts' => 'integer', 'locked_until' => 'datetime', 'learner_id' => 'integer', 'set_by' => 'integer'];
    }

    public function lockedNow(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }
}
