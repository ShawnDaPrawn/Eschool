<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** @property bool $cash_allowed
 * @property bool $eft_allowed
 * @property bool $independent_approval
 * @property int $revision
 */
class FeeRefundPolicy extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = true;

    protected function casts(): array
    {
        return ['cash_allowed' => 'boolean', 'eft_allowed' => 'boolean', 'independent_approval' => 'boolean', 'revision' => 'integer'];
    }
}
