<?php

declare(strict_types=1);

namespace App\Services\Activities;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityStaff;
use App\Models\Parents;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CommunityAuthority
{
    public function today(): string
    {
        return app(AcademicAuthority::class)->today();
    }

    public function actor(User $actor, bool $write = false): User
    {
        abort_unless(app(CurrentSchool::class)->school()?->status === 'active', 404);
        app(ModuleGate::class)->forget();
        abort_unless($write ? app(ModuleGate::class)->writable('groups') : app(ModuleGate::class)->allows('groups'), 403);

        $fresh = app(ProfilePolicy::class)->actor($actor);
        app(ApprovalService::class)->assertAccess($fresh);

        return $fresh;
    }

    public function staff(User $actor, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        abort_if(array_diff($roles, ['Parent', 'Student']) === [], 403);
        abort_unless(array_intersect($roles, ['Teacher', 'Facilitator']) !== [] || app(ProfilePolicy::class)->permission($fresh, 'activity-groups-manage'), 403);

        return $fresh;
    }

    public function manages(User $actor): bool
    {
        $fresh = $this->staff($actor);

        return app(ProfilePolicy::class)->permission($fresh, 'activity-groups-manage');
    }

    public function manage(User $actor): void
    {
        $this->staff($actor, true);
        abort_unless($this->manages($actor), 403);
    }

    public function staffAssignments(User $actor, ?string $date = null): Builder
    {
        $fresh = $this->staff($actor);
        $date ??= $this->today();

        return ActivityStaff::where('active', true)->where('user_id', $fresh->id)->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date);
    }

    public function lineage(ActivityGroup $group): array
    {
        $ids = [];
        $row = $group;
        for ($i = 0; $i < 12; $i++) {
            abort_if(in_array((int) $row->id, $ids, true), 409, 'Invalid activity group ancestry.');
            $ids[] = (int) $row->id;
            if ($row->parent_id === null) {
                return $ids;
            }
            $row = ActivityGroup::where('session_year_id', $group->session_year_id)->findOrFail($row->parent_id);
        }
        abort(422, 'Activity trees may have at most twelve levels.');
    }

    public function descendants(ActivityGroup $group): array
    {
        $ids = [(int) $group->id];
        $front = $ids;
        for ($i = 0; $i < 12 && $front !== []; $i++) {
            $next = ActivityGroup::where('session_year_id', $group->session_year_id)->where('active', true)->whereIn('parent_id', $front)->pluck('id')->map(fn ($x) => (int) $x)->all();
            abort_if(array_intersect($ids, $next) !== [], 409, 'Invalid activity group ancestry.');
            $ids = [...$ids, ...$next];
            abort_if(count($ids) > 1000, 422, 'Activity audience is too large.');
            $front = $next;
        }
        abort_if($front !== [], 422, 'Activity tree exceeds twelve levels.');

        return $ids;
    }

    public function assigned(User $actor, ActivityGroup $group, ?string $date = null): bool
    {
        $ids = $this->lineage($group);

        return $this->staffAssignments($actor, $date)->where(fn ($q) => $q->where('activity_group_id', $group->id)
            ->orWhere(fn ($q) => $q->whereIn('activity_group_id', $ids)->where('role', 'admin')->where('cascade', true)))->exists();
    }

    public function groupStaff(User $actor, int $id, bool $write = false, ?string $date = null): ActivityGroup
    {
        $this->staff($actor, $write);
        $group = ActivityGroup::findOrFail($id);
        if ($this->manages($actor)) {
            return $group;
        }
        foreach ($this->lineage($group) as $ancestor) {
            abort_unless(ActivityGroup::where('active', true)->whereKey($ancestor)->exists(), 404);
        }
        abort_unless($this->assigned($actor, $group) && ($date === null || $this->assigned($actor, $group, $date)), 404);

        return $group;
    }

    public function learnerIds(User $actor): array
    {
        $fresh = $this->actor($actor);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        $ids = [];
        $enrolled = Enrolment::onDate($this->today())->select('learner_id');
        if (in_array('Student', $roles, true)) {
            $ids = Students::where('user_id', $fresh->id)->whereIn('id', $enrolled)->pluck('id')->map(fn ($id) => (int) $id)->all();
        }
        if (in_array('Parent', $roles, true)) {
            $parent = (int) (Parents::where('user_id', $fresh->id)->value('id') ?? 0);
            $linked = app(GuardianService::class)->childrenFor((int) $fresh->id, $parent)->whereIn('id', $enrolled)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $ids = array_values(array_unique([...$ids, ...$linked]));
        }

        return $ids;
    }

    public function read(User $actor, int $id): ActivityGroup
    {
        $this->actor($actor);
        $group = ActivityGroup::findOrFail($id);
        $roles = app(ProfilePolicy::class)->roles($actor);
        if (array_diff($roles, ['Parent', 'Student']) !== []) {
            try {
                return $this->groupStaff($actor, $id);
            } catch (HttpExceptionInterface $error) {
                if (! in_array($error->getStatusCode(), [403, 404], true) || array_intersect($roles, ['Parent', 'Student']) === []) {
                    throw $error;
                }
            }
        }
        foreach ($this->lineage($group) as $ancestor) {
            abort_unless(ActivityGroup::where('active', true)->whereKey($ancestor)->exists(), 404);
        }
        abort_unless(ActivityMembership::where('active', true)->where('activity_group_id', $id)->whereIn('learner_id', $this->learnerIds($actor))
            ->whereDate('starts_on', '<=', $this->today())->whereDate('ends_on', '>=', $this->today())->exists(), 404);

        return $group;
    }

    /** Resolve list scope once; do not scan every learner once per group. */
    public function visible(User $actor, ?int $year = null, bool $staffOnly = false): array
    {
        $fresh = $this->actor($actor);
        if ($year !== null) {
            SessionYear::findOrFail($year);
        }
        $roles = app(ProfilePolicy::class)->roles($fresh);
        $rows = ActivityGroup::when($year !== null, fn ($q) => $q->where('session_year_id', $year))->orderBy('id')->limit(1001)->get();
        abort_if($rows->count() > 1000, 422, 'Choose a school year to narrow the activity list.');
        $staff = array_intersect($roles, ['Teacher', 'Facilitator']) !== [] || app(ProfilePolicy::class)->permission($fresh, 'activity-groups-manage');
        if ($staffOnly || $staff) {
            $this->staff($fresh);
            if ($this->manages($fresh)) {
                return $rows->all();
            }
            $allocations = $this->staffAssignments($fresh)->get()->keyBy('activity_group_id');
        } else {
            abort_if(array_intersect($roles, ['Parent', 'Student']) === [], 403);
            $allocations = collect();
        }
        $memberGroups = $staffOnly ? [] : ActivityMembership::whereIn('learner_id', $this->learnerIds($fresh))->whereDate('starts_on', '<=', $this->today())->whereDate('ends_on', '>=', $this->today())->pluck('activity_group_id')->map(fn ($id) => (int) $id)->all();
        $map = $rows->keyBy('id');
        $visible = [];
        foreach ($rows as $row) {
            $current = $row;
            $seen = [];
            $allowed = false;
            $valid = true;
            while ($current !== null) {
                if (! $current->active || in_array((int) $current->id, $seen, true) || count($seen) >= 12) {
                    $valid = false;
                    break;
                }
                $seen[] = (int) $current->id;
                $grant = $allocations->get($current->id);
                if ($grant !== null && ((int) $current->id === (int) $row->id || ($grant->role === 'admin' && $grant->cascade))) {
                    $allowed = true;
                }
                if ($current->parent_id === null) {
                    break;
                }
                $current = $map->get($current->parent_id);
                if ($current === null) {
                    $valid = false;
                }
            }
            if ($valid && (($staff && $allowed) || in_array((int) $row->id, $memberGroups, true))) {
                $visible[] = $row;
            }
        }

        return $visible;
    }

    public function canStaff(User $actor, int $id): bool
    {
        try {
            $this->groupStaff($actor, $id);

            return true;
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [403, 404], true)) {
                throw $error;
            }

            return false;
        }
    }

    public function facilitatorOnly(User $actor): bool
    {
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($actor);
        $roles = $policy->roles($fresh);

        return in_array('Facilitator', $roles, true) && ! in_array('Teacher', $roles, true)
            && ! $policy->permission($fresh, 'activity-groups-manage') && ! $policy->permission($fresh, 'communication-manage');
    }

    public function audience(User $actor, int $id): array
    {
        $group = $this->groupStaff($actor, $id);
        $ids = $this->descendants($group);
        foreach ($ids as $child) {
            $this->groupStaff($actor, $child);
        }

        return $ids;
    }
}
