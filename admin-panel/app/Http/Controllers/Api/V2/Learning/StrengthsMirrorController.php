<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\StrengthsRequest;
use App\Services\Learning\Strengths\StrengthsMirror;
use App\Services\Learning\Strengths\StrengthsShares;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

/** The learner's interests and strengths mirror (engineering/specs/learning/strengths-mirror.md). */
class StrengthsMirrorController extends Controller
{
    public function mirror(StrengthsRequest $request, StrengthsMirror $mirror): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $mirror->forLearner($request->user()));
    }

    public function recipients(StrengthsRequest $request, StrengthsShares $shares): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', ['recipients' => $shares->recipients($request->user())]);
    }

    public function shares(StrengthsRequest $request, StrengthsShares $shares): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $shares->mine($request->user()));
    }

    public function share(StrengthsRequest $request, StrengthsShares $shares): JsonResponse
    {
        return ResponseService::successResponse('data_store_successfully', $shares->create($request->user(), $request->validated()));
    }

    public function revoke(StrengthsRequest $request, StrengthsShares $shares, int $share): JsonResponse
    {
        return ResponseService::successResponse('data_update_successfully', $shares->revoke($request->user(), $share));
    }

    public function sharedWithMe(StrengthsRequest $request, StrengthsShares $shares): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $shares->sharedWithMe($request->user()));
    }

    public function shared(StrengthsRequest $request, StrengthsShares $shares, int $share): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $shares->view($request->user(), $share));
    }
}
