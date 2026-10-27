<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property array|null $applies_to
 * @property array|null $payment_options
 */
class FeePlan extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['applies_to' => 'array', 'payment_options' => 'array', 'amount_cents' => 'integer', 'target_id' => 'integer', 'session_year_id' => 'integer', 'revision' => 'integer'];
    }
}
