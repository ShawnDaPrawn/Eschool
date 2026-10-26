<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\MyGradeRequest;
use App\Services\Growth\MyGrade;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class MyGradeController extends Controller
{
    public function access(MyGradeRequest $request): JsonResponse
    {
        return ResponseService::successResponse(data: app(MyGrade::class)->access($request->user()));
    }

    public function dashboard(MyGradeRequest $request, int $grade): JsonResponse
    {
        return ResponseService::successResponse(data: app(MyGrade::class)->dashboard($request->user(), $grade,
            $request->validated('on_date'), (int) $request->validated('page', 1), (int) $request->validated('discipline_page', 1)));
    }
}
