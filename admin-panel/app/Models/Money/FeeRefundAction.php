<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** @property int $fee_refund_id
 * @property int $amount_cents
 * @property int $ledger_entry_id
 * @property int $pending_account_id
 * @property int $counter_account_id
 */
class FeeRefundAction extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['fee_refund_id' => 'integer', 'actor_id' => 'integer', 'amount_cents' => 'integer', 'ledger_entry_id' => 'integer', 'pending_account_id' => 'integer', 'counter_account_id' => 'integer', 'private_evidence' => 'encrypted', 'created_at' => UtcInstant::class];
    }
}
