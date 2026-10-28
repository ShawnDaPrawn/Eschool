<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $actor_id
 * @property int $wallet_account_id
 * @property int $requested_cents
 * @property int $policy_revision
 * @property string $currency
 * @property string $method
 * @property string $source_reference
 * @property string $paid_on
 * @property string $operation_id
 * @property string $evidence
 */
class WalletDepositClaim extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['evidence'];

    protected function casts(): array
    {
        return ['actor_id' => 'integer', 'wallet_account_id' => 'integer', 'requested_cents' => 'integer', 'policy_revision' => 'integer', 'evidence' => 'encrypted', 'created_at' => UtcInstant::class];
    }
}
