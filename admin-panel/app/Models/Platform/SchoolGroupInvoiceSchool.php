<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * One school's month on a group invoice: the claim on the school's own
 * monthly draft that keeps it from being billed alone as well. One standing
 * claim per school and month (a unique index). Changed only while the group
 * invoice is a draft.
 */
class SchoolGroupInvoiceSchool extends Model
{
    use BelongsToSchool;

    protected $table = 'school_group_invoice_schools';

    protected $guarded = ['id', 'school_id'];

    protected static function booted(): void
    {
        $guard = function (SchoolGroupInvoiceSchool $row): void {
            $status = SchoolGroupInvoice::query()->whereKey($row->getOriginal('group_invoice_id') ?? $row->group_invoice_id)->value('status');
            if ($status !== SchoolGroupInvoice::DRAFT) {
                throw new LogicException('Only a draft group invoice\'s schools change.');
            }
        };
        static::creating($guard);
        static::updating($guard);
        static::deleting($guard);
    }

    protected function casts(): array
    {
        return ['learners' => 'integer', 'subtotal_cents' => 'integer', 'vat_cents' => 'integer', 'total_cents' => 'integer', 'invoice_computed_at' => 'datetime',
            'group_invoice_id' => 'integer', 'platform_invoice_id' => 'integer', 'open_key' => 'integer'];
    }
}
