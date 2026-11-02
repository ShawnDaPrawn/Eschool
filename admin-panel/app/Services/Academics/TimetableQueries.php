<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\BellPeriod;
use App\Models\Academics\BellSchedule;
use App\Models\Academics\HomeroomAllocation;
use App\Models\Academics\Room;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TimetableLesson;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivitySession;
use App\Models\ClassSection;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\Academics\TimetablePolicy;
use App\Services\Pastoral\CoverTimetable;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;

class TimetableQueries
{
    public function configuration(User $actor, int $year): array
    {
        app(AcademicAuthority::class)->manage($actor);
        SessionYear::findOrFail($year);
        $staff = SchoolMembership::query()->active()->where('school_id', app(CurrentSchool::class)->id())->whereNotIn('role', ['Student', 'Parent'])->pluck('user_id');

        return ['school_years' => SessionYear::orderByDesc('start_date')->get()->map(fn ($year) => ['id' => (int) $year->id, 'name' => $year->name])->all(), 'rooms' => Room::orderBy('name')->get()->map(fn ($room) => $this->room($room))->all(),
            'bell_schedules' => BellSchedule::with('periods')->where('session_year_id', $year)->orderBy('name')->get()->map(fn ($schedule) => $this->bells($schedule))->all(),
            'teachers' => Teacher::with('user')->whereIn('user_id', $staff)->whereHas('user', fn ($q) => $q->where('status', 1))->get()->map(fn ($teacher) => ['id' => (int) $teacher->id, 'name' => trim($teacher->user->first_name.' '.$teacher->user->last_name)])->all(),
            'homerooms' => ClassSection::with('class', 'section')->get()->map(fn ($room) => ['id' => (int) $room->id, 'name' => trim($room->class->name.' '.$room->section->name)])->all()];
    }

    public function room(Room $room): array
    {
        return ['id' => (int) $room->id, 'name' => $room->name, 'building' => $room->building, 'capacity' => $room->capacity,
            'type' => $room->type, 'active' => $room->active, 'revision' => $room->revision];
    }

    public function bells(BellSchedule $schedule): array
    {
        return ['id' => (int) $schedule->id, 'session_year_id' => (int) $schedule->session_year_id, 'name' => $schedule->name, 'section' => $schedule->section,
            'revision' => $schedule->revision, 'periods' => $schedule->periods->map(fn ($period) => ['id' => (int) $period->id, 'name' => $period->name,
                'position' => $period->position, 'starts_at' => substr($period->starts_at, 0, 5), 'ends_at' => substr($period->ends_at, 0, 5), 'is_break' => $period->is_break])->all()];
    }

    public function data(TimetableLesson $lesson, ?string $date = null): array
    {
        $lesson->loadMissing('period.schedule', 'room', 'teacher.user', 'group.subject', 'homeroom.class', 'homeroom.section');

        return ['id' => (int) $lesson->id, 'revision' => $lesson->revision, 'session_year_id' => (int) $lesson->session_year_id,
            'term_id' => $lesson->term_id === null ? null : (int) $lesson->term_id,
            'teaching_group_id' => $lesson->teaching_group_id === null ? null : (int) $lesson->teaching_group_id,
            'class_section_id' => $lesson->class_section_id === null ? null : (int) $lesson->class_section_id,
            'teacher_id' => (int) $lesson->teacher_id, 'teacher_name' => trim(($lesson->teacher?->user?->first_name ?? '').' '.($lesson->teacher?->user?->last_name ?? '')),
            'room_id' => (int) $lesson->room_id, 'room_name' => $lesson->room?->name ?? __('Archived room'), 'building' => $lesson->room?->building,
            'bell_period_id' => (int) $lesson->bell_period_id, 'period_name' => $lesson->period?->name ?? __('Archived period'),
            'starts_at' => substr($lesson->period?->starts_at ?? '', 0, 5), 'ends_at' => substr($lesson->period?->ends_at ?? '', 0, 5),
            'title' => $lesson->group?->name ?? trim(($lesson->homeroom?->class?->name ?? '').' '.($lesson->homeroom?->section?->name ?? '')),
            'subject_name' => $lesson->group?->subject?->name, 'date' => $date, 'weekday' => $lesson->weekday,
            'starts_on' => $lesson->starts_on->toDateString(), 'ends_on' => $lesson->ends_on->toDateString(), 'occurs_on' => $lesson->occurs_on?->toDateString(),
            'week_pattern' => $lesson->week_pattern, 'active' => $lesson->active];
    }

