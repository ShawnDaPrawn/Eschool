<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * A role at one school (Spatie permission in teams mode, the team being the
 * school; config/permission.php). Queries see the current school's roles,
 * so Role::where('name', 'Parent') and Role::find($id) never return another
 * school's; a new role belongs to the current school.
 *
 * Permission::roles() reads every school's (Spatie's permission cache is
 * built from it and shared by all schools).
 *
 * @property int $id
 * @property int|null $school_id
 * @property string $name
 * @property string $guard_name
 * @property int|null $custom_role
 */
class Role extends SpatieRole
{
    public const SCHOOL_SCOPE = 'school';

    /** v1 answers that include roles keep their Phase 0 shape. */
    protected $hidden = ['school_id'];

    /** Every school's roles: for platform code only. */
    public static function acrossSchoolsQuery(): Builder
    {
        return static::withoutGlobalScope(self::SCHOOL_SCOPE);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(self::SCHOOL_SCOPE, function (Builder $query) {
            $schoolId = app(CurrentSchool::class)->scopeId();
            if ($schoolId !== null) {
                $query->where(fn (Builder $q) => $q->where('roles.school_id', $schoolId)->orWhereNull('roles.school_id'));
            }
        });

        static::creating(function (Role $role) {
            $currentSchool = app(CurrentSchool::class);
            if ($role->school_id === null && ! ($currentSchool->isMigrating() && $currentSchool->idOrNull() === null)) {
                $role->school_id = $currentSchool->id();
            }
        });
    }
}
