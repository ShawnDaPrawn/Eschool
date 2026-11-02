<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Saas\PlatformTwoFactor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * platform.can:{ability}: after platform.staff, the staff member's role must
 * hold the ability (config/platform_roles.php). Every /saas route but the
 * operator's own password names one. engineering/specs/platform/platform-roles.md.
 * Two-factor sign-in is checked here again (SA.2), so no ability opens
 * without it even if a route group forgot platform.staff.
 */
class EnsurePlatformAbility
{
    public function __construct(private readonly PlatformStaffAccess $access, private readonly PlatformTwoFactor $twoFactor) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $this->access->can($user, $ability) && $this->twoFactor->passed($request->hasSession() ? $request->session() : null, $user), 403);

        return $next($request);
    }
}
