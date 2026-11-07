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
 * @property int|null $pledge_id
 * @property int $receipt_number
 * @property string $donor_name
 * @property string|null $donor_email
 * @property string $method
 * @property string $provider
 * @property string $collected_by
 * @property string $source_reference
 * @property int $amount_cents
 * @property string $currency
 * @property Carbon $received_on
 * @property int $asset_account_id
 * @property int $income_account_id
 * @property int $ledger_entry_id
 * @property int $recorded_by
 * @property CarbonImmutable $created_at
 */
class GivingDonation extends Model
{
    use BelongsToSchool;

    protected $table = 'giving_donations';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['donor_name', 'donor_email'];

    protected function casts(): array
    {
        return ['campaign_id' => 'integer', 'pledge_id' => 'integer', 'receipt_number' => 'integer', 'donor_name' => 'encrypted', 'donor_email' => 'encrypted', 'amount_cents' => 'integer', 'asset_account_id' => 'integer', 'income_account_id' => 'integer', 'ledger_entry_id' => 'integer', 'recorded_by' => 'integer', 'received_on' => 'date:Y-m-d', 'created_at' => UtcInstant::class];
    }
}
