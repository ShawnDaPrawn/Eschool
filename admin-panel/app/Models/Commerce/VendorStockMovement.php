<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class VendorStockMovement extends Model
{
    use BelongsToSchool;

    protected $table = 'vendor_stock_movements';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['private_reason'];

    protected function casts(): array
    {
        return ['product_id' => 'integer', 'actor_id' => 'integer', 'units_delta' => 'integer', 'before_units' => 'integer', 'after_units' => 'integer', 'private_reason' => 'encrypted', 'created_at' => 'immutable_datetime'];
    }
}
