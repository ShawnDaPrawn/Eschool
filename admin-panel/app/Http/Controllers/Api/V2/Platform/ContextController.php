<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\ContextService;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Apps fetch this at start-up, after signing in and when the user changes
 * school, and keep a cached copy.
 */
class ContextController extends Controller
{
    public function __construct(private readonly ContextService $context) {}

    /**
     * GET /api/v2/me/context (openapi.yaml, Context), composed by the
     * contributors in config/context.php for the school the request works in.
     */
    public function show(Request $request): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $this->context->for($request->user(), $request));
    }
}
