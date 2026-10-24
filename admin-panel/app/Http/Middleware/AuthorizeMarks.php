<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Policies\Academics\MarksPolicy;
use App\Services\Academics\ReleasedMarks;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeMarks
{
    public function handle(Request $request, Closure $next, string $operation): Response
    {
        if ($operation === 'family') {
            app(ReleasedMarks::class)->learner($request->user(), (int) $request->route('learner'));
        } else {
            app(MarksPolicy::class)->group($request->user(), (int) $request->route('group'), $operation);
        }

        return $next($request);
    }
}
