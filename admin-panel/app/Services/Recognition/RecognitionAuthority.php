<?php

declare(strict_types=1);

namespace App\Services\Recognition;

use App\Models\Academics\HomeroomAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Recognition\AwardCategory;
use App\Models\Recognition\AwardCategoryAdmin;
use App\Models\Recognition\AwardWindow;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Activities\CommunityAuthority;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RecognitionAuthority
{
    public function today(): string
    {
        return app(AcademicAuthority::class)->today();
    }

    public function actor(User $actor, string $module, bool $write = false): User
    {
        abort_unless(in_array($module, ['house_points', 'awards'], true), 404);
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->isActive(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required, 403);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable($module) : $gate->allows($module), 403);

        return $fresh;
    }

    public function staff(User $actor, string $module, bool $write = false): User
    {
        $fresh = $this->actor($actor, $module, $write);
        abort_unless(array_diff(app(ProfilePolicy::class)->roles($fresh), ['Parent', 'Student']) !== [], 403);

        return $fresh;
    }

    public function manages(User $actor, string $module, bool $write = false): bool
    {
        $fresh = $this->staff($actor, $module, $write);
        $a = app(AcademicAuthority::class);

        return (array_intersect(app(ProfilePolicy::class)->roles($fresh), ['Admin', 'Super Admin']) !== [] && $a->manages($fresh)) || $a->currentLeadership($fresh, 'principal', 0);
    }

    public function manage(User $actor, string $module, bool $write = true): User
    {
        abort_unless($this->manages($actor, $module, $write), 403);

        return $this->staff($actor, $module, $write);
    }

    public function currentYear(?int $id = null): SessionYear
    {
        $year = SessionYear::findOrFail((int) schoolSetting('session_year'));
        abort_unless(($id === null || $id === (int) $year->id) && $year->start_date !== null && $year->end_date !== null
            && substr((string) $year->start_date, 0, 10) <= $this->today() && substr((string) $year->end_date, 0, 10) >= $this->today(), 409, 'New recognition entries use the current school year.');

        return $year;
    }

    public function placement(int $learner, ?int $year = null, ?string $on = null): Enrolment
    {
        $query = Enrolment::with('homeroom.class')->where('learner_id', $learner)->onDate($on ?? $this->today());
        if ($year !== null) {
            $query->where('session_year_id', $year);
        }
        $row = $query->firstOrFail();
        abort_unless($row->homeroom?->class?->grade_level_id !== null, 409, 'Map the learner homeroom to a grade before recognition.');

        return $row;
    }

    /** Current physical placement and actual staff allocation, never a stale learner projection. */
    public function learner(User $actor, int $id, string $module, bool $write = false, ?int $year = null): array
    {
        $fresh = $this->staff($actor, $module, $write);
        $learner = Students::with('user')->findOrFail($id);
        $place = $this->placement($id, $year);
        $a = app(AcademicAuthority::class);
        if ($this->manages($fresh, $module, $write)) {
            return ['learner' => $learner, 'placement' => $place, 'scope_kind' => 'school', 'scope_id' => null];
        }
        $grade = (int) $place->homeroom->class->grade_level_id;
        if ($a->leads($fresh, 'grade_head', $grade, (int) $place->session_year_id)) {
            return ['learner' => $learner, 'placement' => $place, 'scope_kind' => 'grade', 'scope_id' => $grade];
        }
        $today = $this->today();
        $teachers = $a->teachers($fresh);
        $home = HomeroomAllocation::where('class_section_id', $place->class_section_id)->where('session_year_id', $place->session_year_id)->whereIn('teacher_id', $teachers)
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->first();
        if ($home !== null) {
            return ['learner' => $learner, 'placement' => $place, 'scope_kind' => 'homeroom', 'scope_id' => (int) $home->class_section_id];
        }
        $groups = TeachingGroupMembership::where('learner_id', $id)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('teaching_group_id');
        $group = TeachingGroup::whereIn('id', $groups)->where('active', true)->where('session_year_id', $place->session_year_id)->where('grade_level_id', $grade)
            ->whereHas('allocations', fn ($q) => $q->whereIn('teacher_id', $teachers)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today))->first();
        if ($group !== null) {
            return ['learner' => $learner, 'placement' => $place, 'scope_kind' => 'academic_group', 'scope_id' => (int) $group->id];
        }
        if ($module === 'awards') {
            $categories = $this->awardReadCategories($fresh, $learner, $place, true, $write);
            if ($categories !== []) {
                return ['learner' => $learner, 'placement' => $place, 'scope_kind' => 'award_category', 'scope_id' => $categories[0]];
            }
        }
        $activities = $this->activityScopes($fresh, $id, (int) $place->session_year_id);
        if ($activities !== []) {
            return ['learner' => $learner, 'placement' => $place, 'scope_kind' => 'activity_group', 'scope_id' => $activities[0]];
        }
        abort(404);
    }

    /** Every currently assigned group, independent of a default picker order. */
    public function activityScopes(User $actor, int $learner, int $year): array
    {
        if (! app(ModuleGate::class)->allows('groups')) {
            return [];
        }
        $today = $this->today();
        $ids = [];
        $community = app(CommunityAuthority::class);
        $members = ActivityMembership::where('learner_id', $learner)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('activity_group_id');
        foreach (ActivityGroup::whereIn('id', $members)->where('session_year_id', $year)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->orderBy('id')->get() as $group) {
            try {
                if (! $community->assigned($actor, $group)) {
                    continue;
                }
                foreach ($community->lineage($group) as $ancestor) {
                    abort_unless(ActivityGroup::whereKey($ancestor)->where('session_year_id', $year)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->exists(), 404);
                }
                $ids[] = (int) $group->id;
            } catch (HttpExceptionInterface|ModelNotFoundException) {
            }
        }

        return $ids;
    }

    public function awardReadCategories(User $actor, Students $learner, Enrolment $place, bool $administratorsOnly = false, bool $write = false): array
    {
        $ids = [];
        $activities = $administratorsOnly ? [] : $this->activityScopes($actor, (int) $learner->id, (int) $place->session_year_id);
        foreach (AwardCategory::when($write, fn ($q) => $q->where('active', true))->orderBy('id')->get() as $category) {
            try {
                if (! $this->categoryAdmin($actor, $category, $write) && ! ($category->scope_kind === 'group' && in_array($this->categoryGroup($category, (int) $place->session_year_id), $activities, true))) {
                    continue;
                }
                $this->eligible($category, $learner, $place, $write);
                $ids[] = (int) $category->id;
            } catch (HttpExceptionInterface|ModelNotFoundException) {
            }
        }

        return $ids;
    }

    public function profileCategories(User $actor, int $learner): ?array
    {
        $this->staff($actor, 'awards');
        if ($this->manages($actor, 'awards')) {
            return null;
        }
        $scope = $this->learner($actor, $learner, 'awards');
        if (in_array($scope['scope_kind'], ['grade', 'homeroom', 'academic_group'], true)) {
            return null;
        }

        return $this->awardReadCategories($actor, $scope['learner'], $scope['placement']);
    }

    /** Current institutional authority may read retained school history. */
    public function retainedLearner(User $actor, int $id, string $module): Students
    {
        $this->staff($actor, $module);
        if (! $this->manages($actor, $module)) {
            return $this->learner($actor, $id, $module)['learner'];
        }

        return Students::with('user')->findOrFail($id);
    }

    public function family(User $actor, int $id, string $module): Students
    {
        $fresh = $this->actor($actor, $module);
        $child = Students::with('user')->findOrFail($id);
        abort_if($child->user === null, 404);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        if ((int) $child->user_id === (int) $fresh->id && in_array('Student', $roles, true)) {
            app(ApprovalService::class)->assertAccess($fresh);
        } else {
            abort_unless(in_array('Parent', $roles, true), 403);
            app(ProfilePolicy::class)->resolve($fresh, $child->user, 'learner');
        }

        return $child;
    }

    public function lineage(AwardCategory $category, bool $requireActive = true): array
    {
        $ids = [];
        for ($depth = 0; $depth < 8; $depth++) {
            abort_if(in_array((int) $category->id, $ids, true) || ($requireActive && ! $category->active), 409, 'This award category needs review.');
            $ids[] = (int) $category->id;
            if ($category->parent_id === null) {
                return $ids;
            }
            $category = AwardCategory::findOrFail($category->parent_id);
        }
        abort(409, 'Award category trees may contain at most eight levels.');
    }

    /** Reusable category identity, explicitly assigned to a dated annual group. */
    public function categoryGroup(AwardCategory $category, int $year): ?int
    {
        $mapped = AwardWindow::where('award_category_id', $category->id)->where('session_year_id', $year)->value('activity_group_id');
        if ($mapped !== null) {
            return (int) $mapped;
        }

        return ActivityGroup::where('session_year_id', $year)->whereKey($category->activity_group_id)->exists() ? (int) $category->activity_group_id : null;
    }

    public function categoryAdmin(User $actor, AwardCategory $category, bool $write = false): bool
    {
        $fresh = $this->staff($actor, 'awards', $write);
        $ids = $this->lineage($category, false);
        if ($this->manages($fresh, 'awards', $write)) {
            return true;
        }
        $today = $this->today();
        if (AwardCategoryAdmin::whereIn('award_category_id', $ids)->where('user_id', $fresh->id)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->exists()) {
            return true;
        }
        if ($category->scope_kind === 'grade') {
            return app(AcademicAuthority::class)->currentLeadership($fresh, 'grade_head', (int) $category->grade_level_id);
        }
        if ($category->scope_kind === 'group' && app(ModuleGate::class)->allows('groups')) {
            $group = ActivityGroup::where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->find($this->categoryGroup($category, (int) schoolSetting('session_year')));
            if ($group !== null) {
                $community = app(CommunityAuthority::class);
                $ancestors = $community->lineage($group);
                foreach ($ancestors as $ancestor) {
                    abort_unless(ActivityGroup::where('active', true)->where('session_year_id', $group->session_year_id)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->whereKey($ancestor)->exists(), 404);
                }

                return $community->staffAssignments($fresh)->where('role', 'admin')->where(fn ($q) => $q->where('activity_group_id', $group->id)
                    ->orWhere(fn ($q) => $q->whereIn('activity_group_id', $ancestors)->where('cascade', true)))->exists();
            }
        }

        return false;
    }

    public function eligible(AwardCategory $category, Students $learner, Enrolment $place, bool $requireActive = true): void
    {
        $this->lineage($category, $requireActive);
        $grade = (int) $place->homeroom->class->grade_level_id;
        abort_if($category->scope_kind === 'grade' && (int) $category->grade_level_id !== $grade, 404);
        $today = $this->today();
        if ($category->subject_id !== null) {
            abort_unless(TeachingGroupMembership::where('learner_id', $learner->id)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)
                ->whereHas('group', fn ($q) => $q->where('active', true)->where('session_year_id', $place->session_year_id)->where('grade_level_id', $grade)->where('subject_id', $category->subject_id))->exists(), 404);
        }
        if ($category->scope_kind === 'group') {
            abort_unless(app(ModuleGate::class)->allows('groups'), 403);
            $group = ActivityGroup::where('active', true)->where('session_year_id', $place->session_year_id)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->findOrFail($this->categoryGroup($category, (int) $place->session_year_id));
            foreach (app(CommunityAuthority::class)->lineage($group) as $ancestor) {
                abort_unless(ActivityGroup::where('active', true)->where('session_year_id', $place->session_year_id)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->whereKey($ancestor)->exists(), 404);
            }
            abort_unless(ActivityMembership::where('activity_group_id', $group->id)->where('learner_id', $learner->id)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->exists(), 404);
        }
    }
}
