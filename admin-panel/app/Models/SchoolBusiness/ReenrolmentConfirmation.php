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
 * @property int $session_year_id
 * @property CarbonImmutable $confirmed_at
 */
class ReenrolmentConfirmation extends Model
{
    use BelongsToSchool;

    protected $table = 'reenrolment_confirmations';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['contract_id' => 'integer', 'learner_id' => 'integer', 'session_year_id' => 'integer', 'confirmed_at' => UtcInstant::class];
    }
}
