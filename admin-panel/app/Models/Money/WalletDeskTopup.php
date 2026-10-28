<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $wallet_account_id
 * @property int $owner_user_id
 * @property int $amount_cents
 * @property string $currency
 * @property int $master_account_id
 * @property int $reserve_account_id
 * @property int $reserve_entry_id
 * @property int $requested_by
 * @property int $threshold_cents
 * @property bool $requires_second_approval
 * @property string $reason
 */
class WalletDeskTopup extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['reason'];

    protected function casts(): array
    {
        return ['wallet_account_id' => 'integer', 'owner_user_id' => 'integer', 'amount_cents' => 'integer', 'master_account_id' => 'integer', 'reserve_account_id' => 'integer', 'reserve_entry_id' => 'integer', 'requested_by' => 'integer', 'threshold_cents' => 'integer', 'requires_second_approval' => 'boolean', 'created_at' => UtcInstant::class, 'reason' => 'encrypted'];
    }
}
