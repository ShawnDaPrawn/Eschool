<?php

declare(strict_types=1);

namespace App\Models\Platform\Subscriptions;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $subscription_id
 * @property int $amount_cents
 * @property Carbon $received_on
 * @property Carbon $period_start
 * @property Carbon $period_end
 *
 * One paid period of a subscription: an EFT HQ Finance matched by its bank
 * reference, or a gateway charge. Immutable; its bank line is registered once
 * across every school in platform_bank_receipts.
 */
class LearnerSubscriptionPayment extends Model
{
    use BelongsToSchool;

    public const UPDATED_AT = null;

    protected $table = 'learner_subscription_payments';

    protected $guarded = ['id', 'school_id'];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Subscription payments are never edited.');
        });
        static::deleting(function (): void {
            throw new LogicException('Subscription payments are never deleted.');
        });
    }

    protected function casts(): array
    {
        return ['subscription_id' => 'integer', 'amount_cents' => 'integer', 'received_on' => 'date', 'period_start' => 'date', 'period_end' => 'date', 'recorded_by' => 'integer', 'created_at' => 'datetime'];
    }
}
