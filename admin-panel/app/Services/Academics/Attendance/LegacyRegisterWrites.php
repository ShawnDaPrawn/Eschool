<?php

declare(strict_types=1);

namespace App\Services\Academics\Attendance;

use App\Models\Academics\AttendanceRegister;
use App\Models\Platform\School;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

class LegacyRegisterWrites
{
    public function run(callable $write): mixed
    {
        return DB::transaction(function () use ($write) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();

            return $write();
        });
    }

    public function reject(int $homeroom, int $year, string $date): void
    {
        abort_if(AttendanceRegister::where('class_section_id', $homeroom)->where('session_year_id', $year)->whereDate('on_date', $date)
            ->whereNull('timetable_lesson_id')->whereHas('entries')->exists(), 409, __('Use the academic register to amend this attendance.'));
    }
}
