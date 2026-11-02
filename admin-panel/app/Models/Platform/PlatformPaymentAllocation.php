<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * How much of a payment, or of a credit note's credit, settles one invoice
 * (hq-billing.md). Never deleted: reversing the payment marks it reversed.
 */
class PlatformPaymentAllocation extends Model
{
    use BelongsToSchool;

    protected $table = 'platform_payment_allocations';

    protected $guarded = ['id', 'school_id'];

    protected static function booted(): void
    {
        static::updating(function (PlatformPaymentAllocation $allocation): void {
            if ($allocation->getOriginal('reversed_at') !== null || array_diff(array_keys($allocation->getDirty()), ['reversed_at', 'reversed_by', 'updated_at']) !== []) {
                throw new LogicException('An allocation is never edited; reverse its payment instead.');
            }
        });
        static::deleting(function (): void {
            throw new LogicException('Allocations are never deleted.');
        });
    }

    protected function casts(): array
    {
        return ['invoice_id' => 'integer', 'payment_id' => 'integer', 'credit_note_id' => 'integer', 'amount_cents' => 'integer', 'reversed_at' => 'datetime'];
    }
}
