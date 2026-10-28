<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $revision
 * @property bool $cash_allowed
 * @property bool $eft_allowed
 */
class WalletFundingPolicy extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'cash_allowed' => 'boolean', 'eft_allowed' => 'boolean'];
    }
}
