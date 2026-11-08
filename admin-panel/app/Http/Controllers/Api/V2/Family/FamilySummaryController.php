<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\Family\FamilySummaryRequest;
use App\Services\FamilySummary\FamilySummaryPreferences;
use App\Services\FamilySummary\FamilySummaryReader;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

/** The daily family summary (Action Plan 5.11): the guardian's own choice and reading a summary. */
class FamilySummaryController extends Controller
{
    public function preferences(FamilySummaryRequest $request, FamilySummaryPreferences $preferences): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $preferences->get($request->user()));
    }

    public function savePreferences(FamilySummaryRequest $request, FamilySummaryPreferences $preferences): JsonResponse
    {
        return ResponseService::successResponse('data_update_successfully', $preferences->save($request->user(), [
            'enabled' => $request->boolean('enabled'), 'send_at' => (string) $request->validated('send_at'), 'timezone' => $request->validated('timezone'),
        ]));
    }

    public function show(FamilySummaryRequest $request, FamilySummaryReader $reader, int $summary): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $reader->show($request->user(), $summary));
    }
}
