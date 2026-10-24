<?php

declare(strict_types=1);

namespace App\Services\Academics;

use Illuminate\Validation\ValidationException;

/** Exact hundredth units for marks/percentages; never interpret a binary float. */
final class AcademicNumbers
{
    public static function units(mixed $value, string $field = 'mark', int $maximum = 100000000): int
    {
        if ((! is_string($value) && ! is_int($value)) || ! preg_match('/^\d{1,7}(?:\.\d{1,2})?$/D', (string) $value)) {
            throw ValidationException::withMessages([$field => __('Use a non-negative decimal with at most two decimal places.')]);
        }
        [$whole, $fraction] = array_pad(explode('.', (string) $value, 2), 2, '');
        $units = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
        if ($units > $maximum) {
            throw ValidationException::withMessages([$field => __('The amount exceeds the supported mark range.')]);
        }

        return $units;
    }

    public static function decimal(int $units): string
    {
        return intdiv($units, 100).'.'.str_pad((string) ($units % 100), 2, '0', STR_PAD_LEFT);
    }
}
