<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** @property array $snapshot
 * @property array $charge_ids
 * @property int $amount_cents
 */
class FeeItemBatch extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'charge_ids' => 'array', 'amount_cents' => 'integer', 'created_at' => UtcInstant::class];
    }
}
