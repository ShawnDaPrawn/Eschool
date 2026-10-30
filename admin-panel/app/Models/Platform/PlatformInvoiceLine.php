<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/** One line of a platform invoice; only a draft's lines are ever written (hq-billing.md). */
class PlatformInvoiceLine extends Model
{
    use BelongsToSchool;

    protected $table = 'platform_invoice_lines';

    protected $guarded = ['id', 'school_id'];

    protected static function booted(): void
    {
        // Both the invoice the line belonged to and the one it would belong
        // to must be drafts. The parent is read without the school scope, so a
        // write from another school's context cannot slip past by finding no
        // invoice. Migration 2026_11_01_182000 enforces the same in the
        // database for writes that bypass the model.
        $refuse = function (PlatformInvoiceLine $line): void {
            $ids = array_values(array_unique(array_filter([(int) $line->getOriginal('invoice_id'), (int) $line->getAttribute('invoice_id')])));
            $statuses = PlatformInvoice::acrossSchools()->whereKey($ids)->pluck('status', 'id');
            foreach ($ids as $id) {
                if (($statuses[$id] ?? null) !== PlatformInvoice::DRAFT) {
                    throw new LogicException('Only a draft invoice\'s lines can be added, changed or removed.');
                }
            }
        };
        static::creating($refuse);
        static::updating($refuse);
        static::deleting($refuse);
    }

    protected function casts(): array
    {
        return ['position' => 'integer', 'learners' => 'integer', 'unit_cents' => 'integer', 'amount_cents' => 'integer', 'vat_cents' => 'integer'];
    }
}
