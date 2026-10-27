<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class BankStatementResolution extends Model
{
    use BelongsToSchool;

    protected $table = 'bank_statement_resolutions';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['details', 'reason', 'source_hash', 'payload_hash', 'digest', 'fingerprint'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['bank_statement_line_id' => 'integer', 'payment_id' => 'integer', 'reason' => 'encrypted', 'created_at' => UtcInstant::class];
    }
}
