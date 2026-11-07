<?php

declare(strict_types=1);

namespace App\Models\Reading;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * A row of the reading library catalogue (Action Plan 4.4). These tables have
 * no school_id and no school writes to them: any create, change or delete
 * while a school is the current school (a school request, a queued school
 * job, HQ acting inside a school) is refused. HQ writes from /saas/reading,
 * where no school is set.
 *
 * @mixin Model
 */
trait PlatformReadingRow
{
    public static function bootPlatformReadingRow(): void
    {
        $refuse = function (): void {
            abort_if(app(CurrentSchool::class)->isSet(), 403, 'The reading library catalogue is written only from HQ, never from a school.');
        };
        static::saving($refuse);
        static::deleting($refuse);
    }
}
