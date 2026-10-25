<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activities\ActivityRequest;
use App\Http\Resources\Activities\ActivityResource;
use App\Services\Activities\ActivityGroups;
use Illuminate\Http\JsonResponse;

class GroupController extends Controller
{
    private function response(ActivityRequest $r, array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => trans('data_fetch_successfully'), 'data' => (new ActivityResource($data))->resolve($r), 'code' => 200], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function index(ActivityRequest $r): JsonResponse
    {
        return $this->response($r, app(ActivityGroups::class)->listing($r->user(), $r->validated('session_year_id') === null ? null : (int) $r->validated('session_year_id'), (int) $r->validated('page', 1)));
    }

    public function show(ActivityRequest $r, int $group): JsonResponse
    {
        return $this->response($r, app(ActivityGroups::class)->detail($r->user(), $group));
    }

    public function roster(ActivityRequest $r, int $group): JsonResponse
    {
        return $this->response($r, app(ActivityGroups::class)->roster($r->user(), $group));
    }

    public function create(ActivityRequest $r): JsonResponse
    {
        return $this->response($r, app(ActivityGroups::class)->save($r->user(), null, $r->validated()));
    }

    public function save(ActivityRequest $r, int $group): JsonResponse
    {
        return $this->response($r, app(ActivityGroups::class)->save($r->user(), $group, $r->validated()));
    }

    public function membership(ActivityRequest $r, int $group, ?int $allocation = null): JsonResponse
    {
        return $this->response($r, app(ActivityGroups::class)->allocation($r->user(), $group, $allocation, false, $r->validated()));
    }

    public function staff(ActivityRequest $r, int $group, ?int $allocation = null): JsonResponse
    {
        return $this->response($r, app(ActivityGroups::class)->allocation($r->user(), $group, $allocation, true, $r->validated()));
    }

    public function session(ActivityRequest $r, int $group, ?int $session = null): JsonResponse
    {
        return $this->response($r, app(ActivityGroups::class)->session($r->user(), $group, $session, $r->validated()));
    }

    public function attendance(ActivityRequest $r, int $session): JsonResponse
    {
        return $this->response($r, app(ActivityGroups::class)->attendance($r->user(), $session, $r->validated()));
    }

    public function material(ActivityRequest $r, int $group, ?int $material = null): JsonResponse
    {
        return $this->response($r, app(ActivityGroups::class)->material($r->user(), $group, $material, $r->validated()));
    }
}
