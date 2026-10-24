<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Policies\Academics\TimetablePolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeTimetable
{
    public function handle(Request $request, Closure $next, string $operation): Response
    {
        app(TimetablePolicy::class)->authorise($request->user(), $operation, $request->filled('learner_id') ? (int) $request->input('learner_id') : null);

        return $next($request);
    }
}
