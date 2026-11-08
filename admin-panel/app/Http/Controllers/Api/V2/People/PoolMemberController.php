<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\Pools\PoolSignInRequest;
use App\Services\People\Pools\PoolAccess;
use App\Services\People\Pools\PoolBookings;
use App\Services\People\Pools\PoolMembers;
use App\Services\People\Pools\PoolSignIn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The pool member's own endpoints (Action Plan 5.6), reached only with a
 * people-pool-app token (PoolMemberContext). Nothing here reads learner
 * records, marks or contact details; the only learner names are a booked
 * session's attendance list, on the day.
 */
class PoolMemberController extends Controller
{
    private function ok(array $data, string $message = 'Done.'): JsonResponse
    {
        return response()->json(['error' => false, 'message' => $message, 'data' => $data, 'code' => null], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function signIn(PoolSignInRequest $request): JsonResponse
    {
        $data = app(PoolSignIn::class)->signIn($request, $request->validated());
        $ok = $data['authenticated'];

        return response()->json(['error' => ! $ok, 'message' => $ok ? 'Signed in.' : 'Complete sign-in setup.', 'data' => $data, 'code' => $ok ? null : 'POOL_SETUP_REQUIRED'], $ok ? 200 : 409, ['Cache-Control' => 'private, no-store']);
    }

    public function session(Request $request): JsonResponse
    {
        return $this->ok(app(PoolSignIn::class)->profile($request->user()), 'Session refreshed.');
    }

    public function bookings(Request $request): JsonResponse
    {
        return $this->ok(app(PoolBookings::class)->mine(app(PoolAccess::class)->member($request->user())));
    }

    public function attendance(Request $request, int $booking): JsonResponse
    {
        return $this->ok(app(PoolBookings::class)->attendance(app(PoolAccess::class)->member($request->user()), $booking));
    }

    public function withdraw(Request $request): JsonResponse
    {
        // Withdrawing is always possible with a pool session, even with the
        // pool switched off or read-only, or setup incomplete.
        return $this->ok(app(PoolMembers::class)->withdraw($request->user()), 'Consent withdrawn.');
    }

    public function signOut(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        $token->delete();

        return response()->json(['error' => false, 'message' => 'Signed out.', 'data' => null, 'code' => null], 200, ['Cache-Control' => 'private, no-store']);
    }
}
