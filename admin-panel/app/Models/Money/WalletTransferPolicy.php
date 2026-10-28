<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $child_returns
 * @property int $revision
 */
class WalletTransferPolicy extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['revision' => 'integer'];
    }
}
