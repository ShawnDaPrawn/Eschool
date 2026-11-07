<?php

declare(strict_types=1);

namespace App\Services\OpenStandards\OneRoster;

use RuntimeException;

/**
 * One OneRoster CSV file: UTF-8 without a byte order mark, a header row, then
 * one line per record (RFC 4180 quoting; list fields such as termSourcedIds
 * are quoted because they hold commas).
 */
final class OneRosterCsv
{
    /**
     * @param  list<string>  $headers
     * @param  list<array<string, string>>  $rows
     */
    public static function render(array $headers, array $rows): string
    {
        $stream = fopen('php://temp', 'w+b');
        if ($stream === false) {
            throw new RuntimeException('The CSV could not be written.');
        }
        try {
            fputcsv($stream, array_map(self::safeCell(...), $headers), ',', '"', '', "\r\n");
            foreach ($rows as $row) {
                fputcsv($stream, array_map(fn (string $h): string => self::safeCell((string) ($row[$h] ?? '')), $headers), ',', '"', '', "\r\n");
            }
            rewind($stream);

            return (string) stream_get_contents($stream);
        } finally {
            fclose($stream);
        }
    }

    /** Refuse active spreadsheet cells; never change a roster identity or code. */
    private static function safeCell(string $value): string
    {
        $plain = preg_replace('/\A[\s\x{FEFF}\x00-\x1f]*/u', '', $value);
        if ($plain === null || str_contains($value, "\0") || preg_match('/\A[=+@]/', $plain) === 1
            || (str_starts_with($plain, '-') && preg_match('/\A-\d+(?:\.\d+)?\z/', $plain) !== 1)) {
            throw new UnsafeRosterValue;
        }

        return $value;
    }
}
