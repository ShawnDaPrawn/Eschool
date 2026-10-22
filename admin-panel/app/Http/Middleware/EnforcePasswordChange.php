<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * An account flagged with users.password_change_required must choose a new
 * password before using the app API, when the app says it can ask for one.
 *
 * Installed apps don't send the header and keep working (their sign-in reply
 * carries password_change_required: true, which they ignore). A client that
 * sends "X-Supports-Password-Change: 1" gets HTTP 403 with code
 * PASSWORD_CHANGE_REQUIRED on the role groups until POST api/change-password
 * (outside those groups) succeeds.
 */
class EnforcePasswordChange
{
    public const HEADER = 'X-Supports-Password-Change';

    public const CODE = 'PASSWORD_CHANGE_REQUIRED';

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user instanceof User && $user->password_change_required && $request->header(self::HEADER) === '1') {
            return response()->json([
                'error' => true,
                'message' => trans('password_change_required'),
                'data' => null,
                'code' => self::CODE,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
