<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * school.context: the school a signed-in request works for (the Build Bible,
 * section 3.1, Tenancy), set on CurrentSchool, which also sets Spatie's team
 * and the log context. Runs right after authentication (it is in the
 * middleware priority list before route model binding).
 *
 * The school is one of the user's active memberships, never a request
 * parameter on its own:
 * - the X-School-Id header (the apps) picks one; a school the user is not an
 *   active member of is refused with 403;
 * - the panel's school switcher stores the choice in the session; a stale
 *   choice falls back to the default;
 * - otherwise the user's first school (v1 apps send no header).
 *
 * A user with no active membership is refused (403 on the API; signed out
 * of the panel). Platform staff have none: they reach a school only through
 * audited impersonation, which sets CurrentSchool before this runs.
 *
 * school.context:optional sets the school when there is one and lets the
 * request through otherwise (signing out, changing one's own password).
 *
 * school.context:public is for routes anyone may call (public pages,
 * sign-in, the apps' public v1 endpoints): the signed-in user's school when
 * the request carries a session or token, else a school the X-School-Id
 * header names, else the default school (school 1 on a single-school
 * install). It never refuses.
 *
 * school.context:sign-in is the public mode for sign-in and password reset
 * routes: the school of the account the request names (its email or GR
 * number), so the legacy sign-in code reads that school's year and roles.
 * Choosing the school reveals nothing: the answer for a wrong password is
 * the same whichever school it is.
 */
class SetSchoolContext
{
    public function __construct(
        private readonly CurrentSchool $currentSchool,
        private readonly MembershipService $memberships,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, ?string $mode = null): Response
    {
        if ($mode === 'public' || $mode === 'sign-in') {
            $school = $mode === 'sign-in' ? $this->signInSchool($request) : null;
            $this->currentSchool->set($school ?? $this->publicSchool($request) ?? CurrentSchool::defaultId());

            return $next($request);
        }

        $user = $request->user();
        if (! $user instanceof User) {
            // Guests: the auth middleware refuses them.
            return $next($request);
        }
        if ($this->currentSchool->isImpersonating()) {
            return $next($request);
        }

        $school = $this->chosenSchool($request, $user) ?? $this->memberships->defaultSchool($user);
        if ($school === null) {
            if ($mode === 'optional') {
                return $next($request);
            }

            return $this->refuse($request, 'no_school_membership');
        }

        $this->currentSchool->set($school);
        // Roles loaded before the team was known would be another school's.
        $user->unsetRelation('roles')->unsetRelation('permissions');

        return $next($request);
    }

    private function signInSchool(Request $request): ?School
    {
        $login = $request->input('email', $request->input('gr_number'));
        if (! is_string($login) || trim($login) === '') {
            return null;
        }
        $user = User::query()->where('email', trim($login))->first();
        if ($user === null) {
            return null;
        }
        try {
            return $this->chosenSchool($request, $user) ?? $this->memberships->defaultSchool($user);
        } catch (AccessDeniedHttpException) {
            return $this->memberships->defaultSchool($user);
        }
    }

    private function publicSchool(Request $request): School|int|null
    {
        $user = $request->user() ?? ($request->is('api/*') ? Auth::guard('sanctum')->user() : null);
        if ($user instanceof User) {
            try {
                $school = $this->chosenSchool($request, $user);
            } catch (AccessDeniedHttpException) {
                $school = null;
            }
            if (($school ??= $this->memberships->defaultSchool($user)) !== null) {
                return $school;
            }
        }

        $header = (string) $request->headers->get((string) config('tenancy.header'), '');

        return ctype_digit($header) ? School::query()->active()->find((int) $header) : null;
    }

    private function chosenSchool(Request $request, User $user): ?School
    {
        $header = $request->headers->get((string) config('tenancy.header'));
        if ($header !== null && $header !== '') {
            $school = ctype_digit($header) ? $this->membershipSchool($user, (int) $header) : null;

            return $school ?? throw new AccessDeniedHttpException(trans('school_not_available'));
        }

        if ($request->hasSession()) {
            $chosen = $request->session()->get((string) config('tenancy.session_key'));
            if (is_numeric($chosen)) {
                $school = $this->membershipSchool($user, (int) $chosen);
                if ($school === null) {
                    $request->session()->forget((string) config('tenancy.session_key'));
                }

                return $school;
            }
        }

        return null;
    }

    private function membershipSchool(User $user, int $schoolId): ?School
    {
        return $this->memberships->active($user)->firstWhere('school_id', $schoolId)?->school;
    }

    private function refuse(Request $request, string $message): Response
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            throw new AccessDeniedHttpException(trans($message));
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['message' => trans($message)]);
    }
}
