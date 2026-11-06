<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\OpenStandards;

use App\Http\Controllers\Controller;
use App\Services\OpenStandards\Badges\BadgeCredentials;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

    /** The stored signed credential, byte for byte as issued. */
    public function show(Request $request, int $learner, int $presentation): Response
    {
        $file = $this->credentials->download($request->user(), $learner, $presentation);

        return response($file['json'], 200, [
            'Content-Type' => 'application/ld+json',
            'Content-Disposition' => 'attachment; filename="'.$file['filename'].'"',
        ]);
    }
}
