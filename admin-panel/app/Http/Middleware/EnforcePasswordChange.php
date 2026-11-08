<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * An account flagged with users.password_change_required must choose a new
 * password before using private v1/v2 APIs. Client headers are not authority.
 * Session context and policy setup remain available so existing apps can
 * reach their password screen; password changes and sign-out stay open.
 */
class EnforcePasswordChange
{
    public const HEADER = 'X-Supports-Password-Change';

    public const CODE = 'PASSWORD_CHANGE_REQUIRED';

    private const SETUP = [
        'api/change-password', 'api/logout', 'api/v2/me/context',
        'api/parent/policy-acceptance', 'api/teacher/policy-acceptance',
        'api/v2/facilitator/sign-out', 'api/v2/vendor/sign-out',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user instanceof User && $user->password_change_required && ! $request->is(self::SETUP)) {
            if ($request->is('api/v2', 'api/v2/*')) {
                return ApiV2ExceptionRenderer::envelope(Response::HTTP_FORBIDDEN, trans('password_change_required'))
                    ->header('Cache-Control', 'no-store, private');
            }

            return response()->json([
                'error' => true,
                'message' => trans('password_change_required'),
                'data' => null,
                'code' => self::CODE,
            ], Response::HTTP_FORBIDDEN)->header('Cache-Control', 'no-store, private');
        }

        return $next($request);
    }
}
