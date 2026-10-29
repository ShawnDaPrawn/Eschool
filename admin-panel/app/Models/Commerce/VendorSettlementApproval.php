<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class VendorSettlementApproval extends Model
{
    use BelongsToSchool;

    protected $table = 'vendor_settlement_approvals';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = [];

    protected function casts(): array
    {
        return ['settlement_id' => 'integer', 'actor_id' => 'integer', 'created_at' => UtcInstant::class];
    }
}
