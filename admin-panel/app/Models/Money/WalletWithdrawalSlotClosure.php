<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $slot_id
 * @property int $actor_id
 * @property string $private_reason
 * @property CarbonImmutable $created_at
 */
class WalletWithdrawalSlotClosure extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['private_reason'];

    protected function casts(): array
    {
        return ['slot_id' => 'integer', 'actor_id' => 'integer', 'private_reason' => 'encrypted', 'created_at' => UtcInstant::class];
    }
}
