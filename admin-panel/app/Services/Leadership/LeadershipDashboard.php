<?php

declare(strict_types=1);

namespace App\Services\Leadership;

use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\GradeLevel;
use App\Models\Attendance;
use App\Models\Leadership\LearnerActivityDay;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\Attendance\RegisterLegacy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class LeadershipDashboard
{
    public function read(User $actor, int $yearId, int $weeks = 8, ?string $through = null): array
    {
        $school = app(CurrentSchool::class)->id();
        $authority = app(LeadershipAuthority::class);
        $authority->principal($actor, $yearId);
        $year = SessionYear::findOrFail($yearId);
        abort_unless($year->start_date && $year->end_date && $weeks >= 1 && $weeks <= 12, 422, 'Choose a dated school year and up to twelve weeks.');
        $today = app(AcademicAuthority::class)->today();
        $end = $through ?? min($today, CarbonImmutable::parse($year->end_date)->toDateString());
        abort_unless($end >= CarbonImmutable::parse($year->start_date)->toDateString() && $end <= min($today, CarbonImmutable::parse($year->end_date)->toDateString()), 422, 'Choose a date within this school year and no later than today.');
        $start = max(CarbonImmutable::parse($end)->subDays($weeks * 7 - 1)->toDateString(), CarbonImmutable::parse($year->start_date)->toDateString());
        $enrolments = Enrolment::with('homeroom.class')->where('session_year_id', $yearId)->whereDate('starts_on', '<=', $end)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $start))->orderBy('id')->get();
        $gradeIds = $enrolments->map(fn ($e) => $e->homeroom?->class?->grade_level_id)->filter()->unique()->all();
        $grades = GradeLevel::whereIn('id', $gradeIds)->orderBy('sort_order')->get();
        $events = $this->attendance($yearId, $start, $end, $enrolments);
        $feeCollections = app(LeadershipFeeCollections::class);
        $fees = $feeCollections->read($actor, $yearId, $start, $end);
        $observedSince = LearnerActivityDay::where('session_year_id', $yearId)->min('on_date');
        $activity = LearnerActivityDay::where('session_year_id', $yearId)->whereBetween('on_date', [$start, $end])->get();
        $periods = [];
        for ($cursor = CarbonImmutable::parse($start); $cursor->toDateString() <= $end; $cursor = $stop->addDay()) {
            $stop = $cursor->addDays(6)->min(CarbonImmutable::parse($end));
            $from = $cursor->toDateString();
            $to = $stop->toDateString();
            $rosters = [];
            foreach ($grades as $grade) {
                $rosters[$grade->id] = $enrolments->filter(fn ($e) => $e->homeroom?->class?->grade_level_id === $grade->id && $e->starts_on->toDateString() <= $to && ($e->ends_on === null || $e->ends_on->toDateString() >= $to))->pluck('learner_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
            }
            $signals = app(LeadershipSignals::class)->counts($actor, $yearId, $from, $to, $rosters);
            $rows = [];
            foreach ($grades as $grade) {
                $daily = array_values(array_filter($events, fn ($e) => $e['grade_id'] === $grade->id && $e['date'] >= $from && $e['date'] <= $to));
                $present = array_sum(array_column($daily, 'present_half_days'));
                $days = array_sum(array_column($daily, 'recorded_days'));
                $risk = $signals[$grade->id]['academic_risk'] ?? null;
                $discipline = $signals[$grade->id]['discipline'] ?? null;
                $useKnown = $observedSince !== null && $to >= $observedSince;
                $active = $useKnown ? $activity->filter(fn ($r) => (int) $r->grade_level_id === $grade->id && $r->on_date->toDateString() >= $from && $r->on_date->toDateString() <= $to)->pluck('learner_id')->unique()->count() : null;
                $rows[] = ['grade_id' => (int) $grade->id, 'grade_name' => $grade->name, 'enrolled_learners' => count($rosters[$grade->id]),
                    'attendance' => ['status' => $days > 0 ? 'available' : 'unavailable', 'recorded_days' => $days, 'present_half_days' => $present, 'percentage_bp' => $days > 0 ? intdiv($present * 10000 + $days, $days * 2) : null],
                    'academic_risk' => $this->metric($risk), 'discipline' => $this->metric($discipline), 'fee_collection_cents' => $this->metric($feeCollections->amount($fees, (int) $grade->id, $from, $to)),
                    'fee_collection_currency' => $feeCollections->currency($fees, (int) $grade->id, $from, $to),
                    'active_learners' => ['status' => ! $useKnown ? 'unavailable' : ($from < $observedSince ? 'partial' : 'available'), 'value' => $active]];
            }
            $periods[] = ['starts_on' => $from, 'ends_on' => $to, 'grades' => $rows];
        }
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);
        $authority->principal($actor, $yearId);

        return ['school_id' => $school, 'session_year_id' => $yearId, 'starts_on' => $start, 'ends_on' => $end, 'activity_observed_since' => $observedSince, 'periods' => $periods];
    }

    private function metric(?int $value): array
    {
        return ['status' => $value === null ? 'unavailable' : 'available', 'value' => $value];
    }

    /** One physical school day per learner; canonical records replace legacy mirrors. */
    private function attendance(int $year, string $start, string $end, Collection $enrolments): array
    {
        $scope = fn ($q) => $q->where('session_year_id', $year)->whereNull('timetable_lesson_id')->whereBetween('on_date', [$start, $end]);
        $days = [];
        foreach (Attendance::where('session_year_id', $year)->whereBetween('date', [$start, $end])->lazyById(1000) as $row) {
            $date = substr((string) $row->date, 0, 10);
            $days[$row->student_id.':'.$date] = ['learner_id' => (int) $row->student_id, 'class_section_id' => (int) $row->class_section_id, 'date' => $date, 'state' => app(RegisterLegacy::class)->state((int) $row->type)];
        }
        foreach (AttendanceEntry::with('register')->whereHas('register', $scope)->lazyById(1000) as $entry) {
            $date = $entry->register->on_date->toDateString();
            $days[$entry->learner_id.':'.$date] = ['learner_id' => (int) $entry->learner_id, 'class_section_id' => (int) $entry->register->class_section_id, 'date' => $date, 'state' => $entry->state];
        }
        $byLearner = $enrolments->groupBy('learner_id');
        $events = [];
        foreach ($days as $day) {
            if ($day['state'] === 'holiday') {
                continue;
            }
            $eligible = ($byLearner->get($day['learner_id']) ?? collect())->filter(fn ($e) => $e->starts_on->toDateString() <= $day['date'] && ($e->ends_on === null || $e->ends_on->toDateString() >= $day['date']));
            if ($eligible->count() !== 1 || (int) $eligible->sole()->class_section_id !== $day['class_section_id']) {
                continue;
            }
            $grade = $eligible->sole()->homeroom?->class?->grade_level_id;
            if ($grade === null) {
                continue;
            }
            $key = $grade.':'.$day['date'];
            $events[$key] ??= ['grade_id' => (int) $grade, 'date' => $day['date'], 'recorded_days' => 0, 'present_half_days' => 0];
            $events[$key]['recorded_days']++;
            $events[$key]['present_half_days'] += match ($day['state']) {
                'present', 'late' => 2, 'first_half', 'second_half' => 1, default => 0
            };
        }

        return $events;
    }
}
