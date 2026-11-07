<?php

declare(strict_types=1);

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Navigate Ed's monthly invoice to a school group: the member schools' own
 * monthly lines rolled up (school-groups.md). Once issued or discarded it
 * never changes and it is never deleted; the database refuses the same.
 */
class SchoolGroupInvoice extends Model
{
    public const DRAFT = 'draft';

    public const ISSUED = 'issued';

    public const DISCARDED = 'discarded';

    protected $table = 'school_group_invoices';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updating(function (SchoolGroupInvoice $invoice): void {
            if ($invoice->getOriginal('status') !== self::DRAFT && array_diff(array_keys($invoice->getDirty()), ['updated_at']) !== []) {
                throw new LogicException('An issued or discarded group invoice never changes.');
            }
        });
        static::deleting(function (): void {
            throw new LogicException('Group invoices are never deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'period_start' => 'date', 'period_end' => 'date', 'issued_on' => 'date', 'due_on' => 'date',
            'computed_at' => 'datetime', 'issued_at' => 'datetime', 'discarded_at' => 'datetime',
            'school_group_id' => 'integer', 'vat_rate_bp' => 'integer', 'schools' => 'integer', 'learners' => 'integer',
            'subtotal_cents' => 'integer', 'vat_cents' => 'integer', 'total_cents' => 'integer', 'open_key' => 'integer',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === self::DRAFT;
    }

    public function isIssued(): bool
    {
        return $this->status === self::ISSUED;
    }

    /** Each school's lines, across schools: the group invoice is HQ's own document. */
    public function lines(): HasMany
    {
        return $this->hasMany(SchoolGroupInvoiceLine::class, 'group_invoice_id')->withoutGlobalScopes()->orderBy('position');
    }

    public function schoolRows(): HasMany
    {
        return $this->hasMany(SchoolGroupInvoiceSchool::class, 'group_invoice_id')->withoutGlobalScopes()->orderBy('school_id');
    }
}
