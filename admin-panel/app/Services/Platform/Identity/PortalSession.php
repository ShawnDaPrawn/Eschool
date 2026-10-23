<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Sign-in for the web portals (Sanctum's cookie mode) next to the apps'
 * bearer tokens.
 *
 * A request from a portal origin (SANCTUM_STATEFUL_DOMAINS, sent by the
 * browser as Origin or Referer) runs with a session and the CSRF check. Its
 * sign-in logs the `web` guard in on a fresh session instead of issuing a
 * token, so the browser holds an HttpOnly session cookie and no bearer token
 * ever reaches the portal's JavaScript. Every other request (the phone apps
 * send no Origin) gets an app token from AppTokens, exactly as before.
 *
 * The portals are not devices in AppTokens' sense: signing in or out of a
 * portal leaves the account's phone apps signed in (and keeps the phone's
 * FCM id), and signing out ends the browser session. A request that carries
 * an app token (a portal built with WEB_AUTH_MODE=bearer) signs that app out
 * as AppTokens says.
 */
final class PortalSession
{
    /** Request attribute set once a portal sign-in has completed. */
    private const SIGNED_IN = 'portal_signed_in';

    /** True for a request from a web portal origin that runs with a session. */
    public static function isPortal(Request $request): bool
    {
        return $request->hasSession() && EnsureFrontendRequestsAreStateful::fromFrontend($request);
    }

    /**
     * Completes a sign-in whose checks have passed. For a portal: the web
     * guard on a regenerated session (new id and CSRF token), and an empty
     * token in the v1 answer. For an app: a new token for that app.
     */
    public static function signIn(Request $request, User $user, string $app): string
    {
        app(ApprovalService::class)->assertAccess($user);

        if (! self::isPortal($request)) {
            return AppTokens::issue($user, $app);
        }

        $portal = match ($app) {
            AppTokens::PARENT => 'parent',
            AppTokens::TEACHER => 'teacher',
            default => 'learner',
        };
        abort_unless(app(ModuleGate::class)->allows('web_'.$portal), 403, 'Web access is unavailable at this school.');

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        // Sanctum's AuthenticateSession compares this on every request, so a
        // password change from now on ends the session.
        $request->session()->put('password_hash_web', $user->getAuthPassword());
        $request->attributes->set(self::SIGNED_IN, true);

        return '';
    }

    /**
     * After a portal sign-in request: when it did not complete (wrong
     * password, wrong profile, deactivated account), no session is left
     * signed in. Auth::attempt logs the guard in before the endpoint's own
     * checks run, so without this a refused sign-in kept the session. An
     * error answer after signIn() (a server error) also ends it.
     */
    public static function endIfRefused(Request $request, bool $answeredOk = true): void
    {
        if (self::isPortal($request) && (! $answeredOk || ! $request->attributes->get(self::SIGNED_IN, false))) {
            self::end($request);
        }
    }

    /**
     * Signs out: revokes the app's tokens when the request carried one (and
     * forgets that phone's FCM id), and ends the portal session.
     */
    public static function signOut(Request $request, User $user): void
    {
        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $user->update(['fcm_id' => '']);
            AppTokens::signOut($user, $token);
        }

        if ($request->hasSession()) {
            self::end($request);
        }
    }

    private static function end(Request $request): void
    {
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logoutCurrentDevice();
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