    public function week(User $actor, int $year, string $startsOn, ?int $learner = null): array
    {
        $learner = app(TimetablePolicy::class)->authorise($actor, 'read', $learner);
        SessionYear::findOrFail($year);
        $start = CarbonImmutable::parse($startsOn);
        $end = $start->addDays(6);
        $manager = $learner === null && app(AcademicAuthority::class)->manages($actor);
        $teachers = $learner === null ? app(AcademicAuthority::class)->teachers($actor) : [];
        $groups = $learner === null ? app(AcademicAuthority::class)->groups($actor)->pluck('id')->all() : [];
        $engine = app(TimetableEngine::class);
        $lessons = TimetableLesson::with('period.schedule', 'room', 'teacher.user', 'group.subject', 'homeroom.class', 'homeroom.section')
            ->where('session_year_id', $year)->where('active', true)->whereDate('starts_on', '<=', $end->toDateString())->whereDate('ends_on', '>=', $start->toDateString())->get();
        // Exam mode: a learner's exam days, and whether lessons continue on each.
        $examDays = $learner === null ? [] : app(ExamMode::class)->days($learner, $year, $start->toDateString(), $end->toDateString());
        $rows = [];
        $schedulesOn = [];
        foreach ($lessons as $lesson) {
            if (! $manager && (($lesson->teaching_group_id !== null && $lesson->group?->active !== true) || ($lesson->class_section_id !== null && $lesson->homeroom === null))) {
                continue;
            }
            foreach ($engine->dates($lesson->getAttributes()) as $date) {
                if ($date < $start->toDateString() || $date > $end->toDateString()) {
                    continue;
                }
                $cover = app(CoverTimetable::class)->assigned($lesson, $date);
                if ($learner !== null) {
                    if (($examDays[$date] ?? true) === false || ! in_array($learner, $engine->learners($lesson->getAttributes(), $date), true)) {
                        continue;
                    }
                } elseif (! $manager) {
                    $visible = ($cover !== null && in_array((int) $cover->substitute_teacher_id, $teachers, true)) || in_array((int) $lesson->teacher_id, $teachers, true) || in_array((int) $lesson->teaching_group_id, $groups, true);
                    if (! $visible) {
                        continue;
                    }
                }
                // Cancelled allocations never survive in a stale stored lesson.
                $allocation = $lesson->teaching_group_id !== null ? TeachingAllocation::where('teaching_group_id', $lesson->teaching_group_id)
                    : HomeroomAllocation::where('class_section_id', $lesson->class_section_id)->where('session_year_id', $year);
                if (! $manager && ! $allocation->where('teacher_id', $lesson->teacher_id)->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->exists()) {
                    continue;
                }
                if ($learner !== null && $lesson->period?->bell_schedule_id !== null) {
                    $schedulesOn[$date][(int) $lesson->period->bell_schedule_id] = true;
                }
                // A short day moves the lesson to the replacement bells' period in the same order.
                $row = $engine->patterns()->display($this->data($lesson, $date), (int) $lesson->bell_period_id, $date);
                $rows[] = $cover === null ? $row : app(CoverTimetable::class)->display($row, $cover);
            }
        }
        usort($rows, fn ($a, $b) => [$a['date'], $a['starts_at'], $a['id']] <=> [$b['date'], $b['starts_at'], $b['id']]);

        // A learner's week also shows named breaks and their activity sessions (1.6).
        $breaks = $learner === null ? [] : $this->breaks($schedulesOn, $engine->patterns());
        $activities = $learner === null ? [] : $this->activities($learner, $year, $start->toDateString(), $end->toDateString());

        return ['session_year_id' => $year, 'starts_on' => $start->toDateString(), 'ends_on' => $end->toDateString(), 'can_manage' => $manager, 'lessons' => $rows, 'exam_days' => array_keys($examDays),
            'breaks' => $breaks, 'activities' => $activities];
    }

