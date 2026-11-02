<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * An EFT payment a school made to Navigate Ed (hq-billing.md). Never deleted
 * and never edited: a mistaken payment is reversed with a reason.
 */
class PlatformPayment extends Model
{
    use BelongsToSchool;

    public const REVERSAL = ['reversed_at', 'reversed_by', 'reversal_reason', 'updated_at'];

    protected $table = 'platform_payments';

    protected $guarded = ['id', 'school_id'];

    protected static function booted(): void
    {
        static::updating(function (PlatformPayment $payment): void {
            if ($payment->getOriginal('reversed_at') !== null || array_diff(array_keys($payment->getDirty()), self::REVERSAL) !== []) {
                throw new LogicException('A payment is never edited; reverse it with a reason and record it again.');
            }
        });
        static::deleting(function (): void {
            throw new LogicException('Payments are never deleted.');
        });
    }

    protected function casts(): array
    {
        return ['received_on' => 'date', 'amount_cents' => 'integer', 'reversed_at' => 'datetime', 'recorded_by' => 'integer', 'reversed_by' => 'integer'];
    }
}
