<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Platform\Identity\PortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * On the app sign-in endpoints: a web portal's sign-in that the endpoint
 * refused leaves no signed-in session behind (see PortalSession). Requests
 * from the phone apps are not touched.
 */
class EndRefusedPortalSignIn
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        PortalSession::endIfRefused($request, $response->isSuccessful());

        return $response;
    }
}
