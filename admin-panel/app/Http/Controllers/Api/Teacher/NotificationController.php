<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Api\Teacher\Concerns\TeacherAccess;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\Platform\Notifications\NotificationVisibility;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Auth;

/**
 * Teacher app: the teacher's notifications.
 */
class NotificationController extends Controller
{
    use TeacherAccess;

    public function getNotifications()
    {
        try {
            // $user = $request->user()->id;
            $user = Auth::user()->id;
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            // Send To All Users(1) and Teachers(5)
            $notification = NotificationVisibility::forUser(
                Notification::where('session_year_id', $session_year_id), (int) $user, 5
            )->latest()->paginate();

            return ResponseService::successResponse('Data Fetched Successfully', $notification ?? '');
        } catch (\Exception $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
