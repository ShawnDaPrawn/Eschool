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
                Notification::where('session_year_id', $session_year_id)->where(fn ($q) => $q->whereNull('type')->orWhere('type', '<>', 'group_chat.staff_update'))->where(fn ($q) => $q->whereNull('type')->orWhere('type', '<>', 'group_chat.family_update')->orWhere(fn ($q) => $q->where('type', 'group_chat.family_update')->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(delivery_payload), delivery_payload, '{}'), '$.group_chat_persona')) = ?", ['parent']))), (int) $user, 4
            )->latest()->paginate();
            // The new private action can pin the actual recipient even when a
            // browser cookie changes before a stale client context refreshes.
            $notification->through(function (Notification $row) use ($user): Notification {
                if (in_array($row->type, ['consent.requested', 'school_assistance.parent_update', 'group_chat.family_update'], true)) {
                    $row->setAttribute('reader_id', (int) $user);
                    $row->makeVisible('school_id');
                }

                return $row;
            });

            return ResponseService::successResponse('Data Fetched Successfully', $notification ?? '', [], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
