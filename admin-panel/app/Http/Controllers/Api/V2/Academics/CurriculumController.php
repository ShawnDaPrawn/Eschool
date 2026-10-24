<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\CurriculumRequest;
use App\Http\Resources\Academics\CurriculumPlanResource;
use App\Services\Academics\CurriculumPlans;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class CurriculumController extends Controller
{
    public function index(CurriculumRequest $request, CurriculumPlans $plans): JsonResponse
    {
        $page = $plans->list($request->user(), $request->validated());

        return ResponseService::successResponse('data_fetch_successfully', ['plans' => collect($page->items())->map(fn ($p) => [
            'id' => (int) $p->id, 'subject_id' => (int) $p->subject_id, 'grade_level_id' => (int) $p->grade_level_id,
            'session_year_id' => (int) $p->session_year_id, 'title' => $p->title, 'version' => $p->version,
            'revision' => $p->revision, 'state' => $p->state])->all(),
            'current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()]);
    }

    public function show(CurriculumRequest $request, CurriculumPlans $plans, int $plan): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', (new CurriculumPlanResource($plans->get($request->user(), $plan)))->resolve($request));
    }

    public function store(CurriculumRequest $request, CurriculumPlans $plans): JsonResponse
    {
        return ResponseService::successResponse('data_store_successfully', (new CurriculumPlanResource($plans->save($request->user(), $request->validated())))->resolve($request));
    }

    public function update(CurriculumRequest $request, CurriculumPlans $plans, int $plan): JsonResponse
    {
        return ResponseService::successResponse('data_update_successfully', (new CurriculumPlanResource($plans->save($request->user(), $request->validated(), $plan)))->resolve($request));
    }

    public function publish(CurriculumRequest $request, CurriculumPlans $plans, int $plan, int $assessment): JsonResponse
    {
        return ResponseService::successResponse('data_update_successfully', (new CurriculumPlanResource($plans->publish($request->user(), $plan, $assessment, (int) $request->input('revision'))))->resolve($request));
    }

    public function copy(CurriculumRequest $request, CurriculumPlans $plans, int $plan): JsonResponse
    {
        return ResponseService::successResponse('data_store_successfully', (new CurriculumPlanResource($plans->copy($request->user(), $plan, (int) $request->input('session_year_id'))))->resolve($request));
    }
}
