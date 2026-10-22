<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * POST /csp-report: browsers send what the report-only content security policy
 * would have blocked. Each violation is logged as one warning with the
 * directive, where the blocked thing came from (scheme, host and path, never
 * a query string), the page path, the script file and line, and the signed-in
 * user's id. Nothing else from the report (no sample text) is kept.
 *
 * Accepts the report-uri format ({"csp-report": {...}}) and the Reporting
 * API format ([{"type": "csp-violation", "body": {...}}]). No CSRF token
 * (browsers send none); rate limited per address (throttle:csp).
 */
class CspReportController extends Controller
{
    /** Bytes of a report body read; real reports are well under 2 KB. */
    private const MAX_BODY = 16_384;

    /** Violations logged from one request. */
    private const MAX_REPORTS = 10;

    /** Values the browser puts in blocked-uri that are keywords, not URLs. */
    private const KEYWORDS = ['inline', 'eval', 'data', 'blob', 'self', 'wasm-eval', 'trusted-types-policy', 'trusted-types-sink'];

    public function __invoke(Request $request): Response
    {
        $decoded = json_decode(substr((string) $request->getContent(), 0, self::MAX_BODY), true);

        $reports = [];
        if (is_array($decoded) && is_array($decoded['csp-report'] ?? null)) {
            $reports[] = $decoded['csp-report'];
        } elseif (is_array($decoded) && array_is_list($decoded)) {
            foreach ($decoded as $entry) {
                if (is_array($entry) && ($entry['type'] ?? null) === 'csp-violation' && is_array($entry['body'] ?? null)) {
                    $reports[] = $entry['body'];
                }
            }
        }

        foreach (array_slice($reports, 0, self::MAX_REPORTS) as $report) {
            Log::warning('Content security policy violation', [
                'directive' => self::text($report['effective-directive'] ?? $report['effectiveDirective'] ?? $report['violated-directive'] ?? null),
                'blocked' => self::where($report['blocked-uri'] ?? $report['blockedURL'] ?? null),
                'page' => self::path($report['document-uri'] ?? $report['documentURL'] ?? null),
                'source' => self::where($report['source-file'] ?? $report['sourceFile'] ?? null),
                'line' => is_numeric($report['line-number'] ?? $report['lineNumber'] ?? null) ? (int) ($report['line-number'] ?? $report['lineNumber']) : null,
                'disposition' => self::text($report['disposition'] ?? null),
                'user_id' => $request->user()?->id,
            ]);
        }

        return response()->noContent();
    }

    /** A keyword as it is, or a URL as scheme://host/path (no query, no fragment). */
    private static function where(mixed $value): ?string
    {
        $value = self::text($value);
        if ($value === null || in_array($value, self::KEYWORDS, true)) {
            return $value;
        }
        $parts = parse_url($value);
        if (! is_array($parts) || ! isset($parts['scheme'])) {
            return null;
        }

        return self::text($parts['scheme'].'://'.($parts['host'] ?? '').($parts['path'] ?? ''));
    }

    private static function path(mixed $value): ?string
    {
        $path = parse_url((string) self::text($value), PHP_URL_PATH);

        return is_string($path) ? self::text($path) : null;
    }

    private static function text(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return mb_substr((string) preg_replace('/[\x00-\x1F\x7F]/', '', $value), 0, 200);
    }
}
