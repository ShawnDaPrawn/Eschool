<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\FamilyForms\FamilyPanelScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeFamilyFormsPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        app(FamilyPanelScope::class)->authorize($request);

        return $next($request);
    }
}
