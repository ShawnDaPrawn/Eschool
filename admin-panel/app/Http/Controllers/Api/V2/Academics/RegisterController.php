<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\RegisterRequest;
use App\Http\Resources\Academics\RegisterResource;
use App\Services\Academics\Attendance\RegisterQr;
use App\Services\Academics\Attendance\RegisterQueries;
use App\Services\Academics\Attendance\Registers;
use App\Services\Academics\Attendance\RegisterSettings;
use App\Services\Academics\ReleasedMarks;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    private function response(RegisterRequest $request, array $data): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', (new RegisterResource($data))->resolve($request));
    }

    public function choices(RegisterRequest $request): JsonResponse
    {
        return $this->response($request, app(RegisterQueries::class)->choices($request->user(), $request->validated('on_date'), $request->validated('session_year_id') === null ? null : (int) $request->validated('session_year_id')));
    }

    public function open(RegisterRequest $request): JsonResponse
    {
        return $this->response($request, app(Registers::class)->open($request->user(), $request->validated()));
    }

    public function show(RegisterRequest $request, int $register): JsonResponse
    {
        return $this->response($request, app(Registers::class)->matrix($request->user(), $register));
    }

    public function sync(RegisterRequest $request, int $register): JsonResponse
    {
        return $this->response($request, app(Registers::class)->sync($request->user(), $register, $request->validated()));
    }

    public function today(RegisterRequest $request, int $learner): JsonResponse
    {
        return $this->response($request, app(RegisterQueries::class)->today($request->user(), $learner));
    }

    public function qr(RegisterRequest $request, int $learner): JsonResponse
    {
        return $this->response($request, ['qr_code' => app(RegisterQr::class)->issue(app(ReleasedMarks::class)->learner($request->user(), $learner))]);
    }

    public function rotate(RegisterRequest $request, int $learner): JsonResponse
    {
        return $this->response($request, ['qr_code' => app(RegisterQr::class)->rotate($request->user(), $learner)]);
    }

    public function settings(RegisterRequest $request): JsonResponse
    {
        return $this->response($request, $request->isMethodSafe() ? app(RegisterSettings::class)->get() : app(RegisterSettings::class)->save($request->user(), $request->validated()));
    }
}
