<?php

declare(strict_types=1);

namespace App\Services\Platform\Ledger;

use InvalidArgumentException;

/** Exact bounded proportion with half-up rounding, without an overflowing product. */
final class CentsRatio
{
    public static function portion(int $cents, int $part, int $whole): int
    {
        [$quotient,$remainder] = self::quotientAndRemainder($cents, $part, $whole);

        return $quotient + ($remainder >= intdiv($whole, 2) + $whole % 2 ? 1 : 0);
    }

    /** @return array{int,int} Exact floor and numerator remainder. */
    public static function quotientAndRemainder(int $cents, int $part, int $whole): array
    {
        if ($cents < 0 || $cents > LedgerService::MAX_CENTS || $whole < 1 || $whole > LedgerService::MAX_CENTS || $part < 0 || $part > $whole) {
            throw new InvalidArgumentException('Use bounded non-negative cents and a proportion between zero and one.');
        }
        $quotient = 0;
        $remainder = 0;
        $unitQuotient = intdiv($cents, $whole);
        $unitRemainder = $cents % $whole;
        $bits = $part;
        while ($bits > 0) {
            if ($bits % 2 === 1) {
                $quotient += $unitQuotient;
                $remainder += $unitRemainder;
                if ($remainder >= $whole) {
                    $remainder -= $whole;
                    $quotient++;
                }
            }
            $bits = intdiv($bits, 2);
            if ($bits > 0) {
                $unitQuotient *= 2;
                $unitRemainder *= 2;
                if ($unitRemainder >= $whole) {
                    $unitRemainder -= $whole;
                    $unitQuotient++;
                }
            }
        }

        return [$quotient, $remainder];
    }
}
