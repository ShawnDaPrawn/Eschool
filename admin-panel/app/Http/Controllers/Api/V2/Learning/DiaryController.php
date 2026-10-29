<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\DiaryRequest;
use App\Services\Learning\Diary\DiaryEntries;
use App\Services\ResponseService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

/** The learner's private diary (engineering/specs/learning/learner-diary.md). */
class DiaryController extends Controller
{
    public function index(DiaryRequest $request, DiaryEntries $diary): JsonResponse
    {
        $data = $diary->range($request->learner(),
            CarbonImmutable::createFromFormat('!Y-m-d', (string) $request->validated('from')),
            CarbonImmutable::createFromFormat('!Y-m-d', (string) $request->validated('to')));

        return ResponseService::successResponse('data_fetch_successfully', $data);
    }

    public function reminders(DiaryRequest $request, DiaryEntries $diary): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $diary->reminders($request->learner()));
    }

    public function store(DiaryRequest $request, DiaryEntries $diary): JsonResponse
    {
        return ResponseService::successResponse('data_store_successfully', $diary->create($request->learner(), $request->validated()));
    }

    public function update(DiaryRequest $request, DiaryEntries $diary, int $entry): JsonResponse
    {
        return ResponseService::successResponse('data_update_successfully', $diary->update($request->learner(), $entry, $request->validated()));
    }

    public function destroy(DiaryRequest $request, DiaryEntries $diary, int $entry): JsonResponse
    {
        $diary->delete($request->learner(), $entry);

        return ResponseService::successResponse('data_delete_successfully', ['id' => $entry]);
    }

    public function state(DiaryRequest $request, DiaryEntries $diary, string $source, int $item): JsonResponse
    {
        return ResponseService::successResponse('data_update_successfully', $diary->setItemState($request->learner(), $source, $item, $request->validated()));
    }
}
