<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * Immutable link between original unpaid-only credit and equal replacement debt.
 *
 * @property int $fee_account_id
 * @property int $original_invoice_line_id
 * @property int $original_holder_id
 * @property int $new_holder_id
 * @property int $credit_note_id
 * @property int $replacement_charge_id
 * @property int $amount_cents
 * @property int $vat_cents
 * @property int $actor_id
 * @property string $currency
 */
class FeePayerCorrection extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['fee_account_id' => 'integer', 'original_invoice_line_id' => 'integer', 'original_holder_id' => 'integer', 'new_holder_id' => 'integer', 'credit_note_id' => 'integer', 'replacement_charge_id' => 'integer', 'actor_id' => 'integer', 'amount_cents' => 'integer', 'vat_cents' => 'integer', 'created_at' => UtcInstant::class];
    }
}
