<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\ChildMode;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ChildModeContext;
use App\Http\Requests\ChildMode\ChildModePinRequest;
use App\Models\ChildMode\ChildModeSession;
use App\Services\ChildMode\ChildModePins;
use App\Services\ChildMode\ChildModeSessions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Child mode in the parent app (Action Plan 4.2).
 *
 * Guardian endpoints (a parent-app sign-in, school.context, role:Parent):
 * whether child mode is open for a child and what the child may open, set
 * or change the PIN, start a child session with the PIN, and end their
 * child sessions for that child.
 *
 * Child endpoints (a child token, ChildModeContext): what this child may
 * open now, and leaving child mode with the PIN. Nothing here returns
 * anything the child opened, other learners or messages.
 */
class ChildModeController extends Controller
{
    private function ok(?array $data, string $message): JsonResponse
    {
        return response()->json(['error' => false, 'message' => $message, 'data' => $data, 'code' => null], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function status(Request $request, int $child): JsonResponse
    {
        return $this->ok(app(ChildModeSessions::class)->status($request->user(), $child), 'Child mode.');
    }

    public function pin(ChildModePinRequest $request, int $child): JsonResponse
    {
        $changed = app(ChildModePins::class)->set($request->user(), $child, $request->pin());

        return $this->ok(app(ChildModeSessions::class)->status($request->user(), $child), $changed ? (string) __('child_mode_pin_changed_done') : (string) __('child_mode_pin_set_done'));
    }

    public function start(ChildModePinRequest $request, int $child): JsonResponse
    {
        return $this->ok(app(ChildModeSessions::class)->start($request, $request->user(), $child, $request->pin()), 'Child mode started.');
    }

    public function endForGuardian(Request $request, int $child): JsonResponse
    {
        $ended = app(ChildModeSessions::class)->endForGuardian($request->user(), $child);

        return $this->ok(['ended' => $ended], 'Child mode ended.');
    }

    public function session(Request $request): JsonResponse
    {
        return $this->ok(app(ChildModeSessions::class)->profile($this->current($request)), 'Child mode.');
    }

    public function end(ChildModePinRequest $request): JsonResponse
    {
        app(ChildModeSessions::class)->endWithPin($this->current($request), $request->pin());

        return $this->ok(null, 'Child mode ended.');
    }

    private function current(Request $request): ChildModeSession
    {
        $session = $request->attributes->get(ChildModeContext::SESSION);
        abort_unless($session instanceof ChildModeSession, 401);

        return $session;
    }
}
