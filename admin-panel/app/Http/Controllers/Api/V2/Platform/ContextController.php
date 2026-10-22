<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Platform;

use App\Http\Controllers\Controller;
use App\Http\Resources\Platform\ContextResource;
use App\Services\Platform\ContextService;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The apps call this on launch, after sign-in and after a school switch, and
 * cache the answer.
 */
class ContextController extends Controller
{
    public function __construct(private readonly ContextService $context) {}

    /**
     * GET /api/v2/me/context
     */
    public function show(Request $request): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', new ContextResource($this->context->for($request->user())));
    }
}
