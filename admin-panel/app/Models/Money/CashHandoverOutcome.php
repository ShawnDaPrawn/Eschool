<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class CashHandoverOutcome extends Model
{
    use BelongsToSchool;

    protected $table = 'cash_handover_outcomes';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['private_evidence', 'request_hash'];

    protected function casts(): array
    {
        return ['handover_id' => 'integer', 'actor_id' => 'integer',  'private_evidence' => 'encrypted', 'created_at' => UtcInstant::class];
    }
}
