<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\VendorSignInRequest;
use App\Services\Commerce\VendorOperatorAccess;
use App\Services\Commerce\VendorSignIn;
use App\Services\Platform\Identity\ExpectedAccount;
use App\Services\Platform\Identity\PortalSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorSessionController extends Controller
{
    public function signIn(VendorSignInRequest $request): JsonResponse
    {
        $data = app(VendorSignIn::class)->signIn($request, $request->validated());
        $ok = $data['authenticated'];

        return response()->json(['error' => ! $ok, 'message' => $ok ? 'Signed in.' : 'Complete vendor sign-in setup.', 'data' => $data, 'code' => $ok ? null : 'VENDOR_SETUP_REQUIRED'], $ok ? 200 : 409)->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request): JsonResponse
    {
        abort_unless(app(VendorOperatorAccess::class)->ownsSession($request), 403);
        $profile = DB::transaction(fn () => app(VendorOperatorAccess::class)->profile($request->user()));

        return response()->json(['error' => false, 'message' => '', 'data' => $profile, 'code' => null])->header('Cache-Control', 'private, no-store');
    }

    public function signOut(Request $request): JsonResponse
    {
        ExpectedAccount::check($request);
        abort_unless(app(VendorOperatorAccess::class)->ownsSession($request), 403);
        PortalSession::signOut($request, $request->user());

        return response()->json(['error' => false, 'message' => 'Signed out.', 'data' => null, 'code' => null])->header('Cache-Control', 'private, no-store');
    }
}
