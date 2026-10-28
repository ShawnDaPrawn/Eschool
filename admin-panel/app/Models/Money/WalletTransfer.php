<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $actor_id
 * @property string $operation_id
 * @property int $from_wallet_id
 * @property int $to_wallet_id
 * @property int $from_owner_id
 * @property int $to_owner_id
 * @property string $kind
 * @property string $currency
 * @property int $amount_cents
 * @property int $ledger_entry_id
 * @property int|null $policy_revision
 */
class WalletTransfer extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['actor_id' => 'integer', 'from_wallet_id' => 'integer', 'to_wallet_id' => 'integer', 'from_owner_id' => 'integer', 'to_owner_id' => 'integer', 'amount_cents' => 'integer', 'ledger_entry_id' => 'integer', 'policy_revision' => 'integer', 'created_at' => UtcInstant::class];
    }
}
