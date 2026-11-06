<?php

declare(strict_types=1);

namespace App\Models\SchoolBusiness;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Action Plan 5.10; engineering/specs/money/school-business-tools.md.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $currency
 * @property int|null $goal_cents
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property bool $public_totals
 * @property string $state
 * @property int $income_account_id
 * @property int $revision
 * @property int $created_by
 */
class GivingCampaign extends Model
{
    use BelongsToSchool;

    protected $table = 'giving_campaigns';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['goal_cents' => 'integer', 'public_totals' => 'boolean', 'income_account_id' => 'integer', 'revision' => 'integer', 'created_by' => 'integer', 'starts_on' => 'date:Y-m-d', 'ends_on' => 'date:Y-m-d'];
    }
}
