<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Platform\Saas\PlatformStaffAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * platform.can:{ability}: after platform.staff, the staff member's role must
 * hold the ability (config/platform_roles.php). Every /saas route but the
 * operator's own password names one. engineering/specs/platform/platform-roles.md.
 */
class EnsurePlatformAbility
{
    public function __construct(private readonly PlatformStaffAccess $access) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        abort_unless($this->access->can($request->user(), $ability), 403);

        return $next($request);
    }
}
