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
 * @property int $application_id
 * @property int $actor_id
 * @property string $action
 * @property string|null $reasons
 * @property string|null $award_kind
 * @property int|null $award_value
 * @property CarbonImmutable $created_at
 */
class BursaryEvent extends Model
{
    use BelongsToSchool;

    protected $table = 'bursary_events';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['reasons'];

    protected function casts(): array
    {
        return ['application_id' => 'integer', 'actor_id' => 'integer', 'reasons' => 'encrypted', 'award_value' => 'integer', 'created_at' => UtcInstant::class];
    }
}
