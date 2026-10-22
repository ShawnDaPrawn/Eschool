<?php

declare(strict_types=1);

namespace App\Services\Platform\Ledger;

/**
 * The boundary between the seed's float money columns (and the v1 API that
 * returns them) and integer cents. Phase 0 keeps both columns; the floats go
 * with the ledger work in a later phase. Nothing new should call this: new code
 * uses Money.
 */
final class LegacyAmount
{
    /**
     * Cents for a value the legacy code holds: an int, a numeric string from
     * a request, a float read or computed by old code, or Money. The float is
     * first written with six decimals, then rounded half up to the cent,
     * exactly as the back-fill rounds the stored floats.
     */
    public static function toCents(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof Money) {
            return $value->cents;
        }
        if (is_int($value)) {
            return $value * 100;
        }
        if (is_string($value) && preg_match('/^\s*[+-]?(\d+(\.\d*)?|\.\d+)\s*$/', $value)) {
            return Money::fromDecimalString($value)->cents;
        }
        if (is_float($value) || is_numeric($value)) {
            return Money::fromDecimalString(sprintf('%.6F', (float) $value))->cents;
        }
        if (is_bool($value)) {
            return $value ? 100 : 0;
        }

        throw new \InvalidArgumentException('Not an amount: '.get_debug_type($value));
    }

    /**
     * The value the v1 API and the old views show for an amount stored in
     * cents: a float in currency units, as the float column gave them.
     */
    public static function fromCents(int|string|null $cents): ?float
    {
        if ($cents === null || $cents === '') {
            return null;
        }

        return (int) $cents / 100;
    }

    /** As fromCents(), with null as 0.0. */
    public static function fromCentsOrZero(int|string|null $cents): float
    {
        return self::fromCents($cents) ?? 0.0;
    }
}
