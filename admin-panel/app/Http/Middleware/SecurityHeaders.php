<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Browser protections for every page: no framing by other sites (clickjacking),
 * no MIME sniffing, and no full URLs leaked in the Referer header. HSTS is only
 * sent once the site is already being served over HTTPS.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $defaults = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];
        if ($request->isSecure()) {
            $defaults['Strict-Transport-Security'] = 'max-age=31536000';
        }
        foreach ($defaults as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
