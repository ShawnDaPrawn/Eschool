<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** @property int $payment_id
 * @property int $fee_account_id
 * @property int $holder_user_id
 * @property int|null $pinned_learner_id
 * @property int $fee_refund_policy_id
 * @property int $prepared_by
 * @property string $currency
 * @property string $method
 * @property string $fingerprint
 * @property string $private_explanation
 * @property int $amount_cents
 * @property array $snapshot
 */
class FeeRefund extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['payment_id' => 'integer', 'fee_account_id' => 'integer', 'holder_user_id' => 'integer', 'fee_refund_policy_id' => 'integer', 'pinned_learner_id' => 'integer', 'amount_cents' => 'integer', 'snapshot' => 'array', 'private_explanation' => 'encrypted', 'prepared_by' => 'integer', 'created_at' => UtcInstant::class];
    }
}
