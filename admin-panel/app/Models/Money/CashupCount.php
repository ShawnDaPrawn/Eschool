<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class CashupCount extends Model
{
    use BelongsToSchool;

    protected $table = 'cashup_counts';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['private_evidence', 'private_reason', 'request_hash'];

    protected function casts(): array
    {
        return ['operator_id' => 'integer', 'revision' => 'integer', 'opening_cents' => 'integer', 'receipts_cents' => 'integer', 'payouts_cents' => 'integer', 'closing_cents' => 'integer', 'counted_cents' => 'integer', 'variance_cents' => 'integer', 'private_evidence' => 'encrypted', 'private_reason' => 'encrypted', 'created_at' => UtcInstant::class];
    }
}
