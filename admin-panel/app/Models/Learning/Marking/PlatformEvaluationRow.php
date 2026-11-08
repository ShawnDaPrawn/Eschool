<?php

declare(strict_types=1);

namespace App\Models\Learning\Marking;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * A row of the marking accuracy gate (Action Plan 3.18): platform-owned
 * evaluation data with no school_id and no learner. Any create, change or
 * delete while a school is the current school (a school request, a queued
 * school job, HQ acting inside a school) is refused; HQ writes from
 * /saas/marking-accuracy, where no school is set.
 *
 * @mixin Model
 */
trait PlatformEvaluationRow
{
    public static function bootPlatformEvaluationRow(): void
    {
        $refuse = function (): void {
            abort_if(app(CurrentSchool::class)->isSet(), 403, 'Marking evaluation data is written only from HQ, never from a school.');
        };
        static::saving($refuse);
        static::deleting($refuse);
    }
}
