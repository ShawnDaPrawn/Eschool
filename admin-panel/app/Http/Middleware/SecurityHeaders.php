<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Platform\Security\ContentSecurityPolicy;
use Closure;
use Illuminate\Http\Request;

/**
 * Browser protections for every page: no framing by other sites (clickjacking),
 * no MIME sniffing, and no full URLs leaked in the Referer header. HSTS is only
 * sent once the site is already being served over HTTPS.
 *
 * HTML pages (the panel and the public website) also carry the content
 * security policy, in report-only mode for now (ContentSecurityPolicy).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        // Trusted Blade templates opt their inline scripts into the scoped policy.
        // Never add nonces by rewriting user-controlled response HTML.
        $nonce = base64_encode(random_bytes(24));
        $request->attributes->set('csp_nonce', $nonce);
        $response = $next($request);

        $defaults = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];
        if ($request->isSecure()) {
            $defaults['Strict-Transport-Security'] = 'max-age=31536000';
        }
        if (str_starts_with(strtolower((string) $response->headers->get('Content-Type', 'text/html')), 'text/html')
            && ! $response->headers->has('Content-Security-Policy')) {
            $enforced = $request->routeIs('wallet-finance.*', 'vendor-catalogues.*');
            $defaults[$enforced ? 'Content-Security-Policy' : ContentSecurityPolicy::HEADER] = ContentSecurityPolicy::header($enforced ? $nonce : null);
            $defaults['Reporting-Endpoints'] = ContentSecurityPolicy::reportingEndpoints();
        }
        foreach ($defaults as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
