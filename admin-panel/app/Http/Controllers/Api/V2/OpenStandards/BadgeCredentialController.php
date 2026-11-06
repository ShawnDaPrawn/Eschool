<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\OpenStandards;

use App\Http\Controllers\Controller;
use App\Services\OpenStandards\Badges\BadgeCredentials;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A learner's Open Badges 3.0 credentials (Action Plan 5.9), for their
 * guardians and, from the age stage the school's rules allow, the learner.
 */
class BadgeCredentialController extends Controller
{
    public function __construct(private readonly BadgeCredentials $credentials) {}

    public function index(Request $request, int $learner): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $this->credentials->forLearner($request->user(), $learner));
    }

    public function show(Request $request, int $learner, int $presentation): JsonResponse
    {
        $file = $this->credentials->download($request->user(), $learner, $presentation);

        return response()->json($file['document'], 200, [
            'Content-Type' => 'application/ld+json',
            'Content-Disposition' => 'attachment; filename="'.$file['filename'].'"',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
