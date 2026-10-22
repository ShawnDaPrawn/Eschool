<?php

declare(strict_types=1);

namespace App\Services\Panel;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\ClassSection;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StudentSessions;
use App\Models\User;

/**
 * Today's numbers on Home for the selected year: learners enrolled, staff
 * present (from the staff register), homerooms whose register was taken today,
 * and notices. Each number shows only to people who may open the page behind
 * it. Fees stay in the fees widget, which works them out per learner.
 */
class TodayNumbers
{
    /** @return list<array{key:string, label:string, value:string, note:?string, url:?string}> */
    public function forUser(User $user, int $sessionYearId): array
    {
        $today = now(schoolSetting('time_zone', config('app.timezone')))->toDateString();
        $numbers = [];

        $learners = StudentSessions::where('session_year_id', $sessionYearId)->where('status', 1)->distinct()->count('student_id');
        $numbers[] = $this->number('learners', __('today_learners'), number_format($learners), null,
            $user->canAny(['student-list', 'class-teacher']) ? route('students.index') : null);

        if ($user->can('attendance-staff')) {
            $staff = Staff::count();
            $marked = StaffAttendance::where('date', $today)->count();
            // A half day still counts as at school today.
            $present = StaffAttendance::where('date', $today)->whereIn('type', ['present', 'first_half', 'second_half'])->count();
            $numbers[] = $this->number('staff', __('today_staff_present'),
                $marked > 0 ? __('today_of', ['count' => $present, 'total' => $staff]) : '–',
                $marked > 0 ? null : __('today_staff_register_not_taken'),
                route('attendance.staff_attendance'));
        }

        if ($user->canAny(['attendance-report', 'attendance-list', 'class-teacher'])) {
            $homerooms = ClassSection::count();
            $taken = Attendance::where('session_year_id', $sessionYearId)->where('date', $today)->distinct()->count('class_section_id');
            $numbers[] = $this->number('attendance', __('today_registers_taken'),
                __('today_of', ['count' => $taken, 'total' => $homerooms]),
                $taken < $homerooms ? trans_choice('today_registers_missing', $homerooms - $taken, ['count' => $homerooms - $taken]) : null,
                $user->can('attendance-list') ? route('attendance.view') : null);
        }

        $notices = Announcement::where('session_year_id', $sessionYearId)->count();
        $thisWeek = Announcement::where('session_year_id', $sessionYearId)->where('created_at', '>=', now()->subDays(7))->count();
        $numbers[] = $this->number('notices', __('today_notices'), number_format($notices),
            __('today_notices_this_week', ['count' => $thisWeek]),
            $user->can('announcement-create') ? route('announcement.index') : null);

        return $numbers;
    }

    /** @return array{key:string, label:string, value:string, note:?string, url:?string} */
    private function number(string $key, string $label, string $value, ?string $note, ?string $url): array
    {
        return compact('key', 'label', 'value', 'note', 'url');
    }
}
