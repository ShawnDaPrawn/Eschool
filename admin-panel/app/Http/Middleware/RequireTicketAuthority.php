<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Commerce\TicketHolders;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTicketAuthority
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() !== null, 401);
        abort_if($request->query->count() > 0, 422);
        app(TicketHolders::class)->authorizeTransfer($request->user(), (int) $request->route('ticket'), $request->all());

        return $next($request);
    }
}
