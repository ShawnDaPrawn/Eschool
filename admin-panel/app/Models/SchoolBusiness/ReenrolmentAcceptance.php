<?php

declare(strict_types=1);

namespace App\Models\SchoolBusiness;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Action Plan 5.10; engineering/specs/money/school-business-tools.md.
 *
 * @property int $id
 * @property int $contract_id
 * @property int $learner_id
 * @property int $guardian_user_id
 * @property int $version
 * @property string $text_hash
 * @property bool $fee_responsible
 * @property string $signed_name
 * @property string $ip_address
 * @property CarbonImmutable $accepted_at
 */
class ReenrolmentAcceptance extends Model
{
    use BelongsToSchool;

    protected $table = 'reenrolment_acceptances';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['ip_address'];

    protected function casts(): array
    {
        return ['contract_id' => 'integer', 'learner_id' => 'integer', 'guardian_user_id' => 'integer', 'version' => 'integer', 'fee_responsible' => 'boolean', 'accepted_at' => UtcInstant::class];
    }
}
