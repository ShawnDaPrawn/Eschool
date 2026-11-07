<?php

declare(strict_types=1);

namespace App\Services\Platform\Groups;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolGroup;
use App\Models\Platform\SchoolGroupAdmin;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;

/**
 * Group-level roles (config/school_groups.php, roles): who may see a school
 * group's dashboard. A grant is read from fresh rows on every request, so a
 * revoked grant, a closed group or a disabled account stops at once. It
 * grants nothing inside a school: a group administrator who also works at
 * one of the schools reaches that school only through their own role there.
 * Platform staff are never group administrators; HQ sees groups in /saas.
 */
class SchoolGroupAccess
{
    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /** The groups this person administers now, by id, with the role's abilities. @return array<int, list<string>> */
    public function groups(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return $this->currentSchool->acrossSchools(function () use ($user): array {
            $fresh = User::query()->withoutGlobalScopes()->whereKey($user->id)->whereNull('deleted_at')->first(['id', 'status']);
            if ($fresh === null || (int) $fresh->status !== 1 || PlatformStaffIdentity::query()->where('user_id', $user->id)->where('active', true)->exists()) {
                return [];
            }
            $roles = (array) config('school_groups.roles', []);
            $groups = [];
            foreach (SchoolGroupAdmin::query()->where('user_id', $user->id)->whereNotNull('open_key')->whereNull('revoked_at')
                ->whereIn('school_group_id', SchoolGroup::query()->where('status', SchoolGroup::ACTIVE)->select('id'))->get(['school_group_id', 'role']) as $grant) {
                $groups[(int) $grant->school_group_id] = array_values(array_unique([...($groups[(int) $grant->school_group_id] ?? []), ...(array) ($roles[$grant->role]['abilities'] ?? [])]));
            }

            return $groups;
        });
    }

    public function can(?User $user, int $groupId, string $ability): bool
    {
        return in_array($ability, $this->groups($user)[$groupId] ?? [], true);
    }

    /** 403 unless the person holds the ability in this group now; an unknown group is 403 too, so nothing is learnt from the answer. */
    public function authorise(?User $user, int $groupId, string $ability = 'group.dashboard'): void
    {
        abort_unless($this->can($user, $groupId, $ability), 403);
        abort_if((bool) $user?->password_change_required, 403);
    }
}
