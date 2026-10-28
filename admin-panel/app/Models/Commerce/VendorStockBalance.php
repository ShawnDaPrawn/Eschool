<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class VendorStockBalance extends Model
{
    use BelongsToSchool;

    protected $table = 'vendor_stock_balances';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = [];

    protected function casts(): array
    {
        return ['product_id' => 'integer', 'on_hand' => 'integer', 'revision' => 'integer'];
    }
}
