<?php

declare(strict_types=1);

namespace App\Services\Pastoral;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;

/** Call before loading a restricted record; its domain policy must then check assignment. */
class SensitiveRecordsAccess
{
    public const PERMISSION = 'pastoral-sensitive-access';

    public function actor(User $actor, string $permission = self::PERMISSION): User
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating(), 403);
        // A platform operator cannot gain access by also being given a school role.
        abort_if(PlatformStaffIdentity::query()->where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($actor);
        abort_if($fresh->password_change_required, 403, 'Complete your password change before opening restricted records.');
        abort_unless(array_intersect($policy->roles($fresh), [SchoolRoles::COUNSELLOR, SchoolRoles::SAFEGUARDING]) !== [], 403);
        abort_unless($policy->permission($fresh, $permission), 403);

        return $fresh;
    }
}
