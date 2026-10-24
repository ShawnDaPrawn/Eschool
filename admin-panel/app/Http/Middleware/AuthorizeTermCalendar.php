<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Policies\Academics\TermPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeTermCalendar
{
    public function handle(Request $request, Closure $next, string $operation): Response
    {
        if ($operation === 'apply') {
            $operation = $request->boolean('apply') ? 'create' : 'list';
        }
        app(TermPolicy::class)->authorise($request->user(), $operation);

        return $next($request);
    }
}
