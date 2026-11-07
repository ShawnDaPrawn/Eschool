<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking;

use Illuminate\Support\Str;

/**
 * SI units for marking (Action Plan 3.16): base and common derived units with
 * prefixes (p to G), powers and quotients in the ways learners write them
 * (m/s, m·s⁻¹, m.s-1, m s^-1, ms-1, kg/m^3, J/(kg·K), km/h), and a few unit
 * words (metres, newtons). Two units agree when their dimensions match; the
 * learner's value is then converted into the memo's unit.
 *
 * Degrees Celsius is kept apart from kelvin (no offset conversion), and an
 * unknown unit is never guessed: it is reported as unreadable.
 */
final class Units
{
    /** Dimensions: m, kg, s, A, K, mol, cd, °C. */
    private const UNITS = [
        'm' => [1.0, [1, 0, 0, 0, 0, 0, 0, 0], true],
        'g' => [1e-3, [0, 1, 0, 0, 0, 0, 0, 0], true],
        's' => [1.0, [0, 0, 1, 0, 0, 0, 0, 0], true],
        'A' => [1.0, [0, 0, 0, 1, 0, 0, 0, 0], true],
        'K' => [1.0, [0, 0, 0, 0, 1, 0, 0, 0], false],
        'mol' => [1.0, [0, 0, 0, 0, 0, 1, 0, 0], true],
        'cd' => [1.0, [0, 0, 0, 0, 0, 0, 1, 0], false],
        '°C' => [1.0, [0, 0, 0, 0, 0, 0, 0, 1], false],
        'min' => [60.0, [0, 0, 1, 0, 0, 0, 0, 0], false],
        'h' => [3600.0, [0, 0, 1, 0, 0, 0, 0, 0], false],
        'N' => [1.0, [1, 1, -2, 0, 0, 0, 0, 0], true],
        'J' => [1.0, [2, 1, -2, 0, 0, 0, 0, 0], true],
        'eV' => [1.602176634e-19, [2, 1, -2, 0, 0, 0, 0, 0], true],
        'W' => [1.0, [2, 1, -3, 0, 0, 0, 0, 0], true],
        'Pa' => [1.0, [-1, 1, -2, 0, 0, 0, 0, 0], true],
        'Hz' => [1.0, [0, 0, -1, 0, 0, 0, 0, 0], true],
        'C' => [1.0, [0, 0, 1, 1, 0, 0, 0, 0], true],
        'V' => [1.0, [2, 1, -3, -1, 0, 0, 0, 0], true],
        'Ω' => [1.0, [2, 1, -3, -2, 0, 0, 0, 0], true],
        'F' => [1.0, [-2, -1, 4, 2, 0, 0, 0, 0], true],
        'T' => [1.0, [0, 1, -2, -1, 0, 0, 0, 0], true],
        'L' => [1e-3, [3, 0, 0, 0, 0, 0, 0, 0], true],
        '%' => [0.01, [0, 0, 0, 0, 0, 0, 0, 0], false],
        '°' => [M_PI / 180, [0, 0, 0, 0, 0, 0, 0, 0], false],
        'rad' => [1.0, [0, 0, 0, 0, 0, 0, 0, 0], false],
    ];

    private const ALIASES = ['l' => 'L', 'ℓ' => 'L', 'ohm' => 'Ω', 'ohms' => 'Ω', 'Ω' => 'Ω', '℃' => '°C', 'degC' => '°C', 'μ' => 'µ'];

