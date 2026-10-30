<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Saas\SupportAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * support.session: a Navigate Ed staff member's audited support session in
 * one school (Action Plan SA.9). After auth, on the support-session routes
 * only, and before anything reads a school's rows:
 *
 * 1. the request must be a read (GET or HEAD): every write is refused here,
 *    whatever the route, before the grant is even looked up;
 * 2. the grant in the URL must be the signed-in staff member's own, open now,
 *    read-only, for an active school, with support.access still on their
 *    role (SupportAccess::session, fresh rows on every request);
 * 3. the school comes from the grant alone, never from a header, the session
 *    or a parameter, and is set with CurrentSchool::supportSession(): the
 *    impersonation flag every restricted authority refuses, no membership and
 *    no role (Spatie's team is the school, where the staff member holds none);
 * 4. a page that answers successfully is logged: when, the route name and the
 *    record id from its route, never content or the query string.
 *
 * There is deliberately no school.context or platform.staff here: both would
 * read memberships or refuse impersonation; SupportAccess::session makes the
 * same platform checks before the school is set.
 */
class EnsureSupportSession
{
    public function __construct(private readonly SupportAccess $access, private readonly CurrentSchool $currentSchool) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe() || ! in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            Log::notice('support_session_write_refused', ['route' => $request->route()?->getName(), 'user_id' => $request->user()?->getAuthIdentifier()]);
            abort(403, __('A support session is read-only.'));
        }
        $grantId = $request->route('grant');
        abort_unless(is_numeric($grantId), 404);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $grant = $this->access->session($user, (int) $grantId);

        $this->currentSchool->supportSession(School::query()->findOrFail($grant->school_id), (int) $grant->id);
        // Nothing loaded before the school was known may carry another team's roles.
        $user->unsetRelation('roles')->unsetRelation('permissions');
        Auth::guard('web')->setUser($user);

        $response = $next($request);
        if ($response->getStatusCode() < 400) {
            [$type, $id] = $this->record($request);
            $this->access->recordView($grant, $user, (string) $request->route()?->getName(), $type, $id);
        }
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    /**
     * The record a page shows, from its route parameters (the grant aside).
     *
     * @return array{0: ?string, 1: ?int}
     */
    private function record(Request $request): array
    {
        foreach ((array) $request->route()?->parameters() as $name => $value) {
            if ($name !== 'grant' && is_numeric($value)) {
                return [(string) $name, (int) $value];
            }
        }

        return [null, null];
    }
}
