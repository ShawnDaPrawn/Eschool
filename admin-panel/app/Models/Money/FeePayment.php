<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $received_on
 */
class FeePayment extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $table = 'payments';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['fee_account_id' => 'integer', 'holder_user_id' => 'integer', 'pinned_learner_id' => 'integer', 'debit_account_id' => 'integer', 'credit_account_id' => 'integer', 'ledger_entry_id' => 'integer', 'amount_cents' => 'integer', 'fee_cents' => 'integer', 'net_cents' => 'integer', 'received_on' => 'date:Y-m-d', 'created_at' => UtcInstant::class];
    }
}
