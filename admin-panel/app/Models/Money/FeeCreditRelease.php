<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class FeeCreditRelease extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['credit_note_id' => 'integer', 'allocation_id' => 'integer', 'payment_id' => 'integer', 'amount_cents' => 'integer', 'created_at' => UtcInstant::class];
    }
}
