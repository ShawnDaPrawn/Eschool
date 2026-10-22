<?php

declare(strict_types=1);

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * A permission name, one catalogue for every school. Its roles are every
 * school's roles that hold it: Spatie builds its shared permission cache
 * from this relation, and a user's own roles (limited to the school) are
 * matched against it.
 */
class Permission extends SpatiePermission
{
    public function roles(): BelongsToMany
    {
        return parent::roles()->withoutGlobalScope(Role::SCHOOL_SCOPE);
    }
}
