<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class FeeCharge extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['fee_item_id' => 'integer', 'fee_account_id' => 'integer', 'session_year_id' => 'integer', 'learner_id' => 'integer', 'bill_run_id' => 'integer', 'item_revision' => 'integer', 'amount_cents' => 'integer', 'snapshot' => 'array', 'source_reference' => 'encrypted', 'created_at' => UtcInstant::class, 'sealed_at' => UtcInstant::class];
    }
}
