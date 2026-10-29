<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $order_id
 * @property int $product_id
 * @property int $product_revision
 * @property int $quantity
 * @property int $unit_price_cents
 * @property int $gross_cents
 * @property int $net_cents
 * @property int $vat_cents
 * @property int $vat_rate_bps
 * @property bool $tracks_stock
 */
class VendorOrderLine extends Model
{
    use BelongsToSchool;

    protected $table = 'vendor_order_lines';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = [];

    protected function casts(): array
    {
        return ['order_id' => 'integer', 'product_id' => 'integer', 'product_revision' => 'integer', 'quantity' => 'integer', 'unit_price_cents' => 'integer', 'gross_cents' => 'integer', 'net_cents' => 'integer', 'vat_cents' => 'integer', 'vat_rate_bps' => 'integer', 'tracks_stock' => 'boolean'];
    }
}
