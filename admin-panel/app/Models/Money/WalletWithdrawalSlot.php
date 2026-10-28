<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $capacity
 * @property int $created_by
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property CarbonImmutable $created_at
 * @property string $operation_id
 */
class WalletWithdrawalSlot extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = [];

    protected function casts(): array
    {
        return ['capacity' => 'integer', 'created_by' => 'integer', 'starts_at' => UtcInstant::class, 'ends_at' => UtcInstant::class, 'created_at' => UtcInstant::class];
    }
}
