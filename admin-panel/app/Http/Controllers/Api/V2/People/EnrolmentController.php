<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\EnrolmentRequest;
use App\Http\Resources\People\EnrolmentResource;
use App\Models\People\Enrolment;
use App\Models\Students;
use App\Services\People\EnrolmentService;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class EnrolmentController extends Controller
{
    public function index(EnrolmentRequest $request, int $learner): JsonResponse
    {
        $student = Students::query()->findOrFail($learner);
        Gate::authorize('viewEnrolments', $student);
        $entries = Enrolment::query()->where('learner_id', $student->id)
            ->when($request->validated('date'), fn ($query, $date) => $query->onDate($date))
            ->orderByDesc('starts_on')->paginate((int) $request->validated('per_page', 20));

        return ResponseService::successResponse('data_fetch_successfully', [
            'items' => EnrolmentResource::collection($entries->items())->resolve($request),
            'pagination' => ['page' => $entries->currentPage(), 'per_page' => $entries->perPage(), 'total' => $entries->total(), 'last_page' => $entries->lastPage()],
        ]);
    }

    public function store(EnrolmentRequest $request, int $learner, EnrolmentService $service): JsonResponse
    {
        $student = Students::query()->findOrFail($learner);
        Gate::authorize('manageEnrolments', $student);
        $entry = $service->place((int) $student->id, (int) $request->validated('class_section_id'), (int) $request->validated('session_year_id'), $request->validated('starts_on'), $request->validated('reason'));

        return ResponseService::successResponse('success', (new EnrolmentResource($entry))->resolve($request));
    }
}
