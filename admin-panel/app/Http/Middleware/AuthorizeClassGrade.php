<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Policies\Academics\ClassGradePolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Check fresh permission before a saved idempotent response can be replayed. */
class AuthorizeClassGrade
{
    public function handle(Request $request, Closure $next): Response
    {
        app(ClassGradePolicy::class)->authorise($request->user(), edit: ! $request->isMethodSafe());

        return $next($request);
    }
}
