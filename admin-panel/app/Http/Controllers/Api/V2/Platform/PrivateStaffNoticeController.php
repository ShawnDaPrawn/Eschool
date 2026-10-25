<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\PrivateStaffNoticeRequest;
use App\Services\Platform\Notifications\StaffPrivateNotices;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class PrivateStaffNoticeController extends Controller
{
    public function index(PrivateStaffNoticeRequest $request): JsonResponse
    {
        return ResponseService::successResponse(data: app(StaffPrivateNotices::class)->inbox($request->user(), (int) $request->validated('page', 1)))
            ->header('Cache-Control', 'private, no-store');
    }

    public function read(PrivateStaffNoticeRequest $request, int $notice): JsonResponse
    {
        return ResponseService::successResponse(data: app(StaffPrivateNotices::class)->markRead($request->user(), $notice))
            ->header('Cache-Control', 'private, no-store');
    }
}
