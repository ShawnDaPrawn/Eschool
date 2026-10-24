<?php

declare(strict_types=1);

namespace App\Services\Academics\Reports;

use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Attendance;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Term;
use App\Services\Academics\Attendance\RegisterLegacy;
use App\Services\Academics\MarkDefinitions;
use App\Services\Academics\MarkSnapshots;
use App\Services\Platform\Tenancy\CurrentSchool;
/** Canonical marks and dated physical enrolments, never a fee/account-status gate. */ use Carbon\CarbonImmutable;

class ReportEvidence
{
    public function collect(array $selection): array
    {
        $year = SessionYear::findOrFail($selection['session_year_id']);
        $room = ClassSection::with('class', 'section')->findOrFail($selection['class_section_id']);
        $grade = GradeLevel::findOrFail($room->class?->grade_level_id);
        $term = $selection['term_id'] === null ? null : Term::where('session_year_id', $year->id)->forGrade((int) $grade->sort_order)->findOrFail($selection['term_id']);
        abort_if(! $year->start_date || ! $year->end_date, 409, __('Set the school year dates before reporting.'));
        $start = CarbonImmutable::parse($term?->start_date ?? $year->start_date)->toDateString();
        $end = CarbonImmutable::parse($term?->end_date ?? $year->end_date)->toDateString();
        $ids = array_map('intval', $selection['learner_ids']);
        sort($ids);
        $enrolments = Enrolment::where('session_year_id', $year->id)->where('class_section_id', $room->id)->whereDate('starts_on', '<=', $end)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $start))->whereIn('learner_id', $ids)->orderBy('id')->get();
        abort_unless(array_diff($ids, $enrolments->pluck('learner_id')->map(fn ($id) => (int) $id)->all()) === [], 404);
        $learners = Students::with('user')->whereHas('user')->whereIn('id', $ids)->get()->keyBy('id');
        abort_unless(count($learners) === count($ids), 404);
        $groups = TeachingGroup::with(['subject' => fn ($q) => $q->withTrashed(), 'memberships' => fn ($q) => $q->whereIn('learner_id', $ids)->whereDate('starts_on', '<=', $end)->whereDate('ends_on', '>=', $start)])
            ->where('session_year_id', $year->id)->where('grade_level_id', $grade->id)
            ->whereHas('memberships', fn ($q) => $q->whereIn('learner_id', $ids)->whereDate('starts_on', '<=', $end)->whereDate('ends_on', '>=', $start))->orderBy('id')->get();
        $prepared = [];
        foreach ($groups->unique('subject_id') as $group) {
            $plans = app(MarkDefinitions::class)->plans($group);
            $defined = $term === null ? $plans->isNotEmpty() : $plans->contains('term_id', $term->id);
            if ($defined) {
                app(MarkSnapshots::class)->recompute((int) $group->id, $term === null ? null : (int) $term->id);
            }
            $prepared[(int) $group->subject_id] = ['group' => $group, 'defined' => $defined];
        }
        $cards = [];
        $can = true;
        foreach ($ids as $id) {
            $learner = $learners->get($id);
            $subjects = [];
            foreach ($prepared as $subjectId => $context) {
                if (! $groups->where('subject_id', $subjectId)->contains(fn ($g) => $g->memberships->contains('learner_id', $id))) {
                    continue;
                }
                $snapshot = $context['defined'] ? app(MarkSnapshots::class)->query($context['group'], $id, $term === null ? null : (int) $term->id)->orderByDesc('version')->first() : null;
                $running = $selection['mode'] === 'running';
                $percentage = $running ? $snapshot?->running_bp : $snapshot?->percentage_bp;
                $ready = $snapshot !== null && ($running || ($snapshot->complete && $percentage !== null));
                $dispositions = $snapshot === null ? [] : array_column($snapshot->inputs['tasks'], 'disposition');
                $all = fn ($state) => $dispositions !== [] && array_diff($dispositions, [$state]) === [];
                $status = $snapshot === null ? 'unavailable' : ($all('excused') ? 'excused' : ($all('absent') ? 'absent' : ($percentage === null ? (in_array('catch_up', $dispositions, true) ? 'catch_up' : 'ungraded') : ($snapshot->complete ? 'complete' : 'partial'))));
                $subjects[] = ['subject_id' => $subjectId, 'subject_name' => $context['group']->subject?->name ?? __('Archived subject'), 'snapshot_id' => $snapshot?->id,
                    'fingerprint' => $snapshot?->fingerprint, 'percentage_bp' => $percentage,
                    'rating_level' => $running ? ($snapshot?->result['running_rating_level'] ?? null) : $snapshot?->rating_level,
                    'coverage_bp' => $snapshot?->coverage_bp ?? 0, 'complete' => $snapshot?->complete ?? false, 'ready' => $ready, 'result_status' => $status];
            }
            usort($subjects, fn ($a, $b) => $a['subject_id'] <=> $b['subject_id']);
            $ready = count($subjects) > 0 && ! in_array(false, array_column($subjects, 'ready'), true);
            $can = $can && $ready;
            $cards[] = ['learner_id' => $id, 'learner_name' => trim($learner->user->first_name.' '.$learner->user->last_name),
                'school_name' => app(CurrentSchool::class)->school()->name, 'year_name' => $year->name, 'term_name' => $term?->name,
                'homeroom_name' => trim($room->class->name.' '.$room->section->name), 'grade_name' => $grade->name,
                'session_year_id' => (int) $year->id, 'term_id' => $term === null ? null : (int) $term->id, 'class_section_id' => (int) $room->id, 'grade_level_id' => (int) $grade->id,
                'starts_on' => $start, 'ends_on' => $end, 'mode' => $selection['mode'], 'provisional' => $selection['mode'] === 'running', 'ready' => $ready,
                'subjects' => $subjects, 'attendance' => $this->attendance($id, (int) $year->id, $start, $end),
                'enrolments' => $enrolments->where('learner_id', $id)->map(fn ($e) => ['id' => (int) $e->id, 'starts_on' => $e->starts_on->toDateString(), 'ends_on' => $e->ends_on?->toDateString()])->values()->all()];
        }

        return ['can_prepare' => $can, 'cards' => $cards, 'fingerprint' => hash('sha256', json_encode($cards, JSON_THROW_ON_ERROR)), 'grade_level_id' => (int) $grade->id];
    }

    private function attendance(int $learner, int $year, string $start, string $end): array
    {
        $days = [];
        foreach (Attendance::where('student_id', $learner)->where('session_year_id', $year)->whereBetween('date', [$start, $end])->orderBy('id')->get() as $row) {
            $days[substr((string) $row->date, 0, 10)] = ['state' => app(RegisterLegacy::class)->state((int) $row->type), 'revision' => null];
        }
        foreach (AttendanceEntry::with('register')->where('learner_id', $learner)->whereHas('register', fn ($q) => $q->where('session_year_id', $year)->whereNull('timetable_lesson_id')->whereBetween('on_date', [$start, $end]))->orderBy('id')->get() as $row) {
            $days[$row->register->on_date->toDateString()] = ['state' => $row->state, 'revision' => $row->revision];
        }
        ksort($days);
        $present = $absent = $late = $excused = $recorded = 0;
        foreach ($days as $day) {
            $state = $day['state'];
            if ($state === 'holiday') {
                continue;
            }
            $recorded++;
            if (in_array($state, ['present', 'late'], true)) {
                $present += 2;
            } elseif (in_array($state, ['first_half', 'second_half'], true)) {
                $present++;
                $absent++;
            } elseif ($state === 'excused') {
                $excused++;
            } else {
                $absent += 2;
            }
            if ($state === 'late') {
                $late++;
            }
        }

        return ['days_recorded' => $recorded, 'present_half_days' => $present, 'absent_half_days' => $absent, 'late_days' => $late, 'excused_days' => $excused, 'evidence' => array_map(fn ($date, $day) => ['on_date' => $date] + $day, array_keys($days), array_values($days))];
    }
}
