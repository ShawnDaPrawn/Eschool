<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Pastoral;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pastoral\CoverRequest;
use App\Services\Pastoral\CoverQueries;
use App\Services\Pastoral\ReliefCover;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class CoverController extends Controller
{
    public function configuration(CoverRequest $r): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', app(CoverQueries::class)->configuration($r->user(), (int) $r->validated('session_year_id'), $r->validated('from'), $r->validated('to'), (int) $r->validated('page', 1)));
    }

    public function mine(CoverRequest $r): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', ['occurrences' => app(CoverQueries::class)->mine($r->user(), $r->validated('from'), $r->validated('to'), (int) $r->validated('session_year_id'))]);
    }

    public function absence(CoverRequest $r, int $absence): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', app(CoverQueries::class)->absence($r->user(), $absence));
    }

    public function occurrence(CoverRequest $r, int $occurrence): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', app(CoverQueries::class)->occurrence($r->user(), $occurrence));
    }

    public function candidates(CoverRequest $r, int $occurrence): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', app(CoverQueries::class)->candidates($r->user(), $occurrence));
    }

    public function request(CoverRequest $r): JsonResponse
    {
        $row = app(ReliefCover::class)->request($r->user(), $r->safe()->except('operation_id'), $r->validated('operation_id'));

        return $this->absence($r, (int) $row->id);
    }

    public function assign(CoverRequest $r, int $occurrence): JsonResponse
    {
        app(ReliefCover::class)->assign($r->user(), $occurrence, (int) $r->validated('substitute_teacher_id'), (int) $r->validated('revision'), $r->validated('operation_id'));

        return $this->occurrence($r, $occurrence);
    }

    public function refresh(CoverRequest $r, int $absence): JsonResponse
    {
        return $this->change($r, $absence, 'refresh');
    }

    public function cancel(CoverRequest $r, int $absence): JsonResponse
    {
        return $this->change($r, $absence, 'cancel');
    }

    private function change(CoverRequest $r, int $absence, string $action): JsonResponse
    {
        app(ReliefCover::class)->change($r->user(), $absence, (int) $r->validated('revision'), $action, $r->validated('reason'), $r->validated('operation_id'));

        return $this->absence($r, $absence);
    }
}
