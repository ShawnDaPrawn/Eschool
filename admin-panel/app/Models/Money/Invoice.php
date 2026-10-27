<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property list<array{user_id: int, name: string, share_bp: int, total_cents: int}> $holders
 * @property Carbon $issued_on
 * @property Carbon $due_on
 * @property CarbonImmutable|null $sealed_at
 */
class Invoice extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['created_at' => UtcInstant::class, 'fee_account_id' => 'integer', 'bill_run_id' => 'integer', 'session_year_id' => 'integer', 'ledger_entry_id' => 'integer', 'total_cents' => 'integer', 'holders' => 'array', 'issued_on' => 'date:Y-m-d', 'due_on' => 'date:Y-m-d', 'sealed_at' => UtcInstant::class];
    }
}
