<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\GuardianDecisionRequest;
use App\Http\Resources\Platform\ChildResource;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\School;
use App\Models\Students;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\LearnerIdentifiers;
use App\Services\Platform\SchoolProfile;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GuardianApprovalController extends Controller
{
    public function index(Request $request, ApprovalService $approvals): JsonResponse
    {
        $ids = GuardianLink::query()->where('guardian_user_id', $request->user()->id)->where('status', 'active')->where('can_approve', true)->pluck('learner_id');
        $profile = SchoolProfile::current(School::query()->findOrFail(app(CurrentSchool::class)->id()));
        $rows = Students::query()->with('user')->whereIn('id', $ids)->get()->map(function (Students $learner) use ($request, $profile, $approvals) {
            return [
                'learner' => array_intersect_key((new ChildResource($learner, $profile))->resolve($request), array_flip(['id', 'name', 'image', 'school'])) + ['student_number' => $learner->getAttribute('student_number'),
                    'primary_identifier' => app(LearnerIdentifiers::class)->forLearner($request->user(), $learner)],
                'status' => $approvals->state($learner)['status'],
                'requested_at' => $learner->created_at?->toIso8601String(),
                'consent' => ['version' => config('identity.consent_version'), 'text' => config('identity.consent_text')],
            ];
        });

        return ResponseService::successResponse('data_fetch_successfully', $rows);
    }

    public function store(GuardianDecisionRequest $request, int $learner, ApprovalService $approvals): JsonResponse
    {
        $student = Students::query()->findOrFail($learner);
        Gate::authorize('approveAccess', $student);

        return ResponseService::successResponse('success', $approvals->decide($request->user(), $student, $request->validated('decision'), $request->validated('consent_version')));
    }
}
