<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property 'annual'|'fee_item' $kind
 *
 * Immutable billing snapshot with explicit calendar and UTC attributes.
 * @property array{year_id: int, year_start: string, year_end: string, as_of: string, due_on: string, tax_treatment: string, rows: list<array>, totals: array<string, int>, exceptions: array<string, int>, learners: int, billable: int, skipped: int, account_count: int} $snapshot
 * @property Carbon $as_of
 * @property Carbon $due_on
 * @property CarbonImmutable|null $approved_at
 * @property CarbonImmutable|null $posted_at
 */
class BillRun extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['updated_at' => UtcInstant::class, 'created_at' => UtcInstant::class, 'session_year_id' => 'integer', 'revision' => 'integer', 'snapshot' => 'array', 'as_of' => 'date:Y-m-d', 'due_on' => 'date:Y-m-d', 'approved_at' => UtcInstant::class, 'posted_at' => UtcInstant::class];
    }
}
