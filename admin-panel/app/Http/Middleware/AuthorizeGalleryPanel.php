<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Gallery\GalleryPanelScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeGalleryPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        app(GalleryPanelScope::class)->authorize($request);

        return $next($request);
    }
}
