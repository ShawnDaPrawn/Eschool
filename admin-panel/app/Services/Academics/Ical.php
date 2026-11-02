<?php

declare(strict_types=1);

namespace App\Services\Academics;

use Carbon\CarbonImmutable;

/**
 * Writes iCalendar (RFC 5545) text: escaped values, CRLF line ends, lines
 * folded at 75 octets, times in UTC.
 */
final class Ical
{
    /**
     * @param  list<array{uid: string, start: CarbonImmutable, end: CarbonImmutable, summary: string, location: ?string, description: ?string}>  $events
     */
    public static function calendar(string $name, string $timezone, array $events): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'navigateed';
        $stamp = CarbonImmutable::now('UTC')->format('Ymd\THis\Z');
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//NavigateEd//Calendar feed//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
            'X-WR-CALNAME:'.self::text($name), 'X-WR-TIMEZONE:'.self::text($timezone), 'REFRESH-INTERVAL;VALUE=DURATION:PT6H', 'X-PUBLISHED-TTL:PT6H'];
        foreach ($events as $event) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:'.self::text($event['uid'].'@'.$host);
            $lines[] = 'DTSTAMP:'.$stamp;
            $lines[] = 'DTSTART:'.$event['start']->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTEND:'.$event['end']->utc()->format('Ymd\THis\Z');
            $lines[] = 'SUMMARY:'.self::text($event['summary']);
            if (($event['location'] ?? null) !== null && $event['location'] !== '') {
                $lines[] = 'LOCATION:'.self::text($event['location']);
            }
            if (($event['description'] ?? null) !== null && $event['description'] !== '') {
                $lines[] = 'DESCRIPTION:'.self::text($event['description']);
            }
            $lines[] = 'END:VEVENT';
        }
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map(self::fold(...), $lines))."\r\n";
    }

    public static function text(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], $value);
    }

    /** Folds a content line at 75 octets without splitting a UTF-8 character. */
    public static function fold(string $line): string
    {
        $out = '';
        $width = 0;
        foreach (mb_str_split($line) as $char) {
            $bytes = strlen($char);
            if ($width + $bytes > 75) {
                $out .= "\r\n ";
                $width = 1;
            }
            $out .= $char;
            $width += $bytes;
        }

        return $out;
    }
}
