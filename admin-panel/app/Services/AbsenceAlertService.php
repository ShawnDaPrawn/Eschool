<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Parents;
use App\Models\Students;
use App\Models\UserNotification;
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
            $parentUserIds = Parents::whereIn('id', array_filter([$student->father_id, $student->mother_id, $student->guardian_id]))
                ->pluck('user_id')
                ->filter()
                ->unique()
                ->values();
            if (! $student->user || $parentUserIds->isEmpty()) {
                continue;
            }

            $title = 'Attendance Alert';
            $body = $student->user->first_name.' '.$student->user->last_name.' is Absent on '.date('d-m-Y', strtotime($date));

            // send_to 2: only the users in user_notifications (1 and 4 are shown to everyone).
            $notification = new Notification;
            $notification->send_to = 2;
            $notification->session_year_id = $sessionYearId;
            $notification->title = $title;
            $notification->message = $body;
            $notification->type = 'attendance';
            $notification->date = Carbon::now();
            $notification->is_custom = 0;
            $notification->save();

            UserNotification::insert($parentUserIds->map(fn ($userId) => [
                'notification_id' => $notification->id,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all());

            sendSimpleNotification($parentUserIds, $title, $body, 'attendance', null, null);
        }
    }
}
