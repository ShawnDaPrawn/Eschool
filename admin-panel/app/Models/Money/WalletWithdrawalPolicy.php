<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $cash_threshold_cents
 * @property bool $cash_at_boundary
 * @property bool $accepting_requests
 * @property int $revision
 * @property int $changed_by
 * @property string $currency
 */
class WalletWithdrawalPolicy extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = true;

    protected $hidden = [];

    protected function casts(): array
    {
        return ['cash_threshold_cents' => 'integer', 'cash_at_boundary' => 'boolean', 'accepting_requests' => 'boolean', 'revision' => 'integer', 'changed_by' => 'integer'];
    }
}
