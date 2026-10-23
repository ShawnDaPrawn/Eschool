<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\AcceptGuardianInvitationRequest;
use App\Models\User;
use App\Services\Platform\Identity\GuardianInvitations;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuardianInvitationController extends Controller
{
    public function show(Request $request, string $token, GuardianInvitations $invitations): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $invitations->preview($token, $request));
    }

    public function accept(AcceptGuardianInvitationRequest $request, string $token, GuardianInvitations $invitations): JsonResponse
    {
        $answer = $invitations->accept($token, $request->validated('password'), $request);

        return ResponseService::successResponse('success', $answer['data'], ['token' => $answer['token']]);
    }

    public function store(Request $request, int $guardian, GuardianInvitations $invitations): JsonResponse
    {
        abort_unless($request->user()->can('student-create'), 403);
        $invitation = $invitations->issue(User::query()->findOrFail($guardian), $request->user());

        return ResponseService::successResponse('success', ['id' => (int) $invitation->id, 'expires_at' => $invitation->expires_at->toIso8601String()]);
    }
}
