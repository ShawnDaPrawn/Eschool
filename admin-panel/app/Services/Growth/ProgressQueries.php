<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\LearnerNoteAssignment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Term;
use App\Models\User;
use App\Policies\Growth\LearnerNotesPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Pastoral\SensitiveRecordsAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProgressQueries
{
    public function configuration(User $actor): array
    {
        app(LearnerNotesPolicy::class)->enabled();
        $authority = app(AcademicAuthority::class);
        $fresh = $authority->staff($actor);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        $today = $authority->today();
        $year = (int) schoolSetting('session_year');
        $lead = AcademicLeadership::query()->where('user_id', $fresh->id)->where('session_year_id', $year)
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->get();
        $groups = TeachingGroup::query()->where('active', true)->whereHas('subject')->where('session_year_id', $year)->with('subject', 'gradeLevel')
            ->where(function ($q) use ($fresh, $authority, $today, $lead) {
                $q->whereIn('id', TeachingAllocation::query()->whereIn('teacher_id', $authority->teachers($fresh))->whereDate('starts_on', '<=', $today)
                    ->whereDate('ends_on', '>=', $today)->select('teaching_group_id'));
                if ($lead->where('kind', 'principal')->whereNull('grade_level_id')->isNotEmpty()) {
                    $q->orWhereRaw('1 = 1');
                } else {
                    $q->orWhereIn('grade_level_id', $lead->where('kind', 'grade_head')->pluck('grade_level_id'));
                }
            })->orderBy('name')->limit(501)->get();
        abort_if($groups->count() > 500, 422, 'The group catalogue is too large. Contact the school to review its allocations.');
        $manage = $authority->manages($fresh);
        abort_unless($manage || $groups->isNotEmpty() || array_intersect($roles, [SchoolRoles::TEACHER, SchoolRoles::COUNSELLOR, SchoolRoles::SAFEGUARDING]) !== [], 403);
        $principal = $lead->where('kind', 'principal')->whereNull('grade_level_id')->isNotEmpty()
            && ! PlatformStaffIdentity::query()->where('user_id', $fresh->id)->where('active', true)->exists();
        $clinical = false;
        if (array_intersect($roles, [SchoolRoles::COUNSELLOR, SchoolRoles::SAFEGUARDING]) !== []) {
            try {
                app(SensitiveRecordsAccess::class)->actor($fresh);
                $clinical = true;
            } catch (HttpException|ModelNotFoundException $error) {
                $clinical = false;
            }
        }

        return ['session_year_id' => $year, 'can_manage_bands' => $manage, 'can_assign_counselling' => $principal, 'can_read_caseload' => $clinical,
            'settings' => app(ProgressBands::class)->settings($fresh), 'restricted_staff' => $principal ? $this->restrictedStaff() : [],
            'years' => SessionYear::query()->orderByDesc('start_date')->limit(100)->get()->map(fn ($y) => ['id' => (int) $y->id, 'name' => $y->name])->all(),
            'terms' => Term::query()->where('session_year_id', $year)->orderBy('start_date')->get()->map(fn ($t) => ['id' => (int) $t->id, 'name' => $t->name])->all(),
            'groups' => $groups->map(fn ($g) => ['id' => (int) $g->id, 'name' => $g->name, 'subject_id' => (int) $g->subject_id,
                'subject_name' => $g->subject->name, 'grade_name' => $g->gradeLevel->name, 'session_year_id' => (int) $g->session_year_id])->all()];
    }

    private function restrictedStaff(): array
    {
        $candidates = User::query()->where('status', 1)->whereIn('id', SchoolMembership::query()->active()
            ->where('school_id', app(CurrentSchool::class)->id())
            ->whereIn('role', [SchoolRoles::COUNSELLOR, SchoolRoles::SAFEGUARDING])->select('user_id'))->orderBy('first_name')->limit(501)->get();
        abort_if($candidates->count() > 500, 422, 'Filter the restricted staff catalogue before assigning notes.');
        $rows = [];
        foreach ($candidates as $person) {
            try {
                app(SensitiveRecordsAccess::class)->actor($person);
                $rows[] = ['id' => (int) $person->id, 'name' => trim($person->first_name.' '.$person->last_name)];
            } catch (HttpException|ModelNotFoundException $error) {
                continue;
            }
        }

        return $rows;
    }

    public function group(User $actor, int $groupId): TeachingGroup
    {
        $configuration = $this->configuration($actor);
        abort_unless(in_array($groupId, array_column($configuration['groups'], 'id'), true), 404);

        return TeachingGroup::query()->findOrFail($groupId);
    }

    public function roster(User $actor, int $groupId, int $page = 1): array
    {
        $this->group($actor, $groupId);
        $today = app(AcademicAuthority::class)->today();
        $ids = TeachingGroupMembership::query()->where('teaching_group_id', $groupId)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('learner_id');

        return $this->learners(Students::query()->whereIn('id', $ids)->with('user')->orderBy('id')->paginate(100, ['*'], 'page', max(1, $page)), ['group_id' => $groupId]);
    }

    public function assignmentLearners(User $actor, int $page = 1): array
    {
        app(LearnerNotesPolicy::class)->principal($actor);

        return $this->learners(Students::query()->with('user')->whereHas('user', fn ($q) => $q->where('status', 1))
            ->orderBy('id')->paginate(100, ['*'], 'page', max(1, $page)));
    }

    public function caseload(User $actor, int $page = 1): array
    {
        app(LearnerNotesPolicy::class)->enabled();
        $fresh = app(SensitiveRecordsAccess::class)->actor($actor);
        $today = app(AcademicAuthority::class)->today();
        $ids = LearnerNoteAssignment::query()->where('staff_user_id', $fresh->id)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('learner_id');

        return $this->learners(Students::query()->whereIn('id', $ids)->with('user')->orderBy('id')->paginate(100, ['*'], 'page', max(1, $page)));
    }

    private function learners(LengthAwarePaginator $rows, array $context = []): array
    {
        return $context + ['current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'total' => $rows->total(),
            'learners' => array_map(fn ($learner) => ['learner_id' => (int) $learner->id,
                'display_name' => $learner->user === null ? __('Archived learner') : trim($learner->user->first_name.' '.$learner->user->last_name)], $rows->items())];
    }
}
