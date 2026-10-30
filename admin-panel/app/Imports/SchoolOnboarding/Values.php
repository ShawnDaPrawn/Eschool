<?php

declare(strict_types=1);

namespace App\Imports\SchoolOnboarding;

use App\Models\Academics\GradeLevel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** Reading the values people type into school spreadsheets. */
final class Values
{
    /**
     * "8", "Gr 8", "Grade 8", "Graad 8", "08", "R", "Grade R".
     *
     * @param  Collection<int, GradeLevel>  $grades
     */
    public static function grade(string $value, Collection $grades): ?GradeLevel
    {
        $code = mb_strtoupper(trim((string) preg_replace('/^(grade|graad|gr\.?|ibanga)\s*/i', '', trim($value))));
        $code = ltrim($code, '0') === '' && $code !== '' ? '0' : ltrim($code, '0');
        if ($code === '0') {
            $code = 'R';
        }

        return $grades->first(fn (GradeLevel $grade) => (string) $grade->code === $code);
    }

    /**
     * A date as Y-m-d: 2014-03-09, 2014/03/09, 09/03/2014, 09-03-2014 or
     * 9.3.2014 (day first, as South African schools write it), or a
     * spreadsheet's day number. Null when it is not a real date.
     */
    public static function date(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{5}$/', $value)) {
            // Days since 1899-12-30, as spreadsheets store dates.
            return CarbonImmutable::create(1899, 12, 30)->addDays((int) $value)->toDateString();
        }
        if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $value, $m)) {
            [$year, $month, $day] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/', $value, $m)) {
            [$day, $month, $year] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return null;
        }
        if (! checkdate($month, $day, $year) || $year < 1900) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /** male, female or null when not given or not recognised. */
    public static function gender(string $value): ?string
    {
        return match (mb_strtolower(trim($value))) {
            'm', 'male', 'boy', 'man', 'manlik', 'seun', 'owesilisa' => 'male',
            'f', 'female', 'girl', 'woman', 'vroulik', 'dogter', 'owesifazane' => 'female',
            default => null,
        };
    }

    /** Digits with an optional leading +, 7 to 16 digits; null when not a phone number. */
    public static function mobile(string $value): ?string
    {
        $digits = preg_replace('/[\s\-().]/', '', trim($value)) ?? '';

        return preg_match('/^\+?[0-9]{7,16}$/', $digits) ? $digits : null;
    }

    public static function email(string $value): ?string
    {
        $value = mb_strtolower(trim($value));

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($value) <= 191 ? $value : null;
    }

    /** A person's name: letters, spaces, apostrophes, hyphens and dots, at most 191 characters. */
    public static function name(string $value): ?string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return $value !== '' && mb_strlen($value) <= 191 && preg_match("/^[\\p{L}\\p{M}][\\p{L}\\p{M}\\s'’.\\-]*$/u", $value) ? $value : null;
    }
}
