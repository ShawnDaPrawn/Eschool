<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $amount_cents
 * @property int $ledger_entry_id
 * @property int $master_account_id
 * @property int $asset_account_id
 * @property string $currency
 * @property string $method
 * @property string $source_reference
 * @property string $evidence
 */
class WalletFunding extends Model
{
    use BelongsToSchool;

    public $timestamps = false;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['evidence'];

    protected function casts(): array
    {
        return ['amount_cents' => 'integer', 'fee_cents' => 'integer', 'net_cents' => 'integer', 'ledger_entry_id' => 'integer', 'master_account_id' => 'integer', 'asset_account_id' => 'integer', 'created_at' => UtcInstant::class, 'evidence' => 'encrypted'];
    }
}
