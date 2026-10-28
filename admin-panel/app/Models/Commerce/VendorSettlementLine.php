<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class VendorSettlementLine extends Model
{
    use BelongsToSchool;

    protected $table = 'vendor_settlement_lines';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = [];

    protected function casts(): array
    {
        return ['settlement_id' => 'integer', 'order_id' => 'integer', 'gross_cents' => 'integer', 'sales_net_cents' => 'integer', 'vat_cents' => 'integer'];
    }
}
