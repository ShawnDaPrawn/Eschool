<?php

declare(strict_types=1);

namespace App\Services\Academics\Attendance;

use App\Models\Academics\AttendanceRegister;
use App\Models\Academics\TimetableLesson;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\Students;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\TimetableEngine;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterRosters
{
    /** A transient register is sufficient for authorising choices before creation. */
    public function target(array $input): AttendanceRegister
    {
        $year = SessionYear::findOrFail($input['session_year_id']);
        $date = $input['on_date'];
        if ($date < $year->start_date || $date > $year->end_date || $date > app(AcademicAuthority::class)->today()) {
            throw ValidationException::withMessages(['on_date' => __('Choose a non-future day inside the school year.')]);
        }
        $lessonId = $input['timetable_lesson_id'] ?? null;
        if ($lessonId !== null) {
            $lesson = TimetableLesson::with('group', 'homeroom.class', 'homeroom.section', 'period')->where('session_year_id', $year->id)->findOrFail($lessonId);
            abort_unless($lesson->active && ($lesson->teaching_group_id === null || $lesson->group?->active === true) && in_array($date, app(TimetableEngine::class)->dates($lesson->getAttributes()), true), 422, __('No active lesson occurs on this day.'));
            $grade = $lesson->group?->grade_level_id ?? $lesson->homeroom?->class?->grade_level_id;
            $homeroom = $lesson->class_section_id;
            $scope = 'lesson:'.$lesson->id;
            $label = $lesson->period->name.' · '.($lesson->group?->name ?? trim(($lesson->homeroom?->class?->name ?? '').' '.($lesson->homeroom?->section?->name ?? '')));
        } else {
            $homeroom = ClassSection::with('class', 'section')->findOrFail($input['class_section_id']);
            $grade = $homeroom->class?->grade_level_id;
            $scope = 'homeroom:'.$homeroom->id;
            $label = trim($homeroom->class->name.' '.$homeroom->section->name);
            $homeroom = $homeroom->id;
        }
        abort_unless($grade !== null, 422, __('Map the homeroom to a grade before taking its register.'));

        return new AttendanceRegister(['session_year_id' => (int) $year->id, 'class_section_id' => $homeroom,
            'timetable_lesson_id' => $lessonId, 'grade_level_id' => (int) $grade, 'on_date' => $date, 'scope_key' => $scope, 'label' => Str::limit($label, 255, ''), 'revision' => 0]);
    }

    public function learners(AttendanceRegister $register): Collection
    {
        $date = $register->on_date->toDateString();
        $enrolments = Enrolment::onDate($date)->where('session_year_id', $register->session_year_id);
        if ($register->timetable_lesson_id !== null) {
            $lesson = TimetableLesson::withTrashed()->findOrFail($register->timetable_lesson_id);
            $ids = app(TimetableEngine::class)->learners($lesson->getAttributes(), $date);
            $enrolments->whereIn('learner_id', $ids);
        } else {
            $enrolments->where('class_section_id', $register->class_section_id);
        }

        return Students::with('user')->whereIn('id', $enrolments->pluck('learner_id'))->whereHas('user', fn ($q) => $q->where('status', 1))->get()
            ->sortBy(fn ($learner) => mb_strtolower($learner->user->last_name.' '.$learner->user->first_name))->values();
    }

    public function fingerprint(AttendanceRegister $register): string
    {
        return hash('sha256', json_encode($this->learners($register)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(), JSON_THROW_ON_ERROR));
    }
}
