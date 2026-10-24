<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Policies\Academics\SubjectStandardPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeSubjectStandard
{
    public function handle(Request $request, Closure $next, string $operation): Response
    {
        app(SubjectStandardPolicy::class)->authorise($request->user(), $operation === 'read' ? null : $operation);

        return $next($request);
    }
}
