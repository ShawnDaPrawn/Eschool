<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property list<array{user_id: int, name: string, share_bp: int, amount_cents: int}> $holder_shares
 * @property Carbon $period_start
 * @property Carbon $period_end
 */
class InvoiceLine extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['invoice_id' => 'integer', 'learner_id' => 'integer', 'session_year_id' => 'integer', 'fee_plan_id' => 'integer', 'plan_revision' => 'integer', 'amount_cents' => 'integer', 'vat_cents' => 'integer', 'holder_shares' => 'array', 'period_start' => 'date:Y-m-d', 'period_end' => 'date:Y-m-d'];
    }
}
