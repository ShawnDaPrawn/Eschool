<?php

declare(strict_types=1);

namespace App\Services\Platform;

/**
 * CORS for the uploaded files under /storage, which the web server sends
 * without going through Laravel (so config/cors.php does not reach them).
 *
 * The origins are config/cors.php's: allowed_origins exactly, and
 * allowed_origins_patterns (localhost on any port for local installs). They
 * become one regular expression without delimiters, which the container's
 * Apache gets as STORAGE_CORS_ORIGINS (docker/storage-cors.conf) and the
 * `php artisan serve` router (server.php) uses for local development. Only
 * GET and HEAD are answered with Access-Control-Allow-Origin, and every
 * answer varies by Origin.
 */
final class StorageCors
{
    /** Matches no origin: what the web server uses when nothing is allowed. */
    public const NOTHING = '(?!)';

    /** The expression for config/cors.php's origins. */
    public static function fromConfig(): string
    {
        return self::pattern(
            (array) config('cors.allowed_origins', []),
            (array) config('cors.allowed_origins_patterns', []),
        );
    }

    /**
     * One expression, without delimiters, matching exactly the given
     * origins and the given delimited patterns.
     *
     * @param  array<int, string>  $origins
     * @param  array<int, string>  $patterns  e.g. "#^https?://localhost(:\d+)?$#"
     */
    public static function pattern(array $origins, array $patterns): string
    {
        $origins = array_values(array_filter(array_map('trim', $origins), fn (string $origin) => $origin !== '' && $origin !== '*'));
        $parts = [];
        if ($origins !== []) {
            $parts[] = '^('.implode('|', array_map(fn (string $origin) => preg_quote($origin, '~'), $origins)).')$';
        }
        foreach ($patterns as $delimited) {
            $body = self::body((string) $delimited);
            if ($body !== null) {
                $parts[] = $body;
            }
        }

        return $parts === [] ? self::NOTHING : implode('|', array_map(fn (string $part) => '(?:'.$part.')', $parts));
    }

    /** Whether the expression allows the origin, as the web server decides. */
    public static function allows(string $pattern, string $origin, string $method = 'GET'): bool
    {
        return in_array(strtoupper($method), ['GET', 'HEAD'], true)
            && $origin !== ''
            && preg_match('~'.str_replace('~', '\~', $pattern).'~', $origin) === 1;
    }

    /** "#^a$#i" becomes "(?i)^a$"; anything that is not a delimited pattern is skipped. */
    private static function body(string $delimited): ?string
    {
        if (strlen($delimited) < 2 || @preg_match($delimited, '') === false) {
            return null;
        }
        $delimiter = $delimited[0];
        $end = strrpos($delimited, $delimiter);
        if ($end === false || $end === 0) {
            return null;
        }
        $flags = substr($delimited, $end + 1);
        $body = substr($delimited, 1, $end - 1);

        return (str_contains($flags, 'i') ? '(?i)' : '').$body;
    }
}
