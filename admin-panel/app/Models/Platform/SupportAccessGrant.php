<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * One request by a Navigate Ed staff member for audited, time-limited access
 * to one school (Action Plan SA.9), and its decision and session window.
 * Written only by App\Services\Platform\Saas\SupportAccess.
 * engineering/specs/platform/support-access.md.
 *
 * @property int $id
 * @property int $school_id
 * @property int $requested_by
 * @property string $requester_role
 * @property string $reason
 * @property string $scope
 * @property int $minutes
 * @property string $status
 * @property CarbonImmutable $requested_at
 * @property int|null $decided_by
 * @property CarbonImmutable|null $decided_at
 * @property bool $emergency
 * @property string|null $emergency_reason
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $ended_at
 * @property int|null $ended_by
 * @property string|null $ended_side
 */
class SupportAccessGrant extends Model
{
    use BelongsToSchool;

    public const PENDING = 'pending';

    public const ACTIVE = 'active';

    public const DECLINED = 'declined';

    public const WITHDRAWN = 'withdrawn';

    public const ENDED = 'ended';

    protected $table = 'support_access_grants';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return [
            'minutes' => 'integer',
            'emergency' => 'boolean',
            'requested_at' => UtcInstant::class,
            'decided_at' => UtcInstant::class,
            'starts_at' => UtcInstant::class,
            'expires_at' => UtcInstant::class,
            'ended_at' => UtcInstant::class,
        ];
    }

    /** Open now: approved (or overridden), not ended, and before its expiry. */
    public function isOpen(): bool
    {
        return $this->status === self::ACTIVE && $this->ended_at === null
            && $this->starts_at !== null && $this->starts_at->lessThanOrEqualTo(now())
            && $this->expires_at !== null && $this->expires_at->isFuture();
    }

    /** Still waiting for the school, and not lapsed. */
    public function isAwaitingDecision(): bool
    {
        return $this->status === self::PENDING
            && $this->requested_at->copy()->addHours((int) config('support_access.pending_hours', 24))->isFuture();
    }

    /**
     * What the grant is now: pending, lapsed, active, expired, declined,
     * withdrawn or ended.
     */
    public function state(): string
    {
        return match (true) {
            $this->status === self::PENDING => $this->isAwaitingDecision() ? 'pending' : 'lapsed',
            $this->status === self::ACTIVE => $this->isOpen() ? 'active' : 'expired',
            default => $this->status,
        };
    }
}
