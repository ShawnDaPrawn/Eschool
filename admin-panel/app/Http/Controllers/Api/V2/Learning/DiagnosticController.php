<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\DiagnosticRequest;
use App\Services\Learning\DiagnosticService;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

/** /api/v2/learning/diagnostics: the starting check (Action Plan 3.7). */
class DiagnosticController extends Controller
{
    public function __construct(private readonly DiagnosticService $diagnostics) {}

    private function result(array $data): JsonResponse
    {
        return ResponseService::successResponse(data: $data)->header('Cache-Control', 'private, no-store');
    }

    public function group(DiagnosticRequest $r, int $group): JsonResponse
    {
        return $this->result($this->diagnostics->group($r->user(), $group));
    }

    public function summary(DiagnosticRequest $r, int $learner): JsonResponse
    {
        return $this->result($this->diagnostics->summary($r->user(), $learner));
    }

    public function start(DiagnosticRequest $r, int $learner, int $group): JsonResponse
    {
        return $this->result($this->diagnostics->start($r->user(), $learner, $group));
    }

    public function read(DiagnosticRequest $r, int $learner, int $attempt): JsonResponse
    {
        return $this->result($this->diagnostics->read($r->user(), $learner, $attempt));
    }

    public function answer(DiagnosticRequest $r, int $learner, int $attempt, int $item): JsonResponse
    {
        return $this->result($this->diagnostics->answer($r->user(), $learner, $attempt, $item, $r->validated()));
    }
}
