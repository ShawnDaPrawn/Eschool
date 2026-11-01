<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\CalendarFeedRequest;
use App\Services\Academics\CalendarFeeds;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

/** A learner's calendar subscription links (Action Plan 1.10). */
class CalendarFeedController extends Controller
{
    public function index(CalendarFeedRequest $request): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', ['items' => app(CalendarFeeds::class)->listing($request->user())]);
    }

    public function store(CalendarFeedRequest $request): JsonResponse
    {
        return ResponseService::successResponse('data_store_successfully', app(CalendarFeeds::class)->create($request->user(), (int) $request->validated('learner_id'), (bool) $request->validated('include_lessons', false)));
    }

    public function destroy(CalendarFeedRequest $request, int $feed): JsonResponse
    {
        app(CalendarFeeds::class)->revoke($request->user(), $feed);

        return ResponseService::successResponse('data_update_successfully', ['id' => $feed, 'revoked' => true]);
    }
}
