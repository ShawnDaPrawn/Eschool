<?php

declare(strict_types=1);

namespace App\Models\SchoolBusiness;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Action Plan 5.10; engineering/specs/money/school-business-tools.md.
 *
 * @property int $id
 * @property int $campaign_id
 * @property int $reference_number
 * @property string $donor_name
 * @property string|null $donor_email
 * @property int $amount_cents
 * @property string $currency
 * @property Carbon $pledged_on
 * @property int $recorded_by
 * @property CarbonImmutable $created_at
 */
class GivingPledge extends Model
{
    use BelongsToSchool;

    protected $table = 'giving_pledges';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['donor_name', 'donor_email'];

    protected function casts(): array
    {
        return ['campaign_id' => 'integer', 'reference_number' => 'integer', 'donor_name' => 'encrypted', 'donor_email' => 'encrypted', 'amount_cents' => 'integer', 'recorded_by' => 'integer', 'pledged_on' => 'date:Y-m-d', 'created_at' => UtcInstant::class];
    }
}
