<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;

/**
 * Who may use the platform admin area (/saas): an active platform staff
 * identity (the exclusion identity every operator has) and, separately, an
 * unexpired platform admin grant, which only the operator console sets
 * (platform:admin-access). Neither a school's Super Admin role nor the
 * identity alone opens it, and neither grants any school permission in turn.
 * Checked against fresh rows on every request, so revoking either or
 * deactivating the account ends access at once, and it is refused while
 * acting inside a school (impersonation) or before a forced password change.
 */
class PlatformStaffAccess
{
    public function allowed(?User $user): bool
    {
        if ($user === null || app(CurrentSchool::class)->isImpersonating()) {
            return false;
        }

        return app(CurrentSchool::class)->acrossSchools(function () use ($user): bool {
            $fresh = User::query()->where('status', 1)->find($user->getKey());

            return $fresh !== null
                && ! $fresh->password_change_required
                && PlatformStaffIdentity::query()->where('user_id', $fresh->id)->where('active', true)->exists()
                && PlatformAdminGrant::query()->where('user_id', $fresh->id)->where('active', true)->where('expires_at', '>', now())->exists();
        });
    }

    /** The signed-in staff member, from a fresh row; 403 for anyone else. */
    public function actor(?User $user): User
    {
        abort_unless($this->allowed($user), 403);

        return $user;
    }
}
