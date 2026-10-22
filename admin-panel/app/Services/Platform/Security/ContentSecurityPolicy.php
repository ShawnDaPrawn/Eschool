<?php

declare(strict_types=1);

namespace App\Services\Platform\Security;

/**
 * The content security policy for the panel and the public pages, sent in
 * report-only mode (Content-Security-Policy-Report-Only) during Phase 0: the
 * browser reports what it would have blocked to POST /csp-report and blocks
 * nothing. It allows what the pages load today, found by walking them:
 *
 * - scripts and styles from the app itself, cdnjs and jsDelivr (jQuery
 *   plugins, flatpickr, font-awesome, Owl carousel on the website), and
 *   Google reCAPTCHA on the public forms; inline scripts and styles, which
 *   the Blade pages use throughout (moving them out is later work);
 * - Google Fonts;
 * - images from anywhere over https plus data: and blob: (school logos,
 *   avatars from ui-avatars.com, YouTube thumbnails, uploaded pictures);
 * - frames: YouTube (website videos), Google Maps (the contact page map) and
 *   reCAPTCHA;
 * - nothing for plugins, no <base> elsewhere, forms to the app only, and no
 *   framing by other sites.
 */
final class ContentSecurityPolicy
{
    public const HEADER = 'Content-Security-Policy-Report-Only';

    public const REPORT_PATH = '/csp-report';

    /** @var array<string, list<string>> */
    private const DIRECTIVES = [
        'default-src' => ["'self'"],
        'script-src' => ["'self'", "'unsafe-inline'", 'https://cdnjs.cloudflare.com', 'https://cdn.jsdelivr.net',
            'https://www.google.com/recaptcha/', 'https://www.gstatic.com/recaptcha/'],
        'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com', 'https://cdnjs.cloudflare.com', 'https://cdn.jsdelivr.net'],
        'font-src' => ["'self'", 'data:', 'https://fonts.gstatic.com', 'https://cdnjs.cloudflare.com'],
        'img-src' => ["'self'", 'data:', 'blob:', 'https:'],
        'media-src' => ["'self'", 'blob:', 'https:'],
        'frame-src' => ["'self'", 'https://www.youtube.com', 'https://www.youtube-nocookie.com', 'https://www.google.com',
            'https://maps.google.com'],
        'connect-src' => ["'self'"],
        'worker-src' => ["'self'", 'blob:'],
        'object-src' => ["'none'"],
        'base-uri' => ["'self'"],
        'form-action' => ["'self'"],
        'frame-ancestors' => ["'self'"],
    ];

    public static function header(): string
    {
        $parts = [];
        foreach (self::DIRECTIVES as $directive => $sources) {
            $parts[] = $directive.' '.implode(' ', $sources);
        }
        $parts[] = 'report-uri '.self::REPORT_PATH;
        $parts[] = 'report-to csp';

        return implode('; ', $parts);
    }

    /** The Reporting API endpoint the report-to directive names. */
    public static function reportingEndpoints(): string
    {
        return 'csp="'.self::REPORT_PATH.'"';
    }
}
