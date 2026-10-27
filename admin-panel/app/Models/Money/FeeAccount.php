<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class FeeAccount extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['updated_at' => UtcInstant::class, 'created_at' => UtcInstant::class, 'active' => 'boolean', 'revision' => 'integer', 'ledger_account_id' => 'integer'];
    }
}
