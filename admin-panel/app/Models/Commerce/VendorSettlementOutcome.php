<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable $created_at
 * @property int|null $ledger_entry_id
 * @property int|null $counter_account_id
 * @property int|null $vat_account_id
 * @property array $private_evidence
 */
class VendorSettlementOutcome extends Model
{
    use BelongsToSchool;

    protected $table = 'vendor_settlement_outcomes';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['private_evidence', 'reference_hash'];

    protected function casts(): array
    {
        return ['settlement_id' => 'integer', 'actor_id' => 'integer', 'ledger_entry_id' => 'integer', 'counter_account_id' => 'integer', 'vat_account_id' => 'integer', 'cash_operator_id' => 'integer', 'private_evidence' => 'encrypted:array', 'created_at' => UtcInstant::class];
    }
}
