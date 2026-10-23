<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\ClassGradeRequest;
use App\Http\Resources\Academics\AcademicReferenceResource;
use App\Http\Resources\Academics\ClassGradeResource;
use App\Services\Academics\AcademicReferences;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicReferenceController extends Controller
{
    public function index(Request $request, AcademicReferences $service): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully',
            (new AcademicReferenceResource($service->catalogue($request->user())))->resolve($request));
    }

    public function show(ClassGradeRequest $request, AcademicReferences $service, int $class): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully',
            (new ClassGradeResource($service->mapping($request->user(), $class)))->resolve($request));
    }

    public function update(ClassGradeRequest $request, AcademicReferences $service, int $class): JsonResponse
    {
        return ResponseService::successResponse('data_update_successfully',
            (new ClassGradeResource($service->map($request->user(), $class, $request->validated())))->resolve($request));
    }
}
