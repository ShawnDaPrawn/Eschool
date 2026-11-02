<?php

declare(strict_types=1);

namespace App\Models\Library;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * A row of the platform content library (Action Plan 3.3). Library tables have
 * no school_id, and no school writes to them: any create, change or delete
 * while a school is the current school (a school request, a queued school job,
 * HQ acting inside a school) is refused, so school data cannot flow into the
 * library by any path. HQ writes from /saas, where no school is set.
 *
 * @mixin Model
 */
trait PlatformLibraryRow
{
    public static function bootPlatformLibraryRow(): void
    {
        $refuse = function (): void {
            abort_if(app(CurrentSchool::class)->isSet(), 403, 'The platform library is written only from HQ, never from a school.');
        };
        static::saving($refuse);
        static::deleting($refuse);
    }
}
