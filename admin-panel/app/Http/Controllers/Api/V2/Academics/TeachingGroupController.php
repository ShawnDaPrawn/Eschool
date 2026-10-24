<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\TeachingGroupRequest;
use App\Http\Resources\Academics\AcademicAssignmentResource;
use App\Http\Resources\Academics\TeachingGroupResource;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\TeachingGroupQueries;
use App\Services\Academics\TeachingGroups;
use App\Services\ResponseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

class TeachingGroupController extends Controller
{
    public function index(TeachingGroupRequest $request, TeachingGroups $service, TeachingGroupQueries $queries): JsonResponse
    {
        $page = $queries->groups($request->user(), $request->validated());

        return ResponseService::successResponse('data_fetch_successfully', ['groups' => collect($page->items())->map(fn ($g) => (new TeachingGroupResource($g))->resolve($request))->all()] + $this->page($page));
    }

    public function show(TeachingGroupRequest $request, TeachingGroups $service, int $group): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', (new TeachingGroupResource(app(AcademicAuthority::class)->group($request->user(), $group)))->resolve($request));
    }

    public function store(TeachingGroupRequest $request, TeachingGroups $service): JsonResponse
    {
        return ResponseService::successResponse('data_store_successfully', (new TeachingGroupResource($service->save($request->user(), $request->validated())))->resolve($request));
    }

    public function update(TeachingGroupRequest $request, TeachingGroups $service, int $group): JsonResponse
    {
        return ResponseService::successResponse('data_update_successfully', (new TeachingGroupResource($service->save($request->user(), $request->validated(), $group)))->resolve($request));
    }

    public function rows(TeachingGroupRequest $request, TeachingGroupQueries $queries): JsonResponse
    {
        $group = $request->route('group') === null ? null : (int) $request->route('group');
        $kind = (string) $request->route('kind');
        $page = $queries->rows($request->user(), $kind, $request->validated(), $group);

        return ResponseService::successResponse('data_fetch_successfully', ['assignments' => collect($page->items())->map(fn ($row) => (new AcademicAssignmentResource($row))->resolve($request))->all()] + $this->page($page));
    }

    public function saveRow(TeachingGroupRequest $request, TeachingGroups $service, TeachingGroupQueries $queries): JsonResponse
    {
        $kind = (string) $request->route('kind');
        $group = $request->route('group') === null ? null : (int) $request->route('group');
        $id = $request->route('entry') === null ? null : (int) $request->route('entry');
        $row = $service->dated($request->user(), $kind, $request->validated(), $group, $id);
        $row->load(match ($kind) {
            'memberships' => 'learner.user', 'teachers', 'homerooms' => 'teacher.user', 'leadership' => 'user'
        });

        return ResponseService::successResponse('data_store_successfully', (new AcademicAssignmentResource($row))->resolve($request));
    }

    private function page(LengthAwarePaginator $page): array
    {
        return ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()];
    }
}
