<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\People;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Platform\Identity\LearnerInvitations;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Panel actions for a learner's sign-in set-up: email the guardians a new
 * link, or print a one-time set-up code (the page opens in a new tab with the
 * code shown once; printing a new one stops the old one).
 */
class LearnerInvitationController extends Controller
{
    /** Who may set up a learner's sign-in. */
    private const PERMISSIONS = ['student-create', 'student-edit', 'student-reset-password'];

    public function __construct(private readonly LearnerInvitations $invitations) {}

    public function invite(Request $request, int $id): JsonResponse
    {
        if (! Auth::user()->canAny(self::PERMISSIONS)) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }
        $learner = $this->learner($id);
        if ($learner === null) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }

        $sent = $this->invitations->inviteGuardians($learner, Auth::user());

        return response()->json($sent > 0
            ? ['error' => false, 'message' => trans('learner_invitation_sent', ['count' => $sent])]
            : ['error' => true, 'message' => trans('learner_invitation_no_guardian_email')]);
    }

    public function sheet(Request $request, int $id): View|JsonResponse
    {
        if (! Auth::user()->canAny(self::PERMISSIONS)) {
            abort(403);
        }
        $learner = $this->learner($id);
        if ($learner === null) {
            abort(404);
        }

        [$invitation, $code] = $this->invitations->issueCode($learner, Auth::user());

        return view('learner-setup.sheet', [
            'learner' => $learner,
            'code' => $code,
            'expiresAt' => $invitation->expires_at,
            'setupUrl' => rtrim((string) config('app.url'), '/').route('learner-setup.code', [], false),
        ]);
    }

    private function learner(int $id): ?User
    {
        return User::learners()->whereHas('student')->find($id);
    }
}