    /**
     * The named breaks of the bells the learner's lessons follow each day; on a
     * short day, the replacement bells' breaks.
     *
     * @param  array<string, array<int, true>>  $schedulesOn
     * @return list<array{date: string, name: string, starts_at: string, ends_at: string}>
     */
    private function breaks(array $schedulesOn, DayPatterns $patterns): array
    {
        $out = [];
        foreach ($schedulesOn as $date => $schedules) {
            $ids = [];
            foreach (array_keys($schedules) as $id) {
                $override = $patterns->override($id, $date);
                $ids[] = $override === null ? $id : (int) $override->replacement_bell_schedule_id;
            }
            foreach (BellPeriod::query()->whereIn('bell_schedule_id', array_unique($ids))->where('is_break', true)->orderBy('starts_at')->get() as $period) {
                $key = $date.'|'.substr((string) $period->starts_at, 0, 5).'|'.$period->name;
                $out[$key] = ['date' => $date, 'name' => (string) $period->name, 'starts_at' => substr((string) $period->starts_at, 0, 5), 'ends_at' => substr((string) $period->ends_at, 0, 5)];
            }
        }
        ksort($out);

        return array_values($out);
    }

    /**
     * The learner's activity sessions this week: groups they are a current
     * member of on the session's day, not cancelled. A session without times
     * is all day.
     *
     * @return list<array{id: int, date: string, starts_at: ?string, ends_at: ?string, name: string, group_name: string, venue: string, status: string}>
     */
    private function activities(int $learner, int $year, string $from, string $to): array
    {
        if (! app(ModuleGate::class)->allows('groups')) {
            return [];
        }
        $memberships = ActivityMembership::query()->where('learner_id', $learner)->where('active', true)->whereDate('starts_on', '<=', $to)->whereDate('ends_on', '>=', $from)->get();
        if ($memberships->isEmpty()) {
            return [];
        }
        $groups = ActivityGroup::query()->whereIn('id', $memberships->pluck('activity_group_id'))->where('session_year_id', $year)->where('active', true)->get()->keyBy('id');
        $sessions = ActivitySession::query()->whereIn('activity_group_id', $groups->keys())->where('status', '!=', 'cancelled')
            ->whereDate('on_date', '>=', $from)->whereDate('on_date', '<=', $to)->orderBy('on_date')->orderBy('starts_at')->get();
        $out = [];
        foreach ($sessions as $session) {
            $date = $session->on_date->toDateString();
            $member = $memberships->contains(fn ($row) => (int) $row->activity_group_id === (int) $session->activity_group_id
                && $row->starts_on->toDateString() <= $date && $row->ends_on->toDateString() >= $date);
            if (! $member) {
                continue;
            }
            $out[] = ['id' => (int) $session->id, 'date' => $date, 'starts_at' => $session->starts_at === null ? null : substr((string) $session->starts_at, 0, 5),
                'ends_at' => $session->ends_at === null ? null : substr((string) $session->ends_at, 0, 5), 'name' => (string) $session->name,
                'group_name' => (string) $groups[$session->activity_group_id]->name, 'venue' => (string) $session->venue, 'status' => (string) $session->status];
        }

        return $out;
    }

    public function next(User $actor, ?int $learner = null): array
    {
        app(TimetablePolicy::class)->authorise($actor, 'read', $learner);
        $zone = app(CurrentSchool::class)->school()?->timezone ?? 'Africa/Johannesburg';
        $now = CarbonImmutable::now($zone);
        $years = SessionYear::whereDate('start_date', '<=', $now->addDays(6)->toDateString())->whereDate('end_date', '>=', $now->toDateString())->orderBy('id')->get();
        $upcoming = [];
        foreach ($years as $year) {
            $week = $this->week($actor, (int) $year->id, $now->toDateString(), $learner);
            foreach ($week['lessons'] as $lesson) {
                if (CarbonImmutable::parse($lesson['date'].' '.$lesson['starts_at'], $zone) >= $now) {
                    $upcoming[] = $lesson;
                }
            }
        }
        usort($upcoming, fn ($a, $b) => [$a['date'], $a['starts_at'], $a['id']] <=> [$b['date'], $b['starts_at'], $b['id']]);
        if ($upcoming !== []) {
            return ['lesson' => $upcoming[0]];
        }

        return ['lesson' => null];
    }
}
