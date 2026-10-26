<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Platform\Saas\PlatformStaffAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * platform.staff: the platform admin area (/saas) opens only to an active
 * platform staff identity. It runs after auth and before any record lookup,
 * so a school's own administrators cannot learn from the answer which
 * schools exist. There is deliberately no school.context here: platform
 * staff belong to no school.
 */
class EnsurePlatformStaff
{
    public function __construct(private readonly PlatformStaffAccess $access) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->access->actor($request->user());

        return $next($request);
    }
}
