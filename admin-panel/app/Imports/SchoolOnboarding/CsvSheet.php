<?php

declare(strict_types=1);

namespace App\Imports\SchoolOnboarding;

use Illuminate\Validation\ValidationException;

/**
 * A bounded CSV read for onboarding imports: UTF-8 (a byte-order mark is
 * dropped), comma, semicolon or tab separated, named unique headers, at most
 * MAX_ROWS rows. Blank lines are skipped; short rows are padded, long ones
 * refused. engineering/specs/people/school-imports.md.
 */
final class CsvSheet
{
    public const MAX_BYTES = 1048576;

    public const MAX_ROWS = 1000;

    public const MAX_COLUMNS = 60;

    /**
     * @param  list<string>  $headers
     * @param  list<array<string, string>>  $rows  each row keyed by header, with its line number under self::LINE
     */
    private function __construct(public readonly array $headers, public readonly array $rows) {}

    /** Key under which each row keeps its line number in the file. */
    public const LINE = "\0line";

    public static function read(string $csv): self
    {
        if (str_starts_with($csv, "\xEF\xBB\xBF")) {
            $csv = substr($csv, 3);
        }
        if (trim($csv) === '') {
            self::invalid('The file is empty.');
        }
        if (strlen($csv) > self::MAX_BYTES || str_contains($csv, "\0")) {
            self::invalid('The file is too large (at most 1 MB) or is not a text file.');
        }
        if (! mb_check_encoding($csv, 'UTF-8')) {
            // Spreadsheet programs often save CSV as Windows-1252.
            $csv = mb_convert_encoding($csv, 'UTF-8', 'Windows-1252');
        }
        $first = strtok($csv, "\r\n") ?: '';
        $counts = [',' => substr_count($first, ','), ';' => substr_count($first, ';'), "\t" => substr_count($first, "\t")];
        arsort($counts);
        $delimiter = (string) array_key_first($counts);

        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $csv);
        rewind($stream);
        try {
            $headers = fgetcsv($stream, 0, $delimiter, '"', '');
            if ($headers === false || $headers === [null]) {
                self::invalid('The file needs a header row naming its columns.');
            }
            $headers = array_map(fn ($header) => trim(preg_replace('/\s+/', ' ', (string) $header) ?? ''), $headers);
            while ($headers !== [] && end($headers) === '') {
                array_pop($headers);
            }
            if ($headers === [] || count($headers) > self::MAX_COLUMNS || in_array('', $headers, true)
                || count(array_unique(array_map('mb_strtolower', $headers))) !== count($headers)) {
                self::invalid('Every column needs a different name in the header row (at most 60 columns).');
            }
            $rows = [];
            $line = 1;
            while (($cells = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
                $line++;
                if ($cells === [null] || implode('', array_map(fn ($cell) => trim((string) $cell), $cells)) === '') {
                    continue;
                }
                while (count($cells) > count($headers) && trim((string) end($cells)) === '') {
                    array_pop($cells);
                }
                if (count($cells) > count($headers)) {
                    self::invalid('Line :line has more values than the header has columns.', ['line' => $line]);
                }
                if (count($rows) >= self::MAX_ROWS) {
                    self::invalid('Import at most :max rows at a time. Split the file and import each part.', ['max' => self::MAX_ROWS]);
                }
                $cells = array_pad(array_map(fn ($cell) => trim((string) $cell), $cells), count($headers), '');
                $rows[] = array_combine($headers, $cells) + [self::LINE => (string) $line];
            }
            if ($rows === []) {
                self::invalid('The file has a header but no rows.');
            }

            return new self($headers, $rows);
        } finally {
            fclose($stream);
        }
    }

    /** @param array<string, int|string> $replace */
    private static function invalid(string $message, array $replace = []): never
    {
        throw ValidationException::withMessages(['csv' => __($message, $replace)]);
    }
}
