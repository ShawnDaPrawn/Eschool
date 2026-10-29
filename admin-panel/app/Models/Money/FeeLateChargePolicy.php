<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property bool $enabled
 * @property int $revision
 * @property int $session_year_id
 * @property int|null $flat_cents
 * @property int|null $rate_bp
 * @property int|null $cap_cents
 * @property int|null $grace_days
 * @property int|null $due_days
 * @property int|null $vat_rate_bp
 * @property string $currency
 * @property string|null $scope
 * @property string|null $basis
 * @property string|null $tax_treatment
 */
class FeeLateChargePolicy extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'revision' => 'integer', 'session_year_id' => 'integer', 'flat_cents' => 'integer', 'rate_bp' => 'integer', 'cap_cents' => 'integer', 'grace_days' => 'integer', 'due_days' => 'integer', 'vat_rate_bp' => 'integer'];
    }
}
