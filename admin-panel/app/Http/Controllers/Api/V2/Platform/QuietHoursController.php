<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\QuietHoursRequest;
use App\Services\Platform\Notifications\QuietHours;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

/** Your push quiet hours (Action Plan 1.5). */
class QuietHoursController extends Controller
{
    public function show(QuietHoursRequest $request, QuietHours $quiet): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $quiet->get($request->user()));
    }

    public function update(QuietHoursRequest $request, QuietHours $quiet): JsonResponse
    {
        return ResponseService::successResponse('data_update_successfully', $quiet->save($request->user(), [
            'enabled' => $request->boolean('enabled'), 'starts_at' => (string) $request->validated('starts_at'),
            'ends_at' => (string) $request->validated('ends_at'), 'timezone' => $request->validated('timezone'),
        ]));
    }
}
