<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $actor_id
 * @property array $receipt
 * @property Carbon $created_at
 */
class WalletOperation extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['actor_id' => 'integer', 'receipt' => 'array', 'created_at' => UtcInstant::class];
    }
}
