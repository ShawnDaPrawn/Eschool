<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable $created_at
 * @property array $private_vendor_snapshot
 */
class VendorSettlement extends Model
{
    use BelongsToSchool;

    protected $table = 'vendor_settlements';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['private_vendor_snapshot'];

    protected function casts(): array
    {
        return ['vendor_id' => 'integer', 'vendor_revision' => 'integer', 'order_count' => 'integer', 'gross_cents' => 'integer', 'sales_net_cents' => 'integer', 'vat_cents' => 'integer', 'collector_fee_cents' => 'integer', 'payout_cents' => 'integer', 'prepared_by' => 'integer', 'private_vendor_snapshot' => 'encrypted:array', 'created_at' => UtcInstant::class, 'sealed_at' => UtcInstant::class];
    }
}
