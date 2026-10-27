<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['allocation_run_id' => 'integer', 'payment_id' => 'integer', 'invoice_line_id' => 'integer', 'amount_cents' => 'integer', 'created_at' => UtcInstant::class];
    }
}
