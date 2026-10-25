<?php

declare(strict_types=1);

namespace App\Services\Discipline;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Discipline\DetentionSession;
use App\Models\Discipline\DisciplineRecord;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Metadata discovery is bounded and never selects staff notes or decision text. */
class DisciplineQueries
{
    public function available(User $actor): bool
    {
        try {
            $this->configuration($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    /** Current teaching rights for retained own records, before pagination. */
    private function teachingLearners(User $actor): Builder
    {
        $a = app(AcademicAuthority::class);
        $today = $a->today();
        $groups = TeachingGroup::where('active', true)->where('session_year_id', (int) schoolSetting('session_year'))
            ->whereIn('id', TeachingAllocation::whereIn('teacher_id', $a->teachers($actor))->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('teaching_group_id'));

        return TeachingGroupMembership::whereIn('teaching_group_id', $groups->select('id'))
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('learner_id');
    }

    public function configuration(User $actor): array
    {
        $a = app(DisciplineAuthority::class);
        $fresh = $a->actor($actor);
        $year = (int) schoolSetting('session_year');
        $today = app(AcademicAuthority::class)->today();
        $heads = AcademicLeadership::where('user_id', $fresh->id)->where('kind', 'grade_head')->where('session_year_id', $year)
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('grade_level_id')->map(fn ($id) => (int) $id)->all();
        $manager = $a->manages($fresh);
        abort_unless($manager || $heads !== [] || in_array('Teacher', app(ProfilePolicy::class)->roles($fresh), true), 403);
        $grades = GradeLevel::when(! $manager, fn ($q) => $q->whereIn('id', $heads))->orderBy('sort_order')->get(['id', 'name']);

        return ['session_year_id' => $year, 'today' => $today, 'writable' => app(ModuleGate::class)->writable('discipline'), 'can_manage' => $manager,
            'can_hear' => app(AcademicAuthority::class)->currentLeadership($fresh, 'principal', 0),
            'grade_ids' => $heads, 'grades' => $grades->map(fn ($g) => ['id' => (int) $g->id, 'name' => $g->name])->all(),
            'years' => SessionYear::withTrashed()->orderByDesc('start_date')->limit(100)->get()->map(fn ($y) => ['id' => (int) $y->id, 'name' => $y->name])->all(),
            'framework' => app(DisciplineFrameworks::class)->read($fresh, $year)['framework']];
    }

    public function learners(User $actor, int $year, int $page = 1, ?string $search = null): array
    {
        $config = $this->configuration($actor);
        $a = app(DisciplineAuthority::class);
        $a->year($year);
        $today = app(AcademicAuthority::class)->today();
        $enrolled = Enrolment::where('session_year_id', $year)->onDate($today);
        if (! $config['can_manage']) {
            $members = $this->teachingLearners($actor);
            $enrolled->where(fn ($q) => $q->whereHas('homeroom.class', fn ($c) => $c->whereIn('grade_level_id', $config['grade_ids']))->orWhereIn('learner_id', $members));
        }
        $rows = Students::whereIn('id', $enrolled->select('learner_id'))->with('user')->when($search !== null && $search !== '',
            fn ($q) => $q->whereHas('user', fn ($u) => $u->where(fn ($n) => $n->where('first_name', 'like', '%'.$search.'%')->orWhere('last_name', 'like', '%'.$search.'%'))))
            ->orderBy('id')->paginate(50, ['id', 'user_id'], 'page', max(1, $page));
        $items = [];
        foreach ($rows as $row) {
            try {
                $grade = $a->record($actor, (int) $row->id, $year, $today);
                $items[] = ['learner_id' => (int) $row->id, 'name' => trim($row->user->first_name.' '.$row->user->last_name), 'grade_level_id' => $grade];
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                continue;
            }
        }

        return ['session_year_id' => $year, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'learners' => $items];
    }

    public function records(User $actor, int $year, int $page = 1, ?string $status = null): array
    {
        $config = $this->configuration($actor);
        app(DisciplineAuthority::class)->year($year);
        $rows = DisciplineRecord::select(DisciplineRecords::META)->where('session_year_id', $year)
            ->when(! $config['can_manage'], fn ($q) => $q->where(fn ($s) => $s->where(fn ($own) => $own->where('recorded_by', $actor->id)->whereIn('learner_id', $this->teachingLearners($actor)))->orWhereIn('grade_level_id', $config['grade_ids'])))
            ->when($status !== null, fn ($q) => $q->where('status', $status))->orderByDesc('occurred_on')->orderByDesc('id')->paginate(50, ['*'], 'page', max(1, $page));
        $items = [];
        foreach ($rows as $row) {
            try {
                app(DisciplineRecords::class)->authorize($actor, (int) $row->id);
                $items[] = app(DisciplineRecords::class)->metadata($row);
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                continue;
            }
        }
        $names = Students::whereIn('id', array_column($items, 'learner_id'))->with('user')->get()->keyBy('id');
        foreach ($items as &$item) {
            $user = $names->get($item['learner_id'])?->user;
            $item['learner_name'] = trim(($user?->first_name ?? '').' '.($user?->last_name ?? ''));
        }
        unset($item);

        return ['session_year_id' => $year, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'records' => $items];
    }

    public function detentions(User $actor, int $year, int $page = 1): array
    {
        $config = $this->configuration($actor);
        app(DisciplineAuthority::class)->year($year);
        $rows = DetentionSession::select('id', 'session_year_id', 'grade_level_id', 'supervisor_id', 'on_date', 'starts_at', 'ends_at', 'timezone', 'status', 'capacity', 'revision')
            ->where('session_year_id', $year)->when(! $config['can_manage'], fn ($q) => $q->where(fn ($s) => $s->where('supervisor_id', $actor->id)->orWhereIn('grade_level_id', $config['grade_ids'])))
            ->orderByDesc('on_date')->orderByDesc('id')->paginate(50, ['*'], 'page', max(1, $page));
        $items = [];
        foreach ($rows as $row) {
            try {
                app(DisciplineDetentions::class)->authorize($actor, (int) $row->id);
                $items[] = ['id' => (int) $row->id, 'session_year_id' => (int) $row->session_year_id, 'grade_level_id' => (int) $row->grade_level_id,
                    'supervisor_id' => (int) $row->supervisor_id, 'on_date' => $row->on_date->toDateString(), 'starts_at' => $row->starts_at,
                    'ends_at' => $row->ends_at, 'timezone' => $row->timezone, 'status' => $row->status, 'capacity' => $row->capacity, 'revision' => $row->revision];
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                continue;
            }
        }

        return ['session_year_id' => $year, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'sessions' => $items];
    }

    public function supervisors(User $actor, int $year, int $grade, string $date, int $page = 1): array
    {
        app(DisciplineAuthority::class)->head($actor, $year, $grade);
        $rows = User::whereIn('id', SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->where('role', 'Teacher')
            ->where(fn ($q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $date))->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date))->select('user_id'))
            ->where('status', 1)->orderBy('id')->paginate(50, ['id', 'first_name', 'last_name'], 'page', max(1, $page));
        $items = [];
        foreach ($rows as $row) {
            try {
                app(DisciplineAuthority::class)->actor($row);
                if (! in_array('Teacher', app(ProfilePolicy::class)->roles($row), true)) {
                    continue;
                }
                $items[] = ['id' => (int) $row->id, 'name' => trim($row->first_name.' '.$row->last_name)];
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                continue;
            }
        }

        return ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'supervisors' => $items];
    }
}
