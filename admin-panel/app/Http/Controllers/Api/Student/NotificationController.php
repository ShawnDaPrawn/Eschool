<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Student app: the notification list.
 */
class NotificationController extends Controller
{
    use StudentAccess;

    public function getNotifications(Request $request)
    {
        try {
            // $user = $request->user()->id;
            $user = Auth::user()->id;

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $notification_id = UserNotification::where('user_id', $user)->pluck('notification_id');
            // Send To All Users(1) and Students(3)
            $notification = Notification::where('session_year_id', $session_year_id)
                ->where(function ($query) use ($notification_id) {
                    $query->whereIn('id', $notification_id)
                        ->orWhereIn('send_to', [1, 4]);
                })
                ->latest()
                ->paginate();

            return ResponseService::successResponse('Data Fetched Successfully', $notification ?? '');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
