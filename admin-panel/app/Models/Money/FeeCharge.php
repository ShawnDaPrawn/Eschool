<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $kind
 * @property array<string,mixed> $snapshot
 * @property int $vat_cents
 * @property int $net_cents
 * @property int|null $income_account_id
 * @property int|null $vat_account_id
 */
class FeeCharge extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['vat_cents' => 'integer', 'net_cents' => 'integer', 'income_account_id' => 'integer', 'vat_account_id' => 'integer', 'fee_item_id' => 'integer', 'fee_account_id' => 'integer', 'session_year_id' => 'integer', 'learner_id' => 'integer', 'bill_run_id' => 'integer', 'item_revision' => 'integer', 'amount_cents' => 'integer', 'snapshot' => 'array', 'source_reference' => 'encrypted', 'created_at' => UtcInstant::class, 'sealed_at' => UtcInstant::class];
    }
}
