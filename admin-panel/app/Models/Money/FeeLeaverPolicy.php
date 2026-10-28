<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** @property string $prior_policyless_basis
 * @property string $prior_reductions
 * @property bool $independent_approval
 * @property int $revision
 */
class FeeLeaverPolicy extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'independent_approval' => 'boolean'];
    }
}
