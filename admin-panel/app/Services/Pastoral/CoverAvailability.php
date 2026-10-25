<?php

declare(strict_types=1);

namespace App\Services\Pastoral;

use App\Models\Academics\TimetableLesson;
use App\Models\Pastoral\CoverAbsence;
use App\Models\Pastoral\CoverAssignment;
use App\Services\Academics\TimetableEngine;
use App\Services\Academics\TimetableProjection;
use App\Services\Platform\Tenancy\SchoolTable;
use Carbon\CarbonImmutable;

class CoverAvailability
{
    /** Availability candidates deliberately carry no roster or room identity. */
    public function candidate(int $teacher, TimetableLesson $lesson, string $date): array
    {
        return ['active' => true, 'session_year_id' => (int) $lesson->session_year_id, 'term_id' => $lesson->term_id,
            'teacher_id' => $teacher, 'bell_period_id' => (int) $lesson->bell_period_id,
            'teaching_group_id' => null, 'class_section_id' => null, 'room_id' => 0,
            'starts_on' => $date, 'ends_on' => $date, 'occurs_on' => $date,
            'weekday' => (int) CarbonImmutable::parse($date)->isoWeekday(), 'week_pattern' => 'all'];
    }

    public function ownBusy(int $teacher, TimetableLesson $lesson, string $date): bool
    {
        $row = $this->candidate($teacher, $lesson, $date);
        $engine = app(TimetableEngine::class);
        foreach (TimetableLesson::where('teacher_id', $teacher)->where('active', true)->get() as $other) {
            if ($engine->clashes($row, $other->getAttributes(), 0) !== []) {
                return true;
            }
        }

        return app(TimetableProjection::class)->legacyClashes($row, 0, $engine) !== [];
    }

    public function assignable(int $teacher, CoverAssignment $cover, TimetableLesson $lesson): bool
    {
        $date = $cover->on_date->toDateString();
        if ($teacher === (int) $lesson->teacher_id || $this->ownBusy($teacher, $lesson, $date)
            || CoverAbsence::where('teacher_id', $teacher)->where('state', 'requested')->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->exists()) {
            return false;
        }
        $engine = app(TimetableEngine::class);
        $candidate = $this->candidate($teacher, $lesson, $date);
        foreach (CoverAssignment::where('substitute_teacher_id', $teacher)->whereDate('on_date', $date)->where('state', 'assigned')->where('id', '!=', $cover->id)->get() as $other) {
            if (! app(CoverSources::class)->valid($other)) {
                continue;
            }
            $otherLesson = TimetableLesson::findOrFail($other->timetable_lesson_id);
            if ($engine->clashes($candidate, $this->candidate($teacher, $otherLesson, $date), 0) !== []) {
                return false;
            }
        }

        return true;
    }

    /** Never silently omit an unmapped legacy lesson belonging to the absent teacher. */
    public function hasUnmapped(int $teacher, int $year, string $from, string $to): bool
    {
        $weekdays = [];
        for ($date = CarbonImmutable::parse($from); $date->toDateString() <= $to; $date = $date->addDay()) {
            $weekdays[] = $date->isoWeekday();
        }
        $subjects = SchoolTable::query('subject_teachers')->where('teacher_id', $teacher)->pluck('id')->all();

        return SchoolTable::query('timetables')->whereNull('timetable_lesson_id')->whereNull('deleted_at')
            ->where('session_year_id', $year)->whereIn('subject_teacher_id', $subjects)->whereIn('day', array_unique($weekdays))->exists();
    }
}
