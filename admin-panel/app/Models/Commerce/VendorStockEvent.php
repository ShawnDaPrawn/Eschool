<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $product_id
 * @property int $manual_movement_id
 * @property int $order_line_id
 * @property int $order_action_id
 * @property int $sequence
 * @property int $units_delta
 * @property int $before_units
 * @property int $after_units
 * @property CarbonImmutable $created_at
 */
class VendorStockEvent extends Model
{
    use BelongsToSchool;

    protected $table = 'vendor_stock_events';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = [];

    protected function casts(): array
    {
        return ['product_id' => 'integer', 'manual_movement_id' => 'integer', 'order_line_id' => 'integer', 'order_action_id' => 'integer', 'sequence' => 'integer', 'units_delta' => 'integer', 'before_units' => 'integer', 'after_units' => 'integer', 'created_at' => UtcInstant::class];
    }
}
