<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** @property int $vat_rate_bp */
class FeeItem extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['created_at' => UtcInstant::class, 'updated_at' => UtcInstant::class, 'vat_rate_bp' => 'integer', 'amount_cents' => 'integer', 'revision' => 'integer'];
    }
}
