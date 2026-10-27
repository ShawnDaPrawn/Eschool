<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array $snapshot
 * @property CarbonImmutable|null $sealed_at
 */
class PaymentAllocationRun extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['payment_id' => 'integer', 'ledger_entry_id' => 'integer', 'policy_revision' => 'integer', 'amount_cents' => 'integer', 'snapshot' => 'array', 'created_at' => UtcInstant::class, 'sealed_at' => UtcInstant::class];
    }
}
