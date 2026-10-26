<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Cards\CardEncodingPanelScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeCardEncodingPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        app(CardEncodingPanelScope::class)->authorize($request);

        return $next($request);
    }
}
