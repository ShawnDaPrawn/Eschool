<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\TimetableRequest;
use App\Http\Resources\Academics\TimetableResource;
use App\Models\SessionYear;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\TimetableConfiguration;
use App\Services\Academics\TimetableEngine;
use App\Services\Academics\TimetableImport;
use App\Services\Academics\TimetableQueries;
use App\Services\ResponseService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class TimetableController extends Controller
{
    private function response(TimetableRequest $request, array $data): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', (new TimetableResource($data))->resolve($request));
    }

    public function configuration(TimetableRequest $request): JsonResponse
    {
        return $this->response($request, app(TimetableQueries::class)->configuration($request->user(), (int) $request->validated('session_year_id')));
    }

    public function room(TimetableRequest $request, ?int $room = null): JsonResponse
    {
        return $this->response($request, app(TimetableQueries::class)->room(app(TimetableConfiguration::class)->room($request->user(), $request->validated(), $room)));
    }

    public function bells(TimetableRequest $request, ?int $schedule = null): JsonResponse
    {
        return $this->response($request, app(TimetableQueries::class)->bells(app(TimetableConfiguration::class)->bells($request->user(), $request->validated(), $schedule)));
    }

    public function preview(TimetableRequest $request): JsonResponse
    {
        return $this->response($request, app(TimetableEngine::class)->preview($request->user(), $request->validated('rows')));
    }

    public function commit(TimetableRequest $request): JsonResponse
    {
        return $this->response($request, app(TimetableEngine::class)->save($request->user(), $request->validated('rows'), $request->validated('fingerprint')));
    }

    public function import(TimetableRequest $request): JsonResponse
    {
        return $this->response($request, app(TimetableImport::class)->preview($request->user(), $request->validated()));
    }

    public function week(TimetableRequest $request): JsonResponse
    {
        $starts = $request->validated('starts_on') ?? CarbonImmutable::parse(app(AcademicAuthority::class)->today())->startOfWeek()->toDateString();
        $ends = CarbonImmutable::parse($starts)->addDays(6)->toDateString();
        $learner = $request->validated('learner_id') === null ? null : (int) $request->validated('learner_id');
        $queries = app(TimetableQueries::class);
        if ($request->validated('session_year_id') !== null) {
            return $this->response($request, $queries->week($request->user(), (int) $request->validated('session_year_id'), $starts, $learner));
        }
        $years = SessionYear::whereDate('start_date', '<=', $ends)->whereDate('end_date', '>=', $starts)->orderByDesc('default')->orderByDesc('id')->get();
        abort_if($years->isEmpty(), 404, __('No school calendar covers this week.'));
        $chosen = null;
        $lessons = [];
        foreach ($years as $year) {
            $week = $queries->week($request->user(), (int) $year->id, $starts, $learner);
            // Managers explicitly switch calendars before editing their periods.
            if ($week['can_manage']) {
                return $this->response($request, $week);
            }
            if ($chosen === null || ($chosen['lessons'] === [] && $week['lessons'] !== [])) {
                $chosen = $week;
            }
            $lessons = array_merge($lessons, $week['lessons']);
        }
        usort($lessons, fn ($a, $b) => [$a['date'], $a['starts_at'], $a['id']] <=> [$b['date'], $b['starts_at'], $b['id']]);
        $chosen['lessons'] = $lessons;

        return $this->response($request, $chosen);
    }

    public function next(TimetableRequest $request): JsonResponse
    {
        return $this->response($request, app(TimetableQueries::class)->next($request->user(), ($request->validated('learner_id') === null ? null : (int) $request->validated('learner_id'))));
    }
}
