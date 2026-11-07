<?php

declare(strict_types=1);

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/** An EFT payment a school group made against its group invoice. Never edited or deleted: reversed, once, with a reason. */
class SchoolGroupPayment extends Model
{
    public const REVERSAL = ['reversed_at', 'reversed_by', 'reversal_reason', 'updated_at'];

    protected $table = 'school_group_payments';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updating(function (SchoolGroupPayment $payment): void {
            if ($payment->getOriginal('reversed_at') !== null || array_diff(array_keys($payment->getDirty()), self::REVERSAL) !== []) {
                throw new LogicException('A group payment is never edited; reverse it with a reason and record it again.');
            }
        });
        static::deleting(function (): void {
            throw new LogicException('Group payments are never deleted.');
        });
    }

    protected function casts(): array
    {
        return ['received_on' => 'date', 'amount_cents' => 'integer', 'reversed_at' => 'datetime', 'group_invoice_id' => 'integer', 'school_group_id' => 'integer'];
    }
}
