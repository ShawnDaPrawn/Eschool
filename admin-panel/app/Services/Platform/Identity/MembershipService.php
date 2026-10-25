<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Which schools a person belongs to, with which roles (the Build Bible,
 * section 3.1, Identity: one users row per human across schools, roles
 * granted per membership).
 *
 * Memberships follow role grants: giving a user a role at a school (Spatie's
 * assignRole and syncRoles, in that school's team) grants the membership,
 * and taking the role away ends it (see grantForRoles() and endForRoles(),
 * called from PlatformServiceProvider).
 */
class MembershipService
{
    /** @var array<int, Collection<int, SchoolMembership>> active memberships by user id, for this request */
    private array $active = [];

    /**
     * The user's active memberships, each with its school, ordered by school.
     *
     * @return Collection<int, SchoolMembership>
     */
    public function active(User $user): Collection
    {
        if (isset($this->active[$user->getKey()])) {
            return $this->active[$user->getKey()];
        }

        // One query: the memberships with their schools joined (it runs on
        // every signed-in request).
        $schoolColumns = ['name', 'slug', 'status', 'timezone', 'package_key', 'short_name', 'tagline', 'crest_path', 'theme_color', 'secondary_color', 'created_at', 'updated_at'];
        $rows = SchoolMembership::query()
            ->join('schools', 'schools.id', '=', 'school_memberships.school_id')
            ->where('school_memberships.user_id', $user->getKey())
            ->where('school_memberships.status', SchoolMembership::ACTIVE)
            ->where('schools.status', School::ACTIVE)
            ->orderBy('school_memberships.school_id')
            ->orderBy('school_memberships.id')
            ->select(['school_memberships.*', ...array_map(fn ($column) => "schools.{$column} as school__{$column}", $schoolColumns)])
            ->get();

        $rows->each(function (SchoolMembership $membership) use ($schoolColumns) {
            $attributes = ['id' => $membership->school_id];
            foreach ($schoolColumns as $column) {
                $attributes[$column] = $membership->getAttribute("school__{$column}");
                $membership->offsetUnset("school__{$column}");
            }
            $membership->setRelation('school', (new School)->newFromBuilder($attributes));
            $membership->syncOriginal();
        });

        // Each joined school owns its calendar. Filtering only this user's
        // hydrated rows avoids timezone-table dependencies or loading a school
        // for every membership on the sign-in path.
        return $this->active[$user->getKey()] = $rows->filter(function (SchoolMembership $membership) {
            $today = now($membership->school->timezone)->toDateString();

            return ($membership->starts_on === null || $membership->starts_on->toDateString() <= $today)
                && ($membership->ends_on === null || $membership->ends_on->toDateString() >= $today);
        })->values();
    }

    /**
     * The schools the user is an active member of, each with the user's roles
     * there.
     *
     * @return list<array{school: School, roles: list<string>}>
     */
    public function schools(User $user): array
    {
        return $this->active($user)
            ->groupBy('school_id')
            ->map(fn (Collection $memberships) => [
                'school' => $memberships->first()->school,
                'roles' => $memberships->pluck('role')->unique()->values()->all(),
            ])
            ->values()
            ->all();
    }

    public function isActiveMember(User $user, int $schoolId): bool
    {
        return $this->active($user)->contains('school_id', $schoolId);
    }

    /** The school a user works in when they have not chosen one: their first. */
    public function defaultSchool(User $user): ?School
    {
        return $this->active($user)->first()?->school;
    }

    /** Grant (or renew) a role's membership from today. */
    public function grant(User $user, School|int $school, string $role): SchoolMembership
    {
        $schoolId = $school instanceof School ? (int) $school->getKey() : $school;
        $membership = SchoolMembership::query()->firstOrNew([
            'user_id' => $user->getKey(),
            'school_id' => $schoolId,
            'role' => $role,
        ]);
        if (! $membership->exists || $membership->status !== SchoolMembership::ACTIVE || $membership->ends_on !== null) {
            $membership->fill([
                'status' => SchoolMembership::ACTIVE,
                'starts_on' => $membership->exists && $membership->status === SchoolMembership::ACTIVE ? $membership->starts_on : $this->schoolDate($school),
                'ends_on' => null,
            ])->save();
        }
        $this->forget($user);

        return $membership;
    }

    /** End a role's membership today; the row stays as history. */
    public function end(User $user, School|int $school, string $role): void
    {
        $schoolId = $school instanceof School ? (int) $school->getKey() : $school;
        SchoolMembership::query()
            ->where(['user_id' => $user->getKey(), 'school_id' => $schoolId, 'role' => $role])
            ->where('status', '!=', SchoolMembership::ENDED)
            ->get()
            ->each(fn (SchoolMembership $membership) => $membership->fill([
                'status' => SchoolMembership::ENDED,
                'ends_on' => $this->schoolDate($school),
            ])->save());
        $this->forget($user);
    }

    private function schoolDate(School|int $school): string
    {
        $target = $school instanceof School ? $school : School::findOrFail($school);

        return now($target->timezone)->toDateString();
    }

    /** Forget what this request has read about the user's memberships. */
    public function forget(User $user): void
    {
        unset($this->active[$user->getKey()]);
    }
}
