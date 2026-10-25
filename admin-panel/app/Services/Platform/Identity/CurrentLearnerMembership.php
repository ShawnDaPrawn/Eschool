<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;

/** Current communications/group access only; historical family records retain their own policy. */
class CurrentLearnerMembership
{
    public function users(): Builder
    {
        return User::where('status', 1)->whereIn('id', SchoolMembership::active()
            ->where('school_id', app(CurrentSchool::class)->id())->where('role', 'Student')->select('user_id'));
    }
}
