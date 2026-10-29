<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class CashHandover extends Model
{
    use BelongsToSchool;

    protected $table = 'cash_handovers';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['private_evidence', 'request_hash'];

    protected function casts(): array
    {
        return ['sender_id' => 'integer', 'recipient_id' => 'integer', 'amount_cents' => 'integer', 'available_before_cents' => 'integer',  'private_evidence' => 'encrypted', 'created_at' => UtcInstant::class];
    }
}
