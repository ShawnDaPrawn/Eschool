<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\MarkRequest;
use App\Http\Resources\Academics\MarkResource;
use App\Services\Academics\MarkModeration;
use App\Services\Academics\MarkPreview;
use App\Services\Academics\MarkQueries;
use App\Services\Academics\MarkRecording;
use App\Services\Academics\MarkReleases;
use App\Services\Academics\ReleasedMarks;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class MarkController extends Controller
{
    private function response(MarkRequest $request, array $data): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', (new MarkResource($data))->resolve($request));
    }

    public function matrix(MarkRequest $request, int $group): JsonResponse
    {
        return $this->response($request, app(MarkQueries::class)->matrix($request->user(), $group, (int) $request->validated('term_id'), (int) $request->validated('page', 1), (int) $request->validated('per_page', 100)));
    }

    public function preview(MarkRequest $request, int $group, int $task): JsonResponse
    {
        return $this->response($request, app(MarkPreview::class)->preview($request->user(), $group, $task, $request->validated()));
    }

    public function capture(MarkRequest $request, int $group, int $task): JsonResponse
    {
        return $this->response($request, app(MarkRecording::class)->capture($request->user(), $group, $task, $request->validated()));
    }

    public function correct(MarkRequest $request, int $group, int $task): JsonResponse
    {
        return $this->response($request, app(MarkRecording::class)->capture($request->user(), $group, $task, $request->validated(), true));
    }

    public function moderate(MarkRequest $request, int $group, int $task): JsonResponse
    {
        return $this->response($request, app(MarkModeration::class)->moderate($request->user(), $group, $task, $request->validated()));
    }

    public function releasePreview(MarkRequest $request, int $group): JsonResponse
    {
        return $this->response($request, app(MarkReleases::class)->preview($request->user(), $group, $request->validated()));
    }

    public function release(MarkRequest $request, int $group): JsonResponse
    {
        return $this->response($request, app(MarkReleases::class)->release($request->user(), $group, $request->validated()));
    }

    public function family(MarkRequest $request, int $learner): JsonResponse
    {
        $term = $request->validated('term_id');

        return $this->response($request, app(ReleasedMarks::class)->list($request->user(), $learner, (int) $request->validated('session_year_id'), $term === null ? null : (int) $term));
    }
}
