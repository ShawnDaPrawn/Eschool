<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $claim_id
 * @property int $amount_cents
 * @property int $fee_cents
 * @property int $net_cents
 * @property int $asset_account_id
 * @property int $ledger_entry_id
 * @property int $policy_revision
 * @property int $verified_by
 * @property int|null $cash_operator_id
 * @property string $currency
 * @property string $method
 * @property string $source_reference
 * @property string $received_on
 * @property string $operation_id
 * @property string $evidence
 */
class WalletParentDeposit extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['evidence'];

    protected function casts(): array
    {
        return ['claim_id' => 'integer', 'amount_cents' => 'integer', 'fee_cents' => 'integer', 'net_cents' => 'integer', 'asset_account_id' => 'integer', 'ledger_entry_id' => 'integer', 'policy_revision' => 'integer', 'verified_by' => 'integer', 'cash_operator_id' => 'integer', 'evidence' => 'encrypted', 'created_at' => UtcInstant::class];
    }
}
