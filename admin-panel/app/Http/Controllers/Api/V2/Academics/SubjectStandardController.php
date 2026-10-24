<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\SubjectStandardRequest;
use App\Http\Resources\Academics\SubjectStandardMappingResource;
use App\Http\Resources\Academics\SubjectStandardResource;
use App\Services\Academics\SubjectStandards;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class SubjectStandardController extends Controller
{
    public function index(SubjectStandardRequest $request, SubjectStandards $standards): JsonResponse
    {
        $page = $standards->catalogue($request->user(), $request->validated());

        return ResponseService::successResponse('data_fetch_successfully', ['standards' => SubjectStandardResource::collection($page->items())->resolve($request),
            'current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()]);
    }

    public function show(SubjectStandardRequest $request, SubjectStandards $standards, int $subject): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', (new SubjectStandardMappingResource($standards->mapping($request->user(), $subject)))->resolve($request));
    }

    public function update(SubjectStandardRequest $request, SubjectStandards $standards, int $subject): JsonResponse
    {
        return ResponseService::successResponse('data_update_successfully', (new SubjectStandardMappingResource($standards->map($request->user(), $subject,
            $request->input('standard_id') === null ? null : (int) $request->input('standard_id'))))->resolve($request));
    }

    public function store(SubjectStandardRequest $request, SubjectStandards $standards): JsonResponse
    {
        return ResponseService::successResponse('data_store_successfully', (new SubjectStandardMappingResource($standards->createSubject($request->user(),
            (int) $request->input('standard_id'), (int) $request->input('medium_id'), $request->string('type')->toString(), $request->string('bg_color')->toString())))->resolve($request));
    }
}
