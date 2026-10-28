<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class VendorProduct extends Model
{
    use BelongsToSchool;

    protected $table = 'vendor_products';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = true;

    protected $hidden = [];

    protected function casts(): array
    {
        return ['vendor_id' => 'integer', 'category_id' => 'integer', 'price_cents' => 'integer', 'vat_rate_bps' => 'integer', 'tracks_stock' => 'boolean', 'revision' => 'integer', 'changed_by' => 'integer'];
    }
}
