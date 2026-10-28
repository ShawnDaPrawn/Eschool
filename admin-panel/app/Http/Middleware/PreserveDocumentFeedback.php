<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Background reads must not consume the next document's one-time save feedback. */
class PreserveDocumentFeedback
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $destination = $request->header('Sec-Fetch-Dest');
        $type = strtolower((string) $response->headers->get('Content-Type'));
        $background = $request->ajax() || $request->expectsJson() || ($destination !== null && ! in_array($destination, ['document', 'iframe', 'frame'], true)) || str_contains($type, '/json') || str_contains($type, '+json') || str_contains($type, 'javascript');
        if ($background && $request->hasSession()) {
            $old = $request->session()->get('_flash.old', []);
            if (is_array($old)) {
                $request->session()->keep(array_values(array_intersect($old, ['success', 'error', 'warning', 'status', 'message'])));
            }
        }

        return $response;
    }
}
