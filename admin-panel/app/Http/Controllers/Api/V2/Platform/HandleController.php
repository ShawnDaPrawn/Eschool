<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\HandleDecisionRequest;
use App\Http\Requests\Platform\HandleRequest;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\HandleProposal;
use App\Models\Students;
use App\Services\Platform\Identity\Handles;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HandleController extends Controller
{
    public function show(HandleRequest $request, Handles $handles): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $handles->state($request->user()));
    }

    public function update(HandleRequest $request, Handles $handles): JsonResponse
    {
        return ResponseService::successResponse('success', $handles->request($request->user(), $request->validated('handle')));
    }

    public function proposals(Request $request): JsonResponse
    {
        abort_unless($request->user()->email_verified_at !== null && (int) $request->user()->status === 1, 403, 'Verify your email to review handle requests.');
        $learners = GuardianLink::query()->where('guardian_user_id', $request->user()->id)->where('status', 'active')->where('can_approve', true)->select('learner_id');
        $rows = HandleProposal::query()->whereIn('learner_id', $learners)->where('status', 'pending')->where('expires_at', '>', now())
            ->orderBy('id')->get();
        $names = Students::query()->whereIn('id', $rows->pluck('learner_id'))->with('user')->get()->keyBy('id');

        return ResponseService::successResponse('data_fetch_successfully', $rows->filter(fn ($proposal) => $names->has($proposal->learner_id) && $names[$proposal->learner_id]->user !== null)->map(fn ($proposal) => [
            'id' => (int) $proposal->id, 'handle' => $proposal->handle, 'expires_at' => $proposal->expires_at->toIso8601String(),
            'learner' => ['id' => (int) $proposal->learner_id, 'name' => $names[$proposal->learner_id]->user->full_name],
        ])->values()->all());
    }

    public function decide(HandleDecisionRequest $request, int $proposal, Handles $handles): JsonResponse
    {
        return ResponseService::successResponse('success', $handles->decide($request->user(), $proposal, $request->validated('decision')));
    }
}
