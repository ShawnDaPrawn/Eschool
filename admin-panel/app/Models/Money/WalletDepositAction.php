<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $claim_id
 * @property int $actor_id
 * @property int|null $deposit_id
 * @property string $status
 * @property string $reason_code
 * @property string|null $private_note
 */
class WalletDepositAction extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['private_note'];

    protected function casts(): array
    {
        return ['claim_id' => 'integer', 'actor_id' => 'integer', 'deposit_id' => 'integer', 'private_note' => 'encrypted', 'created_at' => UtcInstant::class];
    }
}
