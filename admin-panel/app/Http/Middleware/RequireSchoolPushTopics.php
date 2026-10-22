<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Notifications\PushTopics;
use App\Services\ResponseService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * On the v1 app sign-in endpoints. App versions from before Phase 1 join the
 * shared push topic names (allAndroid, parentsIOS and so on), which carry
 * the first school's notices only (PushTopics). On such a version, a user
 * who is not a member of that school would get another school's notices,
 * so their sign-in is refused unless the app says it joins each school's own
 * topics (X-Push-Topics: school). The answer asks them to update the app,
 * and the token the endpoint issued is revoked.
 *
 * It runs after the endpoint, so a wrong password is answered as before and
 * the check reveals nothing about an account. A web portal's sign-in
 * returns no token and is never refused here (the portals join no topics).
 */
class RequireSchoolPushTopics
{
    public const HEADER = 'X-Push-Topics';

    public const SCHOOL_TOPICS = 'school';

    public function __construct(private readonly MembershipService $memberships) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->headers->get(self::HEADER) === self::SCHOOL_TOPICS
            || ! $response instanceof JsonResponse || ! $response->isSuccessful()) {
            return $response;
        }

        $body = $response->getData(true);
        $plain = is_array($body) && ($body['error'] ?? true) === false ? ($body['token'] ?? null) : null;
        $token = is_string($plain) && $plain !== '' ? PersonalAccessToken::findToken($plain) : null;
        $user = $token?->tokenable;
        if (! $user instanceof User || $this->memberships->isActiveMember($user, PushTopics::LEGACY_SCHOOL_ID)) {
            return $response;
        }

        $token->delete();

        return ResponseService::errorResponse(trans('update_app_to_sign_in'));
    }
}
