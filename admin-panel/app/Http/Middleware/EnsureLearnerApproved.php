<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Academics\Transitions\LearnerAcademicAccess;
use App\Services\Platform\Identity\ApprovalService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLearnerApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null) {
            app(ApprovalService::class)->assertAccess($request->user());
            app(LearnerAcademicAccess::class)->assertRequest($request);
        }

        return $next($request);
    }
}
