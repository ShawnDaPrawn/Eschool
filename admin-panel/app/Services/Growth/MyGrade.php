<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\GradeLevel;
use App\Models\Attendance;
use App\Models\Discipline\DisciplineRecord;
use App\Models\Growth\RiskObservation;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\Attendance\RegisterLegacy;
use App\Services\Discipline\DisciplineRecords;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Recognition\AwardNominations;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** A current working-grade projection, never a professional or clinical caseload. */
class MyGrade
{
    private function actor(User $actor): User
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating(), 403);
        abort_unless(School::active()->whereKey($school->id())->exists(), 403);
        app(ModuleGate::class)->forget();
        abort_unless(app(ModuleGate::class)->allows('my_grade'), 403);
        $fresh = app(AcademicAuthority::class)->staff($actor);
        abort_if($fresh->password_change_required || PlatformStaffIdentity::where('user_id', $fresh->id)->where('active', true)->exists(), 403);

        return $fresh;
    }

    public function available(User $actor): bool
    {
        try {
            $this->access($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function access(User $actor): array
    {
        $fresh = $this->actor($actor);
        $year = (int) schoolSetting('session_year');
        SessionYear::findOrFail($year);
        $today = app(AcademicAuthority::class)->today();
        $ids = AcademicLeadership::where('user_id', $fresh->id)->where('kind', 'grade_head')->where('session_year_id', $year)
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('grade_level_id');
        $grades = GradeLevel::whereIn('id', $ids)->orderBy('sort_order')->get(['id', 'name']);
        abort_if($grades->isEmpty(), 403);

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $fresh->id, 'session_year_id' => $year, 'today' => $today, 'writable' => app(ModuleGate::class)->writable('my_grade'),
            'grades' => $grades->map(fn ($grade) => ['id' => (int) $grade->id, 'name' => $grade->name])->all()];
    }

    private function grade(User $actor, int $grade): array
    {
        $access = $this->access($actor);
        abort_unless(in_array($grade, array_column($access['grades'], 'id'), true), 403);

        return $access;
    }

    public function dashboard(User $actor, int $grade, ?string $date = null, int $page = 1, int $disciplinePage = 1): array
    {
        $school = app(CurrentSchool::class)->id();
        $access = $this->grade($actor, $grade);
        $year = $access['session_year_id'];
        $date ??= $access['today'];
        Validator::make(['on_date' => $date], ['on_date' => ['required', 'date_format:Y-m-d']])->validate();
        $y = SessionYear::findOrFail($year);
        abort_unless($date >= $y->start_date && $date <= min($y->end_date, $access['today']), 422, 'Choose a non-future working date inside the current school year.');
        $placements = Enrolment::where('session_year_id', $year)->onDate($date)->whereHas('homeroom.class', fn ($q) => $q->where('grade_level_id', $grade));
        $people = Students::whereIn('students.id', (clone $placements)->select('learner_id'))->whereIn('students.user_id', app(CurrentLearnerMembership::class)->users()->select('users.id'))
            ->with('user:id,first_name,last_name');
        $gate = app(ModuleGate::class);
        $risk = $gate->allows('early_warning');
        $latest = RiskObservation::where('session_year_id', $year)->where('grade_level_id', $grade)->whereIn('source', ['academic', 'attendance'])
            ->whereDate('as_of', '<=', $date)->selectRaw('MAX(id)')->groupBy('learner_id', 'source');
        if ($risk) {
            $scores = RiskObservation::whereIn('id', $latest)->selectRaw('learner_id, SUM(score) AS observed_score')->groupBy('learner_id');
            $people->leftJoinSub($scores, 'my_grade_risk', fn ($join) => $join->on('my_grade_risk.learner_id', '=', 'students.id'))
                ->orderByRaw('my_grade_risk.observed_score IS NULL')->orderByDesc('my_grade_risk.observed_score');
        }
        $people = $people->orderBy('students.id')->paginate(50, ['students.*'], 'page', max(1, $page));
        $ids = $people->pluck('id');
        $placements = (clone $placements)->whereIn('learner_id', $ids)->orderBy('starts_on')->get()->keyBy('learner_id');
        $attendance = AttendanceEntry::whereIn('learner_id', $ids)->whereHas('register', fn ($q) => $q->where('session_year_id', $year)->where('grade_level_id', $grade)
            ->whereDate('on_date', $date)->whereNull('timetable_lesson_id'))->with('register:id,class_section_id')->orderBy('id')->get()
            ->filter(fn ($entry) => (int) $entry->register?->class_section_id === (int) $placements->get($entry->learner_id)?->class_section_id)->keyBy('learner_id');
        $legacy = Attendance::whereIn('student_id', $ids)->where('session_year_id', $year)->whereDate('date', $date)->whereNull('attendance_entry_id')->orderBy('id')->get()->keyBy('student_id');
        $signals = $risk ? RiskObservation::whereIn('id', $latest)->whereIn('learner_id', $ids)->get(['learner_id', 'source', 'score', 'reason_code', 'as_of'])->groupBy('learner_id') : collect();
        $learners = $people->map(function ($learner) use ($placements, $attendance, $legacy, $signals) {
            $entry = $attendance->get($learner->id);
            $old = $legacy->get($learner->id);
            $placement = $placements->get($learner->id);
            $legacyMatches = $old !== null && (int) $old->class_section_id === (int) $placement?->class_section_id;

            return ['learner_id' => (int) $learner->id, 'name' => trim(($learner->user?->first_name ?? '').' '.($learner->user?->last_name ?? '')),
                'class_section_id' => (int) $placement?->class_section_id, 'register_id' => $entry === null ? null : (int) $entry->attendance_register_id,
                'attendance_state' => $entry?->state ?? ($legacyMatches ? app(RegisterLegacy::class)->state((int) $old->type) : 'unrecorded'),
                'signals' => $signals->get($learner->id, collect())->map(fn ($s) => ['source' => $s->source, 'score' => $s->score,
                    'reason_code' => $s->reason_code, 'as_of' => $s->as_of->toDateString()])->values()->all()];
        })->all();
        $discipline = ['available' => $gate->allows('discipline'), 'items' => [], 'page' => 1, 'last_page' => 1, 'total' => null];
        if ($discipline['available']) {
            $queue = DisciplineRecord::select(DisciplineRecords::META)->where('session_year_id', $year)->where('grade_level_id', $grade)
                ->where('status', 'pending_review')->whereDate('occurred_on', '<=', $date)->orderBy('occurred_on')->orderBy('id')->paginate(25, ['*'], 'discipline_page', max(1, $disciplinePage));
            $names = Students::whereIn('id', $queue->pluck('learner_id'))->with('user:id,first_name,last_name')->get()->keyBy('id');
            $discipline = ['available' => true, 'page' => $queue->currentPage(), 'last_page' => $queue->lastPage(), 'total' => $queue->total(),
                'items' => $queue->map(fn ($record) => ['id' => (int) $record->id, 'learner_id' => (int) $record->learner_id, 'revision' => $record->revision,
                    'occurred_on' => $record->occurred_on->toDateString(), 'status' => $record->status,
                    'learner_name' => trim(($names->get($record->learner_id)?->user?->first_name ?? '').' '.($names->get($record->learner_id)?->user?->last_name ?? ''))])->all()];
        }
        $awards = ['available' => $gate->allows('awards'), 'items' => []];
        if ($awards['available']) {
            $awards['items'] = app(AwardNominations::class)->catalogue($actor, $year, $grade)['items'];
        }
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);
        $this->grade($actor, $grade);

        return ['school_id' => $school, 'reader_id' => $access['reader_id'], 'session_year_id' => $year, 'grade_level_id' => $grade, 'on_date' => $date, 'writable' => $access['writable'],
            'page' => $people->currentPage(), 'last_page' => $people->lastPage(), 'total' => $people->total(), 'risk_available' => $risk,
            'learners' => $learners, 'discipline' => $discipline, 'awards' => $awards,
            'actions' => ['registers' => $gate->allows('academics'), 'messages' => $gate->allows('communication'), 'interventions' => $gate->allows('early_warning')]];
    }
}
