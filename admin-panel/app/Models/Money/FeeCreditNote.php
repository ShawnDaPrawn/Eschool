<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class FeeCreditNote extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['fee_account_id' => 'integer', 'invoice_line_id' => 'integer', 'holder_user_id' => 'integer', 'amount_cents' => 'integer', 'unpaid_cents' => 'integer', 'released_cents' => 'integer', 'income_account_id' => 'integer', 'receivable_account_id' => 'integer', 'credit_account_id' => 'integer', 'ledger_entry_id' => 'integer', 'reason' => 'encrypted', 'snapshot' => 'array', 'created_at' => UtcInstant::class, 'sealed_at' => UtcInstant::class];
    }
}
