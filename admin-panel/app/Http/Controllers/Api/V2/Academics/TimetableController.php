<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\TimetableRequest;
use App\Http\Resources\Academics\TimetableResource;
use App\Services\Academics\TimetableConfiguration;
use App\Services\Academics\TimetableEngine;
use App\Services\Academics\TimetableImport;
use App\Services\Academics\TimetableQueries;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class TimetableController extends Controller
{
    private function response(TimetableRequest $request, array $data): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', (new TimetableResource($data))->resolve($request));
    }

    public function configuration(TimetableRequest $request): JsonResponse
    {
        return $this->response($request, app(TimetableQueries::class)->configuration($request->user(), (int) $request->validated('session_year_id')));
    }

    public function room(TimetableRequest $request, ?int $room = null): JsonResponse
    {
        return $this->response($request, app(TimetableQueries::class)->room(app(TimetableConfiguration::class)->room($request->user(), $request->validated(), $room)));
    }

    public function bells(TimetableRequest $request, ?int $schedule = null): JsonResponse
    {
        return $this->response($request, app(TimetableQueries::class)->bells(app(TimetableConfiguration::class)->bells($request->user(), $request->validated(), $schedule)));
    }

    public function preview(TimetableRequest $request): JsonResponse
    {
        return $this->response($request, app(TimetableEngine::class)->preview($request->user(), $request->validated('rows')));
    }

    public function commit(TimetableRequest $request): JsonResponse
    {
        return $this->response($request, app(TimetableEngine::class)->save($request->user(), $request->validated('rows'), $request->validated('fingerprint')));
    }

    public function import(TimetableRequest $request): JsonResponse
    {
        return $this->response($request, app(TimetableImport::class)->preview($request->user(), $request->validated()));
    }

    public function week(TimetableRequest $request): JsonResponse
    {
        return $this->response($request, app(TimetableQueries::class)->week($request->user(), (int) $request->validated('session_year_id'), $request->validated('starts_on'), ($request->validated('learner_id') === null ? null : (int) $request->validated('learner_id'))));
    }

    public function next(TimetableRequest $request): JsonResponse
    {
        return $this->response($request, app(TimetableQueries::class)->next($request->user(), ($request->validated('learner_id') === null ? null : (int) $request->validated('learner_id'))));
    }
}
