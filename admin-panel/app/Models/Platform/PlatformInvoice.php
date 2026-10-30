<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Navigate Ed's invoice or credit note to a school (engineering/specs/platform/hq-billing.md),
 * written only from /saas through PlatformBilling. Once issued it never
 * changes: only the reminder columns may be written, and it is never deleted.
 * A correction is a credit note that refers to it.
 */
class PlatformInvoice extends Model
{
    use BelongsToSchool;

    public const INVOICE = 'invoice';

    public const CREDIT_NOTE = 'credit_note';

    public const DRAFT = 'draft';

    public const ISSUED = 'issued';

    public const DISCARDED = 'discarded';

    /** The only columns an issued invoice may still change. */
    public const AFTER_ISSUE = ['reminded_at', 'reminders', 'updated_at'];

    protected $table = 'platform_invoices';

    protected $guarded = ['id', 'school_id'];

    protected static function booted(): void
    {
        static::updating(function (PlatformInvoice $invoice): void {
            if ($invoice->getOriginal('status') !== self::DRAFT && array_diff(array_keys($invoice->getDirty()), self::AFTER_ISSUE) !== []) {
                throw new LogicException('An issued or discarded invoice never changes. Issue a credit note instead.');
            }
        });
        static::deleting(function (): void {
            throw new LogicException('Invoices are never deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'period_start' => 'date', 'period_end' => 'date', 'learners_counted_on' => 'date', 'issued_on' => 'date', 'due_on' => 'date',
            'computed_at' => 'datetime', 'issued_at' => 'datetime', 'discarded_at' => 'datetime', 'reminded_at' => 'datetime',
            'package_version' => 'integer', 'contracted_learners' => 'integer', 'learners' => 'integer', 'vat_rate_bp' => 'integer',
            'subtotal_cents' => 'integer', 'vat_cents' => 'integer', 'total_cents' => 'integer', 'reminders' => 'integer',
            'credits_invoice_id' => 'integer', 'contract_id' => 'integer',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PlatformInvoiceLine::class, 'invoice_id')->orderBy('position');
    }

    public function isDraft(): bool
    {
        return $this->status === self::DRAFT;
    }

    public function isIssued(): bool
    {
        return $this->status === self::ISSUED;
    }
}
