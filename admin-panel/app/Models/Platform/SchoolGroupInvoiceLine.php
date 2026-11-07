<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/** A line of a group invoice, copied from one school's own monthly draft. Changed only while the group invoice is a draft. */
class SchoolGroupInvoiceLine extends Model
{
    use BelongsToSchool;

    protected $table = 'school_group_invoice_lines';

    protected $guarded = ['id', 'school_id'];

    protected static function booted(): void
    {
        $guard = function (SchoolGroupInvoiceLine $line): void {
            $status = SchoolGroupInvoice::query()->whereKey($line->getOriginal('group_invoice_id') ?? $line->group_invoice_id)->value('status');
            if ($status !== SchoolGroupInvoice::DRAFT) {
                throw new LogicException('Only a draft group invoice\'s lines change.');
            }
        };
        static::creating($guard);
        static::updating($guard);
        static::deleting($guard);
    }

    protected function casts(): array
    {
        return ['learners' => 'integer', 'unit_cents' => 'integer', 'amount_cents' => 'integer', 'vat_cents' => 'integer', 'position' => 'integer', 'source_invoice_id' => 'integer'];
    }
}
