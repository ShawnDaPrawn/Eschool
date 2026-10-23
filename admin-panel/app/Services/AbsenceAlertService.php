<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Students;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Notifications\Notifier;
use Carbon\Carbon;

/**
 * Tells a student's father, mother and guardian that the student was marked absent,
 * whether a teacher took attendance in the admin panel or in the teacher app.
 */
class AbsenceAlertService
{
    /**
     * @param  iterable<int>  $studentIds  students newly marked absent
     */
    public function notify(iterable $studentIds, string $date, int $sessionYearId): void
    {
        $students = Students::with('user')->whereIn('id', collect($studentIds)->unique()->all())->get();

        foreach ($students as $student) {
            $parentUserIds = app(GuardianService::class)->guardianUserIds($student);
            if (! $student->user || $parentUserIds->isEmpty()) {
                continue;
            }

            $title = 'Attendance Alert';
            $body = $student->user->first_name.' '.$student->user->last_name.' is Absent on '.date('d-m-Y', strtotime($date));

            // New notices are visible only through their allowed recipient links.
            $notification = new Notification;
            $notification->send_to = 2;
            $notification->session_year_id = $sessionYearId;
            $notification->title = $title;
            $notification->message = $body;
            $notification->type = 'attendance';
            $notification->date = Carbon::now();
            $notification->is_custom = 0;
            $notification->save();

            app(Notifier::class)->assign($notification, $parentUserIds);
        }
    }
}
