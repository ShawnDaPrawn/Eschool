<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\AttendanceRegister;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\MarkSnapshot;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\RiskObservation;
use App\Models\Leave;
use App\Models\People\Enrolment;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Term;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\Attendance\RegisterRosters;
use App\Services\Academics\MarkDefinitions;
use App\Services\Academics\MarkSnapshots;
use App\Services\Discipline\DisciplineMetrics;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Current-day observed facts, never retrospective reconstruction or model inference. */
class RiskRecompute
{
    public static function mutexName(int $school, int $year, int $grade): string
    {
        return 'risk:'.substr(hash('sha256', $school.':'.$year.':'.$grade), 0, 48);
    }

    public function grade(int $yearId, int $gradeId): int
    {
        app(RiskAuthority::class)->enabled();
        $schools = app(CurrentSchool::class);
        $school = $schools->id();
        $connection = DB::connection();
        $mutex = self::mutexName($school, $yearId, $gradeId);
        $lock = $connection->selectOne('SELECT GET_LOCK(?, 10) AS acquired, CONNECTION_ID() AS connection_id', [$mutex], false);
        abort_unless((int) $lock->acquired === 1, 503, 'Early-warning refresh is already running. Retry shortly.');
        $connectionId = (int) $lock->connection_id;
        $verify = function () use ($connection, $mutex, $connectionId, $schools, $school) {
            abort_unless($schools->isSet() && $schools->id() === $school, 403);
            $owner = $connection->selectOne('SELECT IS_USED_LOCK(?) AS owner, CONNECTION_ID() AS connection_id', [$mutex], false);
            abort_unless((int) $owner->owner === $connectionId && (int) $owner->connection_id === $connectionId, 503, 'The refresh connection changed. Retry shortly.');
        };
        try {
            // A connection-scoped mutex orders evidence snapshots without a
            // long school-row transaction that would block mark capture.
            return $schools->run($school, function () use ($yearId, $gradeId, $verify) {
                $result = $this->evaluateGrade($yearId, $gradeId, $verify);
                $verify();

                return $result;
            });
        } finally {
            $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', [$mutex], false);
        }
    }

    private function evaluateGrade(int $yearId, int $gradeId, callable $verify): int
    {
        $verify();
        app(RiskAuthority::class)->enabled();
        $today = app(AcademicAuthority::class)->today();
        $year = SessionYear::findOrFail($yearId);
        $grade = GradeLevel::findOrFail($gradeId);
        abort_unless($yearId === (int) schoolSetting('session_year') && $today >= $year->start_date && $today <= $year->end_date, 422, 'Observe only the current school day inside its school year.');
        $start = max($year->start_date, CarbonImmutable::parse($today)->subDays(29)->toDateString());
        $term = Term::where('session_year_id', $yearId)->forGrade((int) $grade->sort_order)->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();
        if ($term !== null) {
            foreach (TeachingGroup::where('active', true)->where('session_year_id', $yearId)->where('grade_level_id', $gradeId)->get() as $group) {
                if (app(MarkDefinitions::class)->plans($group)->contains('term_id', $term->id)) {
                    app(MarkSnapshots::class)->recompute((int) $group->id, (int) $term->id);
                }
            }
        }
        $rules = app(RiskSettingsService::class)->values();
        // One instance per run, so the calendar and sources in use are read once.
        $contacts = app(PositiveContacts::class);
        $verify();
        $count = 0;
        Students::whereIn('id', Enrolment::where('session_year_id', $yearId)->onDate($today)->select('learner_id'))->whereHas('user')->select('id', 'user_id')
            ->chunkById(100, function ($learners) use ($yearId, $gradeId, $today, $start, $term, $rules, $verify, $contacts, &$count) {
                foreach ($learners as $learner) {
                    try {
                        $currentGrade = app(RiskAuthority::class)->currentGrade((int) $learner->id, $yearId);
                    } catch (ModelNotFoundException) {
                        continue; // An ended enrolment must not stop other learners.
                    } catch (HttpExceptionInterface $error) {
                        if ($error->getStatusCode() !== 422) {
                            throw $error;
                        }

                        continue; // Unmapped grades remain unknown, never inferred as zero.
                    }
                    if ($currentGrade !== $gradeId) {
                        continue;
                    }
                    $academic = $this->academic((int) $learner->id, $yearId, $gradeId, $term?->id, $rules);
                    $attendance = $this->attendance($learner, $yearId, $start, $today, $rules);
                    $unknown = ['score' => null, 'signal_count' => null, 'reason_code' => 'source_unavailable', 'evidence' => []];
                    // "No child unseen" (Action Plan 4.5): positive contact from non-clinical sources only.
                    $unseen = $contacts->observed((int) $learner->id, $yearId, $today);
                    foreach (['academic' => $academic, 'attendance' => $attendance, 'discipline' => app(DisciplineMetrics::class)->observed((int) $learner->id, $yearId, $gradeId, $start, $today), 'wellbeing' => $unknown, 'unseen' => $unseen] as $source => $facts) {
                        $this->record((int) $learner->id, $yearId, $gradeId, $today, $start, $source, $facts, $rules, $verify);
                    }
                    // A recorded positive contact closes the named-adult request.
                    DB::transaction(function () use ($learner, $yearId, $unseen, $verify) {
                        $verify();
                        app(UnseenLearners::class)->clearSeen((int) $learner->id, $yearId, $unseen['status']);
                    });
                    $count++;
                }
            });

        return $count;
    }

