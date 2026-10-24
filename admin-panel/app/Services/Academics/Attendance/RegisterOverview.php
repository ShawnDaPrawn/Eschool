<?php

declare(strict_types=1);

namespace App\Services\Academics\Attendance;

use App\Models\Academics\AttendanceRegister;
use App\Models\Attendance;
use App\Models\People\Enrolment;
use App\Models\Students;

class RegisterOverview
{
    public function rows(array $choices): array
    {
        $ids = array_column($choices['homerooms'], 'id');
        $enrolments = Enrolment::onDate($choices['on_date'])->where('session_year_id', $choices['session_year_id'])->whereIn('class_section_id', $ids)->get();
        $learners = Students::whereIn('id', $enrolments->pluck('learner_id'))->whereHas('user')->pluck('id')->all();
        $rosters = $enrolments->whereIn('learner_id', $learners)->groupBy('class_section_id');
        $registers = AttendanceRegister::with('entries')->where('session_year_id', $choices['session_year_id'])->whereDate('on_date', $choices['on_date'])->whereNull('timetable_lesson_id')->whereIn('class_section_id', $ids)->get()->keyBy('class_section_id');
        $legacy = Attendance::where('session_year_id', $choices['session_year_id'])->whereDate('date', $choices['on_date'])->whereIn('class_section_id', $ids)->whereNull('attendance_entry_id')->orderBy('id')->get()->groupBy('class_section_id');
        $rows = [];
        foreach ($choices['homerooms'] as $room) {
            $eligible = $rosters->get($room['id'], collect())->pluck('learner_id')->unique()->all();
            $recorded = [];
            foreach ($legacy->get($room['id'], collect()) as $entry) {
                if (in_array((int) $entry->student_id, array_map('intval', $eligible), true)) {
                    $recorded[(int) $entry->student_id] = app(RegisterLegacy::class)->state((int) $entry->type);
                }
            }
            $register = $registers->get($room['id']);
            foreach ($register?->entries ?? [] as $entry) {
                if (in_array((int) $entry->learner_id, array_map('intval', $eligible), true)) {
                    $recorded[(int) $entry->learner_id] = $entry->state;
                }
            }
            $rows[] = $room + ['register_id' => $register?->id, 'expected' => count($eligible), 'recorded' => count($recorded), 'absent' => count(array_filter($recorded, fn ($state) => $state === 'absent')),
                'missing' => max(0, count($eligible) - count($recorded))];
        }

        return $rows;
    }
}
