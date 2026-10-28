<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $daily_spend_cents
 * @property bool $child_limits_required
 * @property int $revision
 * @property int $changed_by
 */
class WalletSetting extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = true;

    protected function casts(): array
    {
        return ['daily_spend_cents' => 'integer', 'child_limits_required' => 'boolean', 'revision' => 'integer', 'changed_by' => 'integer'];
    }
}
