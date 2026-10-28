<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $actor_id
 * @property int $holder_user_id
 * @property int $wallet_account_id
 * @property int $pending_account_id
 * @property int $reserved_entry_id
 * @property int $amount_cents
 * @property int $policy_revision
 * @property int|null $slot_id
 * @property string|null $private_bank_instructions
 * @property string|null $private_reason
 * @property CarbonImmutable $created_at
 * @property string $currency
 * @property string $method
 * @property string $operation_id
 */
class WalletWithdrawal extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['private_bank_instructions', 'private_reason'];

    protected function casts(): array
    {
        return ['actor_id' => 'integer', 'holder_user_id' => 'integer', 'wallet_account_id' => 'integer', 'pending_account_id' => 'integer', 'reserved_entry_id' => 'integer', 'amount_cents' => 'integer', 'policy_revision' => 'integer', 'slot_id' => 'integer', 'private_bank_instructions' => 'encrypted', 'private_reason' => 'encrypted', 'created_at' => UtcInstant::class];
    }
}
