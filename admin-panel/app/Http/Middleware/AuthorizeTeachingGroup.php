<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Policies\Academics\TeachingGroupPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeTeachingGroup
{
    public function handle(Request $request, Closure $next): Response
    {
        $operation = $request->isMethodSafe() && ! str_contains((string) $request->route()?->getName(), 'allocations') ? 'read' : 'manage';
        app(TeachingGroupPolicy::class)->authorise($request->user(), $operation, $request->route('group') === null ? null : (int) $request->route('group'));

        return $next($request);
    }
}
