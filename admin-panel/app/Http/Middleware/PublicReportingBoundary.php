<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Wellbeing\PublicReportingPage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicReportingBoundary
{
    public function handle(Request $request, Closure $next): Response
    {
        app(PublicReportingPage::class)->school((string) $request->route('schoolSlug'));
        abort_if($request->isMethod('POST') && $request->query->count() > 0, 422, 'Use the private form, never URL parameters.');
        abort_if($request->query->has('secret'), 422, 'Enter the private code in the form, never in a URL.');

        return $next($request);
    }
}
