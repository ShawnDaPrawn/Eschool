<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\TutorMemoryRequest;
use App\Services\Learning\TutorMemory;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

/** What the tutor remembers about a learner: the learner and their guardians can see and clear it (Action Plan 3.10). */
class TutorMemoryController extends Controller
{
    public function own(TutorMemoryRequest $r): JsonResponse
    {
        return ResponseService::successResponse(data: app(TutorMemory::class)->own($r->user()));
    }

    public function clearOwn(TutorMemoryRequest $r): JsonResponse
    {
        return ResponseService::successResponse(data: app(TutorMemory::class)->clearOwn($r->user()));
    }

    public function guardian(TutorMemoryRequest $r, int $learner): JsonResponse
    {
        return ResponseService::successResponse(data: app(TutorMemory::class)->forGuardian($r->user(), $learner));
    }

    public function clearGuardian(TutorMemoryRequest $r, int $learner): JsonResponse
    {
        return ResponseService::successResponse(data: app(TutorMemory::class)->clearForGuardian($r->user(), $learner));
    }
}
