<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/** One line of a platform invoice; lines of an issued invoice never change (hq-billing.md). */
class PlatformInvoiceLine extends Model
{
    use BelongsToSchool;

    protected $table = 'platform_invoice_lines';

    protected $guarded = ['id', 'school_id'];

    protected static function booted(): void
    {
        $refuse = function (PlatformInvoiceLine $line): void {
            $status = PlatformInvoice::query()->whereKey($line->invoice_id)->value('status');
            if ($status !== null && $status !== PlatformInvoice::DRAFT) {
                throw new LogicException('The lines of an issued invoice never change.');
            }
        };
        static::updating($refuse);
        static::deleting($refuse);
    }

    protected function casts(): array
    {
        return ['position' => 'integer', 'learners' => 'integer', 'unit_cents' => 'integer', 'amount_cents' => 'integer', 'vat_cents' => 'integer'];
    }
}
