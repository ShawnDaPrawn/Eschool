<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Facilitators;

use App\Http\Controllers\Controller;
use App\Http\Requests\Facilitators\SignInRequest;
use App\Http\Resources\Facilitators\SignInResource;
use App\Services\Facilitators\FacilitatorAccess;
use App\Services\Facilitators\FacilitatorSignIn;
use App\Services\Platform\Identity\ExpectedAccount;
use App\Services\Platform\Identity\PortalSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function signIn(SignInRequest $request): JsonResponse
    {
        $data = app(FacilitatorSignIn::class)->signIn($request, $request->validated());
        $ok = $data['authenticated'];

        return response()->json(['error' => ! $ok, 'message' => $ok ? 'Signed in.' : 'Complete sign-in setup.', 'data' => (new SignInResource($data))->resolve($request), 'code' => $ok ? null : 'FACILITATOR_SETUP_REQUIRED'], $ok ? 200 : 409)->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request): JsonResponse
    {
        abort_unless(app(FacilitatorAccess::class)->ownsSession($request), 403);
        app(FacilitatorAccess::class)->enforceProfile($request);

        return response()->json(['error' => false, 'message' => 'Session refreshed.', 'data' => app(FacilitatorAccess::class)->profile($request->user()), 'code' => null])->header('Cache-Control', 'private, no-store');
    }

    public function signOut(Request $request): JsonResponse
    {
        ExpectedAccount::check($request);
        abort_unless(app(FacilitatorAccess::class)->ownsSession($request), 403);
        PortalSession::signOut($request, $request->user());

        return response()->json(['error' => false, 'message' => 'Signed out.', 'data' => null, 'code' => null])->header('Cache-Control', 'private, no-store');
    }
}
