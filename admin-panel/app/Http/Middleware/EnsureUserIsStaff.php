<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Commerce\VendorOperatorAccess;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

/**
 * The API v2 staff group: the Super Admin, Teachers and every role a school
 * creates for its staff, which is any role except the app-only Parent and
 * Student (the panel's rule, see CheckRole). What each staff member may do is
 * decided by policies and permissions inside the group.
 */
class EnsureUserIsStaff
{
    /** Roles that are never staff. */
    public const APP_ONLY_ROLES = ['Parent', 'Student', 'Vendor'];

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            throw UnauthorizedException::notLoggedIn();
        }

        abort_if(app(VendorOperatorAccess::class)->ownsSession($request), 403);

        if ($user->getRoleNames()->diff(self::APP_ONLY_ROLES)->isEmpty()) {
            throw UnauthorizedException::forRoles(['staff']);
        }

        return $next($request);
    }
}
