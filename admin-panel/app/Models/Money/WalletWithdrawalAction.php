<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $withdrawal_id
 * @property int $actor_id
 * @property int|null $ledger_entry_id
 * @property int|null $counter_account_id
 * @property int|null $cash_operator_id
 * @property string|null $private_evidence
 * @property CarbonImmutable $created_at
 * @property string|null $paid_on
 * @property string $operation_id
 * @property string $status
 * @property string|null $reference_hash
 */
class WalletWithdrawalAction extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['private_evidence', 'reference_hash'];

    protected function casts(): array
    {
        return ['withdrawal_id' => 'integer', 'actor_id' => 'integer', 'ledger_entry_id' => 'integer', 'counter_account_id' => 'integer', 'cash_operator_id' => 'integer', 'private_evidence' => 'encrypted', 'created_at' => UtcInstant::class];
    }
}
