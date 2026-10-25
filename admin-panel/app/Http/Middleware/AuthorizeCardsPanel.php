<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Cards\CardPanelScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeCardsPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        app(CardPanelScope::class)->authorize($request);

        return $next($request);
    }
}
