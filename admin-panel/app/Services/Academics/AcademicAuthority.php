<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;

/** Canonical fresh authority shared by marks, registers, reports and planning. */
class AcademicAuthority
{
    public function today(): string
    {
        return now(app(CurrentSchool::class)->school()?->timezone ?? 'Africa/Johannesburg')->toDateString();
    }

    public function staff(User $actor): User
    {
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($actor);
        abort_if(array_diff($policy->roles($fresh), ['Parent', 'Student']) === [], 403);

        return $fresh;
    }

    public function manages(User $actor): bool
    {
        return app(ProfilePolicy::class)->permission($this->staff($actor), 'academic-structure-manage');
    }

    public function manage(User $actor): void
    {
        abort_unless($this->manages($actor), 403);
    }

    public function teachers(User $actor): array
    {
        $fresh = $this->staff($actor);

        return Teacher::query()->where('user_id', $fresh->id)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function group(User $actor, int $id, bool $capture = false, ?string $date = null): TeachingGroup
    {
        $fresh = $this->staff($actor);
        $group = TeachingGroup::query()->findOrFail($id);
        if (! $capture && ($this->manages($fresh) || $this->leads($fresh, 'grade_head', (int) $group->grade_level_id, (int) $group->session_year_id)
            || $this->leads($fresh, 'principal', (int) $group->grade_level_id, (int) $group->session_year_id))) {
            return $group;
        }
        $date ??= $this->today();
        $allocation = TeachingAllocation::query()->where('teaching_group_id', $id)->whereIn('teacher_id', $this->teachers($fresh))
            ->whereDate('ends_on', '>=', $date);
        if ($capture) {
            $allocation->whereDate('starts_on', '<=', $date);
        }
        abort_unless($group->active && $allocation->exists(), 403);

        return $group;
    }

    public function groups(User $actor): Builder
    {
        $fresh = $this->staff($actor);
        $query = TeachingGroup::query();
        if ($this->manages($fresh)) {
            return $query;
        }

        return $query->where('active', true)->where(function ($scope) use ($fresh) {
            $scope->whereHas('allocations', fn ($q) => $q->whereIn('teacher_id', $this->teachers($fresh))->whereDate('ends_on', '>=', $this->today()))
                ->orWhereExists(function ($q) use ($fresh) {
                    $q->selectRaw('1')->from('academic_leaderships as group_lead')
                        ->where('group_lead.school_id', app(CurrentSchool::class)->id())->where('group_lead.user_id', $fresh->id)
                        ->whereColumn('group_lead.session_year_id', 'teaching_groups.session_year_id')->whereNull('group_lead.deleted_at')
                        ->whereDate('group_lead.starts_on', '<=', $this->today())->whereDate('group_lead.ends_on', '>=', $this->today())
                        ->where(fn ($lead) => $lead->where(fn ($principal) => $principal->where('group_lead.kind', 'principal')->whereNull('group_lead.grade_level_id'))
                            ->orWhere(fn ($grade) => $grade->where('group_lead.kind', 'grade_head')->whereColumn('group_lead.grade_level_id', 'teaching_groups.grade_level_id')));
                });
        });
    }

    public function leads(User $actor, string $kind, int $gradeId, int $yearId, ?string $date = null): bool
    {
        $fresh = $this->staff($actor);
        $date ??= $this->today();
        $query = AcademicLeadership::query()->where('user_id', $fresh->id)->where('kind', $kind)->where('session_year_id', $yearId)
            ->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date);

        return ($kind === 'principal' ? $query->whereNull('grade_level_id') : $query->where('grade_level_id', $gradeId))->exists();
    }
}
