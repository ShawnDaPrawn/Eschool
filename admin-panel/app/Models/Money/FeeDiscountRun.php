<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** @property int $fee_discount_id
 * @property int $fee_account_id
 * @property int $learner_id
 * @property int $session_year_id
 * @property array $snapshot
 * @property array $credit_note_ids
 * @property int $amount_cents
 */
class FeeDiscountRun extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['fee_discount_id' => 'integer', 'fee_account_id' => 'integer', 'learner_id' => 'integer', 'session_year_id' => 'integer', 'revision' => 'integer', 'snapshot' => 'array', 'credit_note_ids' => 'array', 'amount_cents' => 'integer', 'private_explanation' => 'encrypted', 'prepared_by' => 'integer', 'approved_by' => 'integer', 'sealed_at' => UtcInstant::class];
    }
}
