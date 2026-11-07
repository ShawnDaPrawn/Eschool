<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Passport;

use App\Http\Controllers\Controller;
use App\Http\Requests\Passport\PassportExportRequest;
use App\Services\Passport\LearnerPassport;
use App\Services\Passport\PassportExports;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The learner passport for the apps (Action Plan 4.10): the learner's own,
 * a guardian's child's or, for staff, what this school holds.
 */
class PassportController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        return $this->result(app(LearnerPassport::class)->mine($request->user()));
    }

    public function show(Request $request, int $learner): JsonResponse
    {
        return $this->result(app(LearnerPassport::class)->forLearner($request->user(), $learner));
    }

    public function export(PassportExportRequest $request): Response
    {
        $file = app(PassportExports::class)->export($request->user(), (string) ($request->validated('format') ?? 'json'));

        return response($file['bytes'], 200, ['Content-Type' => $file['mime'], 'Content-Disposition' => 'attachment; filename="'.$file['filename'].'"', 'Cache-Control' => 'no-store, private']);
    }

    private function result(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'Learner passport.', 'data' => $data, 'code' => 200], 200, ['Cache-Control' => 'no-store, private']);
    }
}
