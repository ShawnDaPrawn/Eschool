<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Activities\Chat\GroupChatPanelScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeGroupChatPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        app(GroupChatPanelScope::class)->authorize($request);

        return $next($request);
    }
}
