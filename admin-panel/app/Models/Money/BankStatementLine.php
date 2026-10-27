<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $received_on
 * @property array $details
 */
class BankStatementLine extends Model
{
    use BelongsToSchool;

    protected $table = 'bank_statement_lines';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['details', 'reason', 'source_hash', 'payload_hash', 'digest', 'fingerprint'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['amount_cents' => 'integer', 'received_on' => 'date:Y-m-d', 'details' => 'encrypted:array', 'created_at' => UtcInstant::class];
    }
}
