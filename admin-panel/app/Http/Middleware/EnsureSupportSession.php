<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Saas\SupportAccess;
use App\Services\Platform\Saas\SupportSessionPages;
use App\Services\Platform\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * support.session: a Navigate Ed staff member's audited support session in
 * one school (Action Plan SA.9). After auth, on the support-session routes
 * only, and before anything reads a school's rows:
 *
 * 1. the request must be a read: GET or HEAD, or a POST on the one read
 *    route that takes its input in the body (the people search, so the
 *    words stay out of URLs). Every other request is refused here, before
 *    the grant is looked up, and so is one another site started
 *    (Sec-Fetch-Site: cross-site); refusals are logged, never stored;
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
 * There is no school.context here. SupportAccess checks the grant first, and
 * the platform staff guard checks sign-in before the school is bound. No
 * platform check runs after the request becomes an impersonated school read.
 */
class EnsureSupportSession
{
    /** Reads sent as a POST so their input stays out of URLs: the people search. Nothing else but GET and HEAD passes. */
    private const READ_POSTS = ['support-session.people.search'];

    public function __construct(private readonly SupportAccess $access, private readonly CurrentSchool $currentSchool, private readonly EnsurePlatformStaff $staff) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $grantId = $request->route('grant');
        $grantId = is_numeric($grantId) ? (int) $grantId : null;
        $user = $request->user();
        $readPost = $request->isMethod('POST') && in_array($request->route()?->getName(), self::READ_POSTS, true);
        if (! $readPost && ! in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            SupportAccess::refused($grantId, $user, 'write');
            abort(403, __('A support session is read-only.'));
        }
        // A link or form from another site never opens a page (and so never writes a view row); browsers say so in Sec-Fetch-Site.
        if ($request->headers->get('Sec-Fetch-Site') === 'cross-site') {
            SupportAccess::refused($grantId, $user, 'cross_site');
            abort(403);
        }
        abort_unless($grantId !== null, 404);
        abort_unless($user instanceof User, 403);
        $grant = $this->access->session($user, (int) $grantId);

        // Reuse the platform sign-in guard before binding the school. This also
        // requires the current two-factor proof once SA.2 is installed.
        return $this->staff->handle($request, function (Request $request) use ($next, $grant): Response {
            $user = $request->user();
            abort_unless($user instanceof User, 403);
            $this->currentSchool->supportSession(School::query()->findOrFail($grant->school_id), (int) $grant->id);
            // Nothing loaded before the school was known may carry another team's roles.
            $user->unsetRelation('roles')->unsetRelation('permissions');
            Auth::guard('web')->setUser($user);

            $response = $next($request);
            if ($response->getStatusCode() < 400) {
                [$type, $id] = $this->record($request);
                $listed = $request->attributes->get(SupportSessionPages::LISTED);
                $this->access->recordView($grant, $user, (string) $request->route()?->getName(), $type, $id, is_array($listed) ? $listed : null);
            }
            $response->headers->set('Cache-Control', 'no-store, private');

            return $response;
        });
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