    /** Unit words, English and Afrikaans, for the symbol they stand for. */
    private const WORDS = [
        'metre' => 'm', 'metres' => 'm', 'meter' => 'm', 'meters' => 'm', 'kilometre' => 'km', 'kilometres' => 'km', 'kilometer' => 'km', 'kilometers' => 'km',
        'centimetre' => 'cm', 'centimetres' => 'cm', 'millimetre' => 'mm', 'millimetres' => 'mm',
        'second' => 's', 'seconds' => 's', 'sekonde' => 's', 'sekondes' => 's', 'minute' => 'min', 'minutes' => 'min', 'minuut' => 'min', 'minute(s)' => 'min',
        'hour' => 'h', 'hours' => 'h', 'uur' => 'h', 'gram' => 'g', 'grams' => 'g', 'kilogram' => 'kg', 'kilograms' => 'kg',
        'newton' => 'N', 'newtons' => 'N', 'joule' => 'J', 'joules' => 'J', 'watt' => 'W', 'watts' => 'W', 'volt' => 'V', 'volts' => 'V',
        'ampere' => 'A', 'amperes' => 'A', 'amp' => 'A', 'amps' => 'A', 'hertz' => 'Hz', 'pascal' => 'Pa', 'pascals' => 'Pa',
        'coulomb' => 'C', 'coulombs' => 'C', 'litre' => 'L', 'litres' => 'L', 'liter' => 'L', 'liters' => 'L', 'kelvin' => 'K',
        'degrees' => '°', 'degree' => '°', 'grade' => '°', 'percent' => '%',
    ];

    private const PREFIXES = ['p' => 1e-12, 'n' => 1e-9, 'µ' => 1e-6, 'u' => 1e-6, 'm' => 1e-3, 'c' => 1e-2, 'd' => 1e-1, 'k' => 1e3, 'M' => 1e6, 'G' => 1e9];

    private const MAX_READINGS = 16;

    /**
     * The possible readings of a unit as [factor to SI, dimensions]: more than one
     * when the writing is ambiguous (ms-1 is m·s⁻¹ or per millisecond). Empty when
     * the unit cannot be read.
     *
     * @return list<array{0: float, 1: list<int>}>
     */
    public static function readings(string $unit): array
    {
        $unit = trim(AnswerText::normalize($unit));
        if ($unit === '' || mb_strlen($unit) > 40) {
            return [];
        }
        $lower = Str::lower($unit);
        if (isset(self::WORDS[$lower])) {
            $unit = self::WORDS[$lower];
        }
        // Words like "metres per second".
        $unit = (string) preg_replace_callback('/\p{L}{3,}/u', fn ($m) => self::WORDS[Str::lower($m[0])] ?? $m[0], $unit);
        $unit = (string) preg_replace('/\s+per\s+/i', '/', $unit);
        $unit = str_replace(['·', '*', '×', '⋅'], ' ', $unit);
        $unit = (string) preg_replace('/(?<=[\p{L})])\.(?=[\p{L}(])/u', ' ', $unit);
        $unit = (string) preg_replace('/(?<=\d)\.(?=[\p{L}(])/u', ' ', $unit);
        $readings = [[1.0, array_fill(0, 8, 0)]];
        foreach (explode('/', $unit) as $i => $part) {
            $part = trim(str_replace(['(', ')'], ' ', $part));
            if ($part === '') {
                return [];
            }
            foreach (preg_split('/\s+/', $part) as $factor) {
                $options = self::factor($factor);
                if ($options === []) {
                    return [];
                }
                $next = [];
                foreach ($readings as [$scale, $dims]) {
                    foreach ($options as [$f, $d]) {
                        $sign = $i === 0 ? 1 : -1;
                        $next[] = [$sign > 0 ? $scale * $f : $scale / $f, array_map(fn ($a, $b) => $a + $sign * $b, $dims, $d)];
                    }
                }
                $readings = array_slice($next, 0, self::MAX_READINGS);
            }
        }

        return $readings;
    }

