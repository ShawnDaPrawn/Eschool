<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Consultations\ConsultationPanelScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeConsultationPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        app(ConsultationPanelScope::class)->authorize($request);

        return $next($request);
    }
}
