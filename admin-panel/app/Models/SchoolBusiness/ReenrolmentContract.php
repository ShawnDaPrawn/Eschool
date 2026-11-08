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
 * @property int $session_year_id
 * @property int $version
 * @property string $title
 * @property string $body
 * @property string $text_hash
 * @property string $acceptance_rule
 * @property Carbon $respond_by
 * @property string $state
 * @property int $revision
 * @property int $published_by
 * @property CarbonImmutable $published_at
 * @property CarbonImmutable|null $closed_at
 */
class ReenrolmentContract extends Model
{
    use BelongsToSchool;

    protected $table = 'reenrolment_contracts';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['session_year_id' => 'integer', 'version' => 'integer', 'revision' => 'integer', 'published_by' => 'integer', 'published_at' => UtcInstant::class, 'closed_at' => UtcInstant::class, 'respond_by' => 'date:Y-m-d'];
    }
}
