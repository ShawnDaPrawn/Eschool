<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $fee_account_id
 * @property int $original_invoice_line_id
 * @property int $holder_user_id
 * @property int $policy_revision_id
 * @property int $replacement_charge_id
 * @property int $basis_cents
 * @property int $amount_cents
 * @property int $vat_cents
 * @property string $currency
 * @property string $reason
 */
class FeeLateCharge extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['fee_account_id' => 'integer', 'original_invoice_line_id' => 'integer', 'holder_user_id' => 'integer', 'policy_revision_id' => 'integer', 'replacement_charge_id' => 'integer', 'basis_cents' => 'integer', 'amount_cents' => 'integer', 'vat_cents' => 'integer', 'actor_id' => 'integer', 'reason' => 'encrypted', 'created_at' => UtcInstant::class];
    }
}
