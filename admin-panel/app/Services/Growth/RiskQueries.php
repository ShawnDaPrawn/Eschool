<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\Intervention;
use App\Models\Growth\RiskCaseload;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Pastoral\SensitiveRecordsAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Bounded metadata pickers; no observation evidence or intervention body. */
class RiskQueries
{
    public function configuration(User $actor): array
    {
        $a = app(RiskAuthority::class);
        $a->enabled();
        $fresh = $a->actor($actor);
        $year = (int) schoolSetting('session_year');
        $today = app(AcademicAuthority::class)->today();
        $head = AcademicLeadership::where('user_id', $fresh->id)->where('kind', 'grade_head')
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('grade_level_id')->map(fn ($id) => (int) $id)->all();
        $principal = $professional = false;
        try {
            $a->principal($fresh);
            $principal = true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
        }
        try {
            app(SensitiveRecordsAccess::class)->actor($fresh);
            $professional = true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
        }
        $groups = app(ProgressQueries::class)->configuration($fresh)['groups'];
        abort_unless($principal || $professional || $head !== [] || $groups !== [], 403);

        return ['session_year_id' => $year, 'can_manage' => $principal, 'can_read_signals' => $head !== [] || $professional,
            'grade_ids' => $head, 'groups' => $groups, 'settings' => app(RiskSettingsService::class)->values(),
            'years' => SessionYear::withTrashed()->orderByDesc('start_date')->limit(100)->get()->map(fn ($y) => ['id' => (int) $y->id, 'name' => $y->name])->all()];
    }

    public function learners(User $actor, int $year, int $page = 1, ?string $search = null): array
    {
        $config = $this->configuration($actor);
        $a = app(RiskAuthority::class);
        $a->year($year);
        $today = app(AcademicAuthority::class)->today();
        $ids = Enrolment::where('session_year_id', $year);
        if ($year === (int) schoolSetting('session_year')) {
            $ids->onDate($today);
        }
        if (! $config['can_manage']) {
            $members = TeachingGroupMembership::whereIn('teaching_group_id', array_column($config['groups'], 'id'))
                ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('learner_id');
            $grants = RiskCaseload::where('professional_id', $actor->id)->where('session_year_id', $year)->whereNull('revoked_at')
                ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('learner_id');
            // Allocated teachers are narrowed again by caseActor before returning a choice.
            $ids->where(fn ($q) => $q->whereHas('homeroom.class', fn ($c) => $c->whereIn('grade_level_id', $config['grade_ids']))
                ->orWhereIn('learner_id', $members)->orWhereIn('learner_id', $grants));
        }
        $rows = Students::whereIn('id', $ids->select('learner_id'))->with('user')
            ->when($search !== null && $search !== '', fn ($q) => $q->whereHas('user', fn ($u) => $u->where(fn ($n) => $n->where('first_name', 'like', '%'.$search.'%')->orWhere('last_name', 'like', '%'.$search.'%'))))
            ->orderBy('id')->paginate(50, ['id', 'user_id'], 'page', max(1, $page));
        $result = [];
        foreach ($rows as $learner) {
            try {
                if (! $config['can_manage']) {
                    $grade = $year === (int) schoolSetting('session_year') ? $a->currentGrade((int) $learner->id, $year) : null;
                    if ($grade === null) {
                        continue;
                    }
                    $a->caseActor($actor, (int) $learner->id, $year, $grade);
                }
                $result[] = ['learner_id' => (int) $learner->id, 'name' => trim($learner->user->first_name.' '.$learner->user->last_name)];
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                continue;
            }
        }

        return ['session_year_id' => $year, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'learners' => $result];
    }

    public function professionals(User $actor, int $learner, int $year, int $page = 1, ?string $search = null): array
    {
        app(RiskAuthority::class)->administer($actor, $learner, $year);
        $rows = User::where('status', 1)->whereIn('id', SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())
            ->whereIn('role', [SchoolRoles::COUNSELLOR, SchoolRoles::SAFEGUARDING])->select('user_id'))
            ->when($search !== null && $search !== '', fn ($q) => $q->where(fn ($n) => $n->where('first_name', 'like', '%'.$search.'%')->orWhere('last_name', 'like', '%'.$search.'%')))
            ->orderBy('id')->paginate(50, ['*'], 'page', max(1, $page));
        $people = [];
        foreach ($rows as $person) {
            try {
                app(SensitiveRecordsAccess::class)->actor($person);
                $people[] = ['id' => (int) $person->id, 'name' => trim($person->first_name.' '.$person->last_name)];
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                continue;
            }
        }

        return ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'staff' => $people];
    }

    public function owners(User $actor, int $learner, int $year, ?int $intervention = null, int $page = 1, ?string $search = null): array
    {
        $a = app(RiskAuthority::class);
        $a->actor($actor);
        $a->year($year);
        $kind = null;
        $admin = false;
        if ($intervention !== null) {
            $row = Intervention::select(Interventions::META)->where('learner_id', $learner)->where('session_year_id', $year)->findOrFail($intervention);
            $a->administer($actor, $learner, $year);
            $grade = (int) $row->grade_level_id;
            $kind = $row->owner_authority;
            $admin = true;
        } else {
            $a->year($year, true);
            $grade = $a->currentGrade($learner, $year);
            $a->caseActor($actor, $learner, $year, $grade, true);
            try {
                $a->administer($actor, $learner, $year);
                $admin = true;
            } catch (HttpExceptionInterface|ModelNotFoundException) {
            }
        }
        $rows = User::where('status', 1)->whereIn('id', SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())
            ->whereNotIn('role', [SchoolRoles::PARENT, SchoolRoles::STUDENT])->select('user_id'))
            ->when(! $admin, fn ($q) => $q->whereKey($actor->id))
            ->when($search !== null && $search !== '', fn ($q) => $q->where(fn ($n) => $n->where('first_name', 'like', '%'.$search.'%')->orWhere('last_name', 'like', '%'.$search.'%')))
            ->orderBy('id')->paginate(50, ['*'], 'page', max(1, $page));
        $people = [];
        foreach ($rows as $person) {
            try {
                $selected = $kind ?? $a->caseKind($person, $learner, $year, $grade);
                $a->caseActor($person, $learner, $year, $grade, false, $selected);
                $people[] = ['id' => (int) $person->id, 'name' => trim($person->first_name.' '.$person->last_name), 'authority' => $selected];
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                continue;
            }
        }

        return ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'owners' => $people];
    }

    public function assignments(User $actor, int $learner, int $year): array
    {
        app(RiskAuthority::class)->administer($actor, $learner, $year);

        return ['learner_id' => $learner, 'session_year_id' => $year, 'assignments' => RiskCaseload::where('learner_id', $learner)->where('session_year_id', $year)->orderBy('id')->get()->map(fn ($row) => [
            'id' => (int) $row->id, 'professional_id' => (int) $row->professional_id, 'starts_on' => $row->starts_on->toDateString(),
            'ends_on' => $row->ends_on->toDateString(), 'revoked' => $row->revoked_at !== null, 'revision' => $row->revision])->all()];
    }
}
