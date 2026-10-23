<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\NotificationPreferenceRequest;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class NotificationPreferenceController extends Controller
{
    public function index(NotificationPreferenceRequest $request, NotificationRules $rules): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $rules->preferences($request->user()));
    }

    public function update(NotificationPreferenceRequest $request, NotificationRules $rules): JsonResponse
    {
        $rules->choose($request->user(), $request->validated('type'), $request->validated('channel'), $request->boolean('enabled'));

        return ResponseService::successResponse('success', $rules->preferences($request->user()));
    }
}
