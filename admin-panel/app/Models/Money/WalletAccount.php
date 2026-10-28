<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $user_id
 * @property int $ledger_account_id
 * @property Carbon $created_at
 * @property string $currency
 */
class WalletAccount extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'ledger_account_id' => 'integer', 'created_at' => UtcInstant::class];
    }
}
