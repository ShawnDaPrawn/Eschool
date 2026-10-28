<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $currency
 * @property int $revision
 * @property int $threshold_cents
 */
class WalletDeskPolicy extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['threshold_cents' => 'integer', 'revision' => 'integer'];
    }
}
