<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $wallet_account_id
 * @property int $daily_spend_cents
 * @property bool $all_categories
 * @property array $categories
 * @property int $revision
 * @property int $changed_by
 */
class WalletLimit extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = true;

    protected function casts(): array
    {
        return ['wallet_account_id' => 'integer', 'daily_spend_cents' => 'integer', 'all_categories' => 'boolean', 'categories' => 'array', 'revision' => 'integer', 'changed_by' => 'integer'];
    }
}
