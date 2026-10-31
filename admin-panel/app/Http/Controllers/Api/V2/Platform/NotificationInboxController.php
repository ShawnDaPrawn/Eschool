<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\NotificationInboxRequest;
use App\Services\Platform\Notifications\NotificationInbox;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class NotificationInboxController extends Controller
{
    public function index(NotificationInboxRequest $request, NotificationInbox $inbox): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $inbox->page($request->user(), (int) $request->validated('page', 1)))
            ->header('Cache-Control', 'private, no-store');
    }

    public function show(NotificationInboxRequest $request, NotificationInbox $inbox, int $notice): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $inbox->show($request->user(), $notice))
            ->header('Cache-Control', 'private, no-store');
    }
}
