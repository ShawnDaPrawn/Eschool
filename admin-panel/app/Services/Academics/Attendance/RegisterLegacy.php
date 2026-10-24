<?php

declare(strict_types=1);

namespace App\Services\Academics\Attendance;

use App\Models\Academics\AttendanceRegister;
use App\Models\Attendance;
use Illuminate\Support\Collection;

/** Existing registers remain visible until an explicit canonical capture adopts them. */
class RegisterLegacy
{
    public function rows(AttendanceRegister $register): Collection
    {
        if ($register->timetable_lesson_id !== null) {
            return collect();
        }

        return Attendance::where('session_year_id', $register->session_year_id)->where('class_section_id', $register->class_section_id)
            ->whereDate('date', $register->on_date->toDateString())->whereNull('attendance_entry_id')->orderBy('id')->get()->keyBy('student_id');
    }

    public function state(int $type): string
    {
        return match ($type) {
            Attendance::TYPE_PRESENT => 'present', Attendance::TYPE_FIRST_HALF => 'first_half', Attendance::TYPE_SECOND_HALF => 'second_half',
            Attendance::TYPE_HOLIDAY => 'holiday', default => 'absent',
        };
    }

    public function type(string $state): int
    {
        return match ($state) {
            'present', 'late' => Attendance::TYPE_PRESENT, 'first_half' => Attendance::TYPE_FIRST_HALF, 'second_half' => Attendance::TYPE_SECOND_HALF,
            'holiday' => Attendance::TYPE_HOLIDAY, default => Attendance::TYPE_ABSENT,
        };
    }
}