    /** @return list<array{0: float, 1: list<int>}> */
    private static function factor(string $factor): array
    {
        if (! preg_match('/^(?<symbol>[\p{L}°%℃]+)(?:\^?\(?(?<exp>[-+]?\d)\)?)?$/u', $factor, $m)) {
            return [];
        }
        $exp = isset($m['exp']) && $m['exp'] !== '' ? (int) $m['exp'] : 1;
        if ($exp === 0) {
            return [];
        }
        $direct = array_map(fn ($o) => [$o[0] ** $exp, array_map(fn ($d) => $d * $exp, $o[1])], self::symbols($m['symbol']));
        if ($direct !== [] && $exp === 1) {
            return $direct;
        }
        // A run like "kgm" is two units written together, and "ms-1" is
        // usually m·s⁻¹ rather than per millisecond: the power belongs to the last.
        $pairs = self::pairs($m['symbol'], $exp);
        $options = $m['symbol'] === 'ms' ? array_merge($pairs, $direct) : array_merge($direct, $pairs);

        return array_slice($options, 0, self::MAX_READINGS);
    }

    /** @return list<array{0: float, 1: list<int>}> */
    private static function symbols(string $symbol): array
    {
        $symbol = self::ALIASES[$symbol] ?? self::ALIASES[Str::lower($symbol)] ?? $symbol;
        $options = [];
        if (isset(self::UNITS[$symbol])) {
            $options[] = [self::UNITS[$symbol][0], self::UNITS[$symbol][1]];
        }
        $first = mb_substr($symbol, 0, 1);
        $rest = mb_substr($symbol, 1);
        $first = self::ALIASES[$first] ?? $first;
        $rest = self::ALIASES[$rest] ?? $rest;
        if ($rest !== '' && isset(self::PREFIXES[$first], self::UNITS[$rest]) && self::UNITS[$rest][2]) {
            $options[] = [self::PREFIXES[$first] * self::UNITS[$rest][0], self::UNITS[$rest][1]];
        }

        return $options;
    }

    /** @return list<array{0: float, 1: list<int>}> */
    private static function pairs(string $symbol, int $exp): array
    {
        $options = [];
        $length = mb_strlen($symbol);
        for ($cut = 1; $cut < $length; $cut++) {
            foreach (self::symbols(mb_substr($symbol, 0, $cut)) as [$f1, $d1]) {
                foreach (self::symbols(mb_substr($symbol, $cut)) as [$f2, $d2]) {
                    $options[] = [$f1 * $f2 ** $exp, array_map(fn ($a, $b) => $a + $b * $exp, $d1, $d2)];
                }
            }
        }

        return $options;
    }

    /** Whether the text reads as a unit. */
    public static function readable(string $unit): bool
    {
        return self::readings($unit) !== [];
    }

    /**
     * Compare a learner's unit with the memo's; ratio converts the learner's value into the memo's unit.
     *
     * @return array{status: 'same'|'converted'|'missing'|'wrong'|'unreadable'|'unknown', ratio: float}
     */
    public static function compare(string $memo, ?string $learner): array
    {
        if ($learner === null || trim($learner) === '') {
            return ['status' => 'missing', 'ratio' => 1.0];
        }
        // The memo is read one way only (its first reading); a learner's writing may be read any way it allows.
        $expected = array_slice(self::readings($memo), 0, 1);
        $given = self::readings($learner);
        if ($expected === []) {
            return ['status' => self::plain($memo) === self::plain($learner) ? 'same' : 'unknown', 'ratio' => 1.0];
        }
        if ($given === []) {
            return ['status' => 'unreadable', 'ratio' => 1.0];
        }
        $best = null;
        foreach ($expected as [$ef, $ed]) {
            foreach ($given as [$gf, $gd]) {
                if ($ed !== $gd) {
                    continue;
                }
                $ratio = $gf / $ef;
                if (abs($ratio - 1.0) < 1e-12) {
                    return ['status' => 'same', 'ratio' => 1.0];
                }
                $best ??= ['status' => 'converted', 'ratio' => $ratio];
            }
        }

        return $best ?? ['status' => 'wrong', 'ratio' => 1.0];
    }

    private static function plain(string $unit): string
    {
        return (string) preg_replace('/[\s·.*^]+/u', '', AnswerText::normalize($unit));
    }
}
