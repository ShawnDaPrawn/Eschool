<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\MasteryRequest;
use App\Http\Resources\Growth\MasteryResource;
use App\Jobs\Growth\RecomputeMastery;
use App\Policies\Growth\MasteryPolicy;
use App\Services\Growth\MasteryQueries;
use App\Services\Growth\MasteryService;
use App\Services\Growth\TopicGraph;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class MasteryController extends Controller
{
    private function response(MasteryRequest $request, array $data): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', (new MasteryResource($data))->resolve($request));
    }

    public function configuration(MasteryRequest $request): JsonResponse
    {
        $year = $request->validated('session_year_id');

        return $this->response($request, app(MasteryQueries::class)->configuration($request->user(), $year === null ? null : (int) $year));
    }

    public function roster(MasteryRequest $request, int $group): JsonResponse
    {
        return $this->response($request, app(MasteryQueries::class)->roster($request->user(), $group, (int) $request->validated('page', 1)));
    }

    public function staff(MasteryRequest $request, int $group, int $learner): JsonResponse
    {
        $context = app(MasteryPolicy::class)->staff($request->user(), $group, $learner);

        return $this->response($request, app(MasteryService::class)->view($request->user(), $learner, (int) $context->subject_id, $group));
    }

    public function subjects(MasteryRequest $request, int $learner): JsonResponse
    {
        return $this->response($request, app(MasteryQueries::class)->subjects($request->user(), $learner));
    }

    public function family(MasteryRequest $request, int $learner): JsonResponse
    {
        return $this->response($request, app(MasteryService::class)->view($request->user(), $learner, (int) $request->validated('subject_id')));
    }

    public function graph(MasteryRequest $request, int $subject): JsonResponse
    {
        $group = $request->validated('group_id');

        return $this->response($request, app(MasteryQueries::class)->graph($request->user(), $subject, $group === null ? null : (int) $group));
    }

    public function saveGraph(MasteryRequest $request, int $subject): JsonResponse
    {
        return $this->response($request, app(TopicGraph::class)->save($request->user(), $subject, $request->validated()));
    }

    public function rebuild(MasteryRequest $request, int $group): JsonResponse
    {
        app(MasteryPolicy::class)->staff($request->user(), $group);
        RecomputeMastery::dispatch(app(CurrentSchool::class)->id(), $group);

        return $this->response($request, ['group_id' => $group, 'queued' => true]);
    }
}
