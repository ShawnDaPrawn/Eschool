<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Academics\AttendanceRegister;
use App\Policies\Academics\RegisterPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\ReleasedMarks;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeRegister
{
    public function handle(Request $request, Closure $next, string $operation): Response
    {
        if ($operation === 'family') {
            app(ReleasedMarks::class)->learner($request->user(), (int) $request->route('learner'));
        } elseif ($operation === 'manage') {
            app(AcademicAuthority::class)->manage($request->user());
        } elseif ($request->route('register') !== null) {
            app(RegisterPolicy::class)->authorise($request->user(), AttendanceRegister::findOrFail((int) $request->route('register')), $operation === 'sync');
        } else {
            app(AcademicAuthority::class)->staff($request->user());
        }

        return $next($request);
    }
}
