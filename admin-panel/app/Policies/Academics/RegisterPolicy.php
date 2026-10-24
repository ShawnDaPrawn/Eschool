<?php

declare(strict_types=1);

namespace App\Policies\Academics;

use App\Models\Academics\AttendanceRegister;
use App\Models\Academics\HomeroomAllocation;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TimetableLesson;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;

class RegisterPolicy
{
    /** Rights are checked again even when replaying a previously completed operation. */
    public function authorise(User $actor, AttendanceRegister $register, bool $write = false): void
    {
        $authority = app(AcademicAuthority::class);
        $fresh = $authority->staff($actor);
        $today = $authority->today();
        $date = $register->on_date->toDateString();
        abort_if($write && $date > $today, 422, __('Future attendance cannot be captured.'));
        if ($authority->manages($fresh) || $authority->leads($fresh, 'grade_head', (int) $register->grade_level_id, (int) $register->session_year_id)) {
            return;
        }
        // A principal needs a grade-head appointment or academic management permission
        // to amend attendance: the Bible explicitly assigns corrections to grade heads.
        abort_if($write && $date !== $today, 403, __('Ask the grade head to correct a previous day.'));
        $teachers = $authority->teachers($fresh);
        if ($register->timetable_lesson_id !== null) {
            $lesson = TimetableLesson::findOrFail($register->timetable_lesson_id);
            abort_unless($lesson->active && in_array((int) $lesson->teacher_id, $teachers, true), 403);
            if ($lesson->teaching_group_id !== null) {
                $query = TeachingAllocation::where('teaching_group_id', $lesson->teaching_group_id)->whereHas('group', fn ($q) => $q->where('active', true));
            } else {
                $query = HomeroomAllocation::where('class_section_id', $lesson->class_section_id)->where('session_year_id', $register->session_year_id);
            }
        } else {
            $query = HomeroomAllocation::where('class_section_id', $register->class_section_id)->where('session_year_id', $register->session_year_id);
        }
        abort_unless($query->whereIn('teacher_id', $teachers)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->exists(), 403);
    }
}
