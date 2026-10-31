<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Saas\PlatformTwoFactor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * platform.staff: the platform admin area (/saas) opens only to an active
 * platform staff identity. It runs after auth and before any record lookup,
 * so a school's own administrators cannot learn from the answer which
 * schools exist. There is deliberately no school.context here: platform
 * staff belong to no school.
 *
 * It also requires two-factor sign-in (SA.2): a staff member who has not set
 * up an authenticator app, or has not passed the challenge in this session,
 * is sent to do so before any /saas page opens. Purposes:
 *  (none)      every HQ page: active staff who passed two-factor;
 *  password    the operator's own password: also before a forced password
 *              change, and two-factor first only once it is set up;
 *  two-factor  setting up the app and the challenge themselves.
 * For every purpose, a session that signed in before the staff member's last
 * two-factor reset is signed out (PlatformTwoFactor::current()).
 */
class EnsurePlatformStaff
{
    public function __construct(private readonly PlatformStaffAccess $access, private readonly PlatformTwoFactor $twoFactor) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, ?string $purpose = null): Response
    {
        $actor = $this->access->actor($request->user(), $purpose === 'password' || $purpose === 'two-factor');
        Auth::guard('web')->setUser($actor);
        $request->setUserResolver(fn () => $actor);
        // A session from before a two-factor reset signs in again, for every
        // purpose (setup and the own password included), whatever the driver.
        if (! $this->twoFactor->current($request->hasSession() ? $request->session() : null, $actor)) {
            return $this->twoFactor->signOut($request);
        }
        // Any other purpose than these two, a misspelt one included, needs two-factor.
        $needsTwoFactor = $purpose !== 'two-factor' && ($purpose !== 'password' || $this->twoFactor->enrolled($actor));
        if ($needsTwoFactor && ! $this->twoFactor->passed($request->hasSession() ? $request->session() : null, $actor)) {
            return $this->twoFactor->redirect($request, $actor);
        }
        if ($purpose === 'password' && $request->isMethod('POST')) {
            abort_if($request->query->count() !== 0, 400);
            // Never flash old/new password fields, even for an HTML Accept.
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
