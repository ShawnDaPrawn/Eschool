<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Services\Platform\Notifications\NotificationVisibility;
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

            // Send To All Users(1) and Students(3)
            $notification = NotificationVisibility::forUser(
                Notification::where('session_year_id', $session_year_id)->where(fn ($q) => $q->whereNull('type')->orWhere('type', '<>', 'group_chat.staff_update'))->where(fn ($q) => $q->whereNull('type')->orWhere('type', '<>', 'group_chat.family_update')->orWhere(fn ($q) => $q->where('type', 'group_chat.family_update')->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(delivery_payload), delivery_payload, '{}'), '$.group_chat_persona')) = ?", ['student']))), (int) $user, 3
            )->latest()->paginate();

            $notification->through(function (Notification $row) use ($user): Notification {
                if ($row->type === 'group_chat.family_update') {
                    $row->setAttribute('reader_id', (int) $user);
                    $row->makeVisible('school_id');
                }

                return $row;
            });

            return ResponseService::successResponse('Data Fetched Successfully', $notification ?? '');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
