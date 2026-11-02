<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\RequestContext;
use App\Services\Platform\Saas\PlatformPackages;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Which schools a person belongs to, with which roles (the Build Bible,
 * section 3.1, Identity: one users row per human across schools, roles
 * granted per membership).
 *
 * Memberships follow role grants: giving a user a role at a school (Spatie's
 * assignRole and syncRoles, in that school's team) grants the membership,
 * and taking the role away ends it (see grantForRoles() and endForRoles(),
 * called from PlatformServiceProvider).
 *
 * A leaver membership (SchoolMembership::LEAVER, Action Plan 1.21) still
 * opens the school until the end of its leaver_until, read-only: active()
 * includes it, and readOnlyAt() tells the school context to refuse writes.
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
        $schoolColumns = ['name', 'slug', 'status', 'timezone', 'package_key', 'package_version', 'short_name', 'tagline', 'crest_path', 'theme_color', 'secondary_color', 'created_at', 'updated_at'];
        $withPackage = RequestContext::inHttpRequest();
        $query = SchoolMembership::query()
            ->join('schools', 'schools.id', '=', 'school_memberships.school_id')
            ->where('school_memberships.user_id', $user->getKey())
            ->whereIn('school_memberships.status', [SchoolMembership::ACTIVE, SchoolMembership::LEAVER])
            ->where('schools.status', School::ACTIVE)
            ->orderBy('school_memberships.school_id')
            ->orderBy('school_memberships.id')
            ->select(['school_memberships.*', ...array_map(fn ($column) => "schools.{$column} as school__{$column}", $schoolColumns)]);
        if ($withPackage) {
            // One scalar projection per school, without duplicating memberships.
            // Applied package versions are immutable; a locked fresh School still reads its own version.
            $query->selectSub(DB::table('platform_packages as pp')
                ->join('platform_package_modules as ppm', 'ppm.package_id', '=', 'pp.id')
                ->whereRaw('pp.package_key = COALESCE(schools.package_key, ?)', [PlatformPackages::LEGACY])
                ->whereRaw('ppm.version = COALESCE(schools.package_version, 1)')
                ->selectRaw('JSON_ARRAYAGG(ppm.product_module)'), 'school__package_products');
        }
        $rows = $query->get();

        $rows->each(function (SchoolMembership $membership) use ($schoolColumns, $withPackage) {
            $attributes = ['id' => $membership->school_id];
            foreach ($schoolColumns as $column) {
                $attributes[$column] = $membership->getAttribute("school__{$column}");
                $membership->offsetUnset("school__{$column}");
            }
            $school = (new School)->newFromBuilder($attributes);
            if ($withPackage) {
                $raw = $membership->getAttribute('school__package_products');
                $products = $raw === null ? [] : json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
                $school->withMembershipPackage((string) ($school->package_key ?? PlatformPackages::LEGACY), (int) ($school->package_version ?? 1), $products);
                $membership->offsetUnset('school__package_products');
            }
            $membership->setRelation('school', $school);
            $membership->syncOriginal();
        });

        // Each joined school owns its calendar. Filtering only this user's
        // hydrated rows avoids timezone-table dependencies or loading a school
        // for every membership on the sign-in path.
        return $this->active[$user->getKey()] = $rows->filter(function (SchoolMembership $membership) {
            $today = now($membership->school->timezone)->toDateString();
            if ($membership->status === SchoolMembership::LEAVER) {
                return $membership->leaver_until !== null && $membership->leaver_until->toDateString() >= $today;
            }

            return ($membership->starts_on === null || $membership->starts_on->toDateString() <= $today)
                && ($membership->ends_on === null || $membership->ends_on->toDateString() >= $today);
        })->values();
    }

    /**
     * True when everything the user holds at the school is a leaver
     * membership: they may read there, and every write is refused.
     */
    public function readOnlyAt(User $user, int $schoolId): bool
    {
        $here = $this->active($user)->where('school_id', $schoolId);

        return $here->isNotEmpty() && $here->every(fn (SchoolMembership $membership) => $membership->status === SchoolMembership::LEAVER);
    }

    /** True when the user's roles at the school are only learner and guardian ones. */
    public function familyOnlyAt(User $user, int $schoolId): bool
    {
        return $this->active($user)->where('school_id', $schoolId)->every(fn (SchoolMembership $membership) => in_array($membership->role, ['Student', 'Parent'], true));
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

    /** The school a user works in when they have not chosen one: their first current one, else their first. */
    public function defaultSchool(User $user): ?School
    {
        // A school where the person is a current member comes before one where they are only a leaver.
        $active = $this->active($user);

        return ($active->firstWhere('status', SchoolMembership::ACTIVE) ?? $active->first())?->school;
    }

    /**
     * Grant (or renew) a role's membership from today. Renewing a leaver's
     * membership makes them a current member again, from when they first
     * joined.
     */
    public function grant(User $user, School|int $school, string $role): SchoolMembership
    {
        $schoolId = $school instanceof School ? (int) $school->getKey() : $school;
        $membership = SchoolMembership::query()->firstOrNew([
            'user_id' => $user->getKey(),
            'school_id' => $schoolId,
            'role' => $role,
        ]);
        if (! $membership->exists || $membership->status !== SchoolMembership::ACTIVE || $membership->ends_on !== null) {
            $continues = $membership->exists && in_array($membership->status, [SchoolMembership::ACTIVE, SchoolMembership::LEAVER], true);
            $membership->fill([
                'status' => SchoolMembership::ACTIVE,
                'starts_on' => $continues ? $membership->starts_on : $this->schoolDate($school),
                'ends_on' => null,
                'leaver_until' => null,
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
