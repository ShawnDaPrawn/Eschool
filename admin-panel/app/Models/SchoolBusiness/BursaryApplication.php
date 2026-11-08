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
 * @property string $public_id
 * @property int $learner_id
 * @property int $guardian_user_id
 * @property int $session_year_id
 * @property string $state
 * @property array<string, mixed>|null $household
 * @property int $revision
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $decided_at
 * @property string|null $award_kind
 * @property int|null $award_value
 * @property string|null $currency
 * @property int|null $fee_discount_id
 * @property CarbonImmutable|null $erased_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable $created_at
 */
class BursaryApplication extends Model
{
    use BelongsToSchool;

    protected $table = 'bursary_applications';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['household'];

    protected function casts(): array
    {
        return ['learner_id' => 'integer', 'guardian_user_id' => 'integer', 'session_year_id' => 'integer', 'household' => 'encrypted:array', 'revision' => 'integer', 'award_value' => 'integer', 'fee_discount_id' => 'integer', 'submitted_at' => UtcInstant::class, 'decided_at' => UtcInstant::class, 'erased_at' => UtcInstant::class, 'updated_at' => UtcInstant::class, 'created_at' => UtcInstant::class];
    }
}