    private function academic(int $learner, int $year, int $grade, ?int $term, array $rules): array
    {
        $today = app(AcademicAuthority::class)->today();
        $subjects = [];
        if ($term !== null) {
            foreach (TeachingGroup::where('active', true)->where('session_year_id', $year)->where('grade_level_id', $grade)
                ->whereIn('id', TeachingGroupMembership::where('learner_id', $learner)->whereDate('starts_on', '<=', $today)
                    ->whereDate('ends_on', '>=', $today)->select('teaching_group_id'))->get() as $group) {
                if (app(MarkDefinitions::class)->plans($group)->contains('term_id', $term)) {
                    $subjects[] = (int) $group->subject_id;
                }
            }
        }
        $rows = $term === null ? collect() : MarkSnapshot::select('id', 'subject_id', 'running_bp', 'coverage_bp', 'version')
            ->whereIn('subject_id', $subjects)
            ->where('learner_id', $learner)->where('session_year_id', $year)->where('grade_level_id', $grade)->where('period_key', 'term:'.$term)
            ->orderByDesc('version')->get()->unique('subject_id')->filter(fn ($row) => $row->running_bp !== null && $row->coverage_bp >= $rules['minimum_coverage_bp']);
        $weak = $rows->filter(fn ($row) => $row->running_bp < $rules['academic_threshold_bp'])->count();

        return ['score' => $rows->isEmpty() ? null : min(100, 25 * $weak), 'signal_count' => $rows->isEmpty() ? null : $weak,
            'reason_code' => $rows->isEmpty() ? 'insufficient_evidence' : ($weak > 0 ? 'academic_below_threshold' : 'no_observed_signal'),
            'evidence' => ['eligible_subjects' => $rows->count(), 'below_threshold' => $weak, 'threshold_bp' => $rules['academic_threshold_bp'], 'snapshot_ids' => $rows->pluck('id')->sort()->values()->all()]];
    }

    private function attendance(Students $learner, int $year, string $start, string $end, array $rules): array
    {
        $registers = AttendanceRegister::where('session_year_id', $year)->whereNull('timetable_lesson_id')->whereBetween('on_date', [$start, $end])
            ->whereIn('id', AttendanceEntry::where('learner_id', $learner->id)->select('attendance_register_id'))->orderBy('on_date')->get();
        $days = [];
        foreach ($registers as $register) {
            if (! app(RegisterRosters::class)->learners($register)->contains('id', $learner->id)) {
                continue;
            }
            $entry = AttendanceEntry::where('attendance_register_id', $register->id)->where('learner_id', $learner->id)->select('state')->first();
            if ($entry === null || $entry->state === 'holiday') {
                continue;
            }
            $date = $register->on_date->toDateString();
            $leave = Leave::approvedTypesForDate($date)[$learner->user_id] ?? null;
            $days[$date] = $leave !== null ? 0 : match ($entry->state) {
                'absent' => 2, 'first_half', 'second_half' => 1, default => 0,
            };
        }
        $units = array_sum($days);
        $enough = count($days) >= $rules['minimum_registers'];
        $signal = $enough && $units * 10000 >= count($days) * 2 * $rules['absence_threshold_bp'];

        return ['score' => ! $enough ? null : ($signal ? min(100, intdiv($units * 100, count($days) * 2)) : 0),
            'signal_count' => ! $enough ? null : ($signal ? $units : 0),
            'reason_code' => ! $enough ? 'insufficient_evidence' : ($signal ? 'recorded_absence_threshold' : 'no_observed_signal'),
            'evidence' => ['recorded_days' => count($days), 'absence_half_units' => $units, 'threshold_bp' => $rules['absence_threshold_bp']]];
    }

    private function record(int $learner, int $year, int $grade, string $today, string $start, string $source, array $facts, array $rules, callable $verify): void
    {
        DB::transaction(function () use ($learner, $year, $grade, $today, $start, $source, $facts, $rules, $verify) {
            $verify();
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            app(RiskAuthority::class)->enabled();
            try {
                if (app(RiskAuthority::class)->currentGrade($learner, $year) !== $grade) {
                    return;
                }
            } catch (ModelNotFoundException) {
                return;
            } catch (HttpExceptionInterface $error) {
                if ($error->getStatusCode() === 422) {
                    return;
                }
                throw $error;
            }
            $evidence = $facts['evidence'] + ['settings_revision' => $rules['revision'], 'rule_version' => $rules['rule_version']];
            $fingerprint = hash('sha256', json_encode([$facts['score'], $facts['signal_count'], $facts['reason_code'], $evidence], JSON_THROW_ON_ERROR));
            $identity = ['learner_id' => $learner, 'session_year_id' => $year, 'grade_level_id' => $grade, 'as_of' => $today, 'source' => $source];
            $latest = RiskObservation::where($identity)->latest('id')->first();
            // Only an immediately repeated observation is a replay. A -> B -> A
            // is a genuine later transition and must retain its own ordered row.
            if ($latest === null || ! hash_equals($latest->fingerprint, $fingerprint)) {
                RiskObservation::create($identity + ['fingerprint' => $fingerprint, 'window_start' => $start,
                    'score' => $facts['score'], 'signal_count' => $facts['signal_count'], 'reason_code' => $facts['reason_code'],
                    'evidence' => $evidence, 'created_at' => now()]);
            }
            $verify();
        });
    }
}
