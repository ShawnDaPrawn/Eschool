<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\Platform\Notifications\NotificationVisibility;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Parent app: the notification list.
 */
class NotificationController extends Controller
{
    use ParentAccess;

    public function getNotifications()
    {
        try {
            $user = Auth::user()->id;

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            // Send To All Users(1) and Parents(4)
            $notification = NotificationVisibility::forUser(
                Notification::where('session_year_id', $session_year_id), (int) $user, 4
            )->latest()->paginate();

            return ResponseService::successResponse('Data Fetched Successfully', $notification ?? '', [], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
