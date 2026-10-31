<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * One page a Navigate Ed staff member viewed in a support session: when, the
 * route name and the record id from the route. Never the page's content or
 * its query string. engineering/specs/platform/support-access.md.
 *
 * @property int $id
 * @property int $school_id
 * @property int $grant_id
 * @property int $user_id
 * @property string $route_name
 * @property string|null $record_type
 * @property int|null $record_id
 * @property list<int>|null $listed
 * @property CarbonImmutable $viewed_at
 */
class SupportAccessView extends Model
{
    use BelongsToSchool;

    public $timestamps = false;

    protected $table = 'support_access_views';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['grant_id' => 'integer', 'user_id' => 'integer', 'record_id' => 'integer', 'listed' => 'array', 'viewed_at' => UtcInstant::class];
    }
}
