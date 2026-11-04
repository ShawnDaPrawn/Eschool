<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking;

/**
 * Reads numbers as South African learners write them (Action Plan 3.16):
 * a decimal comma or point (3,5 or 3.5), digit groups separated by spaces
 * (1 000 000), and scientific notation (3 × 10^8, 3x10^8, 3e8, 10^-3).
 * A comma is always a decimal separator, never digit grouping.
 *
 * Each number keeps what marking needs: its value, its decimal places and
 * its significant figures (a range when trailing zeros of a whole number
 * are ambiguous, as in 1200).
 */
final class Numbers
{
    private const BODY = '(?<int>\d{1,3}(?: \d{3})+(?!\d)|\d{1,15})(?:[.,](?<frac>\d{1,15}))?(?:\s*(?:[x×*·]\s*10\s*\^\s*\(?\s*(?<exp>[-+]?\d{1,3})\s*\)?|[eE](?<exp2>[-+]?\d{1,3})(?![\p{L}\d])))?';

    /** The number at the start of the text (after spaces), or null. */
    public static function leading(string $text): ?array
    {
        if (! preg_match('/^\s*(?<sign>[-+])?'.self::BODY.'/u', $text, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        return self::build($text, $m);
    }

    /** Every number in the text, in order. @return list<array> */
    public static function all(string $text): array
    {
        preg_match_all('/(?:(?<=^|[\s=(\[])(?<sign>[-+]))?(?<![\p{L}\d_.,^])'.self::BODY.'/u', $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        $numbers = [];
        $after = 0;
        foreach ($matches as $m) {
            $number = self::build($text, $m);
            // The power of a 10^-3 already read belongs to that number.
            if ($number['start'] >= $after) {
                $numbers[] = $number;
                $after = $number['end'];
            }
        }

        return $numbers;
    }

    private static function build(string $text, array $m): array
    {
        $int = str_replace(' ', '', $m['int'][0]);
        $frac = isset($m['frac']) && $m['frac'][1] >= 0 && $m['frac'][0] !== '' ? $m['frac'][0] : null;
        $exp = 0;
        $start = isset($m['sign']) && $m['sign'][1] >= 0 && $m['sign'][0] !== '' ? $m['sign'][1] : $m['int'][1];
        $end = $m[0][1] + strlen($m[0][0]);
        if (isset($m['exp']) && $m['exp'][1] >= 0 && $m['exp'][0] !== '') {
            $exp = (int) $m['exp'][0];
        } elseif (isset($m['exp2']) && $m['exp2'][1] >= 0 && $m['exp2'][0] !== '') {
            $exp = (int) $m['exp2'][0];
        } elseif ($int === '10' && $frac === null && preg_match('/\G\s*\^\s*\(?\s*([-+]?\d{1,3})\s*\)?/', $text, $power, 0, $end)) {
            // 10^-3 written on its own: one times ten to that power.
            $int = '1';
            $exp = (int) $power[1];
            $end += strlen($power[0]);
        }
        $negative = isset($m['sign']) && $m['sign'][0] === '-';
        $digits = $int.($frac ?? '');
        $stripped = ltrim($digits, '0');
        if ($stripped === '') {
            [$min, $max] = [1, max(1, strlen($frac ?? ''))];
        } elseif ($frac !== null) {
            $min = $max = strlen($stripped);
        } else {
            [$min, $max] = [max(1, strlen(rtrim($stripped, '0'))), strlen($stripped)];
        }
        $value = (float) ($int.($frac === null ? '' : '.'.$frac).'e'.$exp);

        return ['value' => $negative ? -$value : $value, 'raw' => substr($text, $start, $end - $start),
            'decimals' => strlen($frac ?? '') - $exp, 'sig_min' => $min, 'sig_max' => $max, 'start' => $start, 'end' => $end];
    }

    /** Whether two values agree within an absolute tolerance, allowing for binary rounding. */
    public static function within(float $value, float $expected, float $tolerance): bool
    {
        return abs($value - $expected) <= $tolerance + 1e-9 * max(1.0, abs($expected));
    }

    /** Whether a value is the expected value rounded to the given number of decimal places. */
    public static function roundsTo(float $value, float $expected, int $decimals): bool
    {
        if ($decimals < 0 || $decimals > 12) {
            return false;
        }

        return self::within($value, round($expected, $decimals, PHP_ROUND_HALF_UP), 0.0);
    }
}
