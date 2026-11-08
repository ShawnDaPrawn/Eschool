<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\LearningPathRequest;
use App\Services\Learning\LearningPathQueries;
use App\Services\Learning\LearningPathService;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

/** The guided learning path (Action Plan 3.9): the learner's own path, a group's steps for its teacher, a child's summary for a guardian. */
class LearningPathController extends Controller
{
    private function result(callable $action): JsonResponse
    {
        return ResponseService::successResponse(data: $action())->header('Cache-Control', 'private, no-store');
    }

    public function show(LearningPathRequest $r, int $learner, int $group): JsonResponse
    {
        return $this->result(fn () => app(LearningPathService::class)->read($r->user(), $learner, $group));
    }

    public function act(LearningPathRequest $r, int $learner, int $group): JsonResponse
    {
        return $this->result(fn () => app(LearningPathService::class)->act($r->user(), $learner, $group, $r->all()));
    }

    public function answer(LearningPathRequest $r, int $learner, int $group, int $item): JsonResponse
    {
        return $this->result(fn () => app(LearningPathService::class)->answer($r->user(), $learner, $group, $item, $r->all()));
    }

    public function group(LearningPathRequest $r, int $group): JsonResponse
    {
        return $this->result(fn () => app(LearningPathQueries::class)->group($r->user(), $group, (int) $r->validated('page', 1)));
    }

    public function family(LearningPathRequest $r, int $learner): JsonResponse
    {
        return $this->result(fn () => app(LearningPathQueries::class)->family($r->user(), $learner));
    }
}
