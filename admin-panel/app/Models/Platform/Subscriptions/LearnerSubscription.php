<?php

declare(strict_types=1);

namespace App\Models\Platform\Subscriptions;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $school_id
 * @property string $reference
 * @property string $plan
 * @property int|null $learner_id
 * @property int|null $guardian_user_id
 * @property string|null $sponsor_name
 * @property int|null $places
 * @property string $status
 * @property int $price_cents
 * @property string $currency
 * @property int $period_months
 * @property string $provider
 * @property string|null $provider_ref
 * @property Carbon|null $current_period_start
 * @property Carbon|null $current_period_end
 * @property string|null $request_key
 * @property string|null $cancel_key
 * @property Carbon|null $activated_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $created_at
 *
 * A subscription to the paid Mastery features (Action Plan 3.20): one child's
 * (per_child, or family for each further child of the same guardian), the
 * school's licence for every learner, or a sponsor's places for named
 * learners. Never deleted; the database also refuses a change of identity or
 * price and a status that moves backwards.
 * engineering/specs/platform/learner-subscriptions.md.
 */
class LearnerSubscription extends Model
{
    use BelongsToSchool;

    public const PER_CHILD = 'per_child';

    public const FAMILY = 'family';

    public const SCHOOL_LICENCE = 'school_licence';

    public const SPONSORED = 'sponsored';

    public const PLANS = [self::PER_CHILD, self::FAMILY, self::SCHOOL_LICENCE, self::SPONSORED];

    public const REQUESTED = 'requested';

    public const ACTIVE = 'active';

    public const CANCELLED = 'cancelled';

    public const ENDED = 'ended';

    public const DECLINED = 'declined';

    /** A subscription in one of these, within its paid period, entitles. */
    public const PAID_STATUSES = [self::ACTIVE, self::CANCELLED];

    protected $table = 'learner_subscriptions';

    protected $guarded = ['id', 'school_id', 'open_learner', 'open_licence'];

    protected static function booted(): void
    {
        static::deleting(function (): void {
            throw new LogicException('Subscriptions are retained; cancel, decline or end them.');
        });
    }

    protected function casts(): array
    {
        return [
            'learner_id' => 'integer', 'guardian_user_id' => 'integer', 'places' => 'integer', 'price_cents' => 'integer', 'period_months' => 'integer',
            'current_period_start' => 'date', 'current_period_end' => 'date', 'activated_at' => 'datetime', 'cancelled_at' => 'datetime', 'closed_at' => 'datetime',
            'requested_by' => 'integer', 'cancelled_by' => 'integer', 'closed_by' => 'integer',
        ];
    }

    public function isPersonal(): bool
    {
        return in_array($this->plan, [self::PER_CHILD, self::FAMILY], true);
    }

    /** Paid for and within its period on this day (Y-m-d). */
    public function coversDay(string $day): bool
    {
        return in_array($this->status, self::PAID_STATUSES, true) && $this->current_period_end !== null && $this->current_period_end->toDateString() >= $day;
    }

    /** @return HasMany<LearnerSubscriptionPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(LearnerSubscriptionPayment::class, 'subscription_id');
    }

    /** @return HasMany<LearnerSubscriptionMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(LearnerSubscriptionMember::class, 'subscription_id');
    }
}
