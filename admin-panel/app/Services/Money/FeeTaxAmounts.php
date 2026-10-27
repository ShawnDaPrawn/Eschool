<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Services\Platform\Ledger\CentsRatio;
use InvalidArgumentException;

/** Explicit tax classification/rate; old no-VAT amounts are never reinterpreted. */
final class FeeTaxAmounts
{
    public const TREATMENTS = ['no_vat', 'vat_exempt', 'vat_zero', 'vat_inclusive', 'vat_exclusive'];

    public static function labels(): array
    {
        return ['no_vat' => 'No VAT charged', 'vat_exempt' => 'VAT exempt', 'vat_zero' => 'VAT zero-rated', 'vat_inclusive' => 'Price includes VAT', 'vat_exclusive' => 'Add VAT to the price'];
    }

    public static function price(int $cents, string $treatment, int $rateBp): array
    {
        if ($cents < 1 || $cents > FeePlans::MAX_CENTS || ! in_array($treatment, self::TREATMENTS, true) || $rateBp < 0 || $rateBp > 10000) {
            throw new InvalidArgumentException('Choose a supported positive price and explicit VAT treatment/rate.');
        }
        $taxable = in_array($treatment, ['vat_inclusive', 'vat_exclusive'], true);
        if (($taxable && $rateBp === 0) || (! $taxable && $rateBp !== 0)) {
            throw new InvalidArgumentException('A taxable item needs a positive rate; no-VAT, exempt and zero-rated items use zero.');
        }
        $vat = $taxable ? CentsRatio::portion($cents, $rateBp, $treatment === 'vat_inclusive' ? 10000 + $rateBp : 10000) : 0;
        $gross = $treatment === 'vat_exclusive' ? $cents + $vat : $cents;
        if ($gross > FeePlans::MAX_CENTS) {
            throw new InvalidArgumentException('The gross charge exceeds the supported cents limit.');
        }

        return ['price_cents' => $cents, 'net_cents' => $gross - $vat, 'vat_cents' => $vat, 'gross_cents' => $gross, 'vat_rate_bp' => $rateBp, 'tax_treatment' => $treatment];
    }

    /** Largest remainders over actual gross shares, preserving the exact original VAT. */
    public static function split(int $vat, array $grossShares): array
    {
        $gross = 0;
        foreach ($grossShares as $cents) {
            if (! is_int($cents) || $cents < 0 || $cents > FeePlans::MAX_CENTS - $gross) {
                throw new InvalidArgumentException('Use bounded non-negative integer gross shares.');
            }
            $gross += $cents;
        }
        if ($vat < 0 || $gross < 1 || $gross > FeePlans::MAX_CENTS || $vat > $gross) {
            throw new InvalidArgumentException('VAT needs bounded positive gross shares.');
        }
        $out = [];
        $remainders = [];
        foreach ($grossShares as $key => $cents) {
            [$out[$key], $remainders[$key]] = CentsRatio::quotientAndRemainder($vat, $cents, $gross);
        }
        $keys = array_keys($out);
        usort($keys, fn ($a, $b) => ($remainders[$b] <=> $remainders[$a]) ?: ($a <=> $b));
        $left = $vat - array_sum($out);
        for ($n = 0; $n < $left; $n++) {
            $out[$keys[$n]]++;
        }

        return $out;
    }

    public static function rate(string $percent): int
    {
        if (preg_match('/\A(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)\z/D', $percent) !== 1) {
            throw new InvalidArgumentException('Use a rate from zero to 100 with at most two decimal places.');
        }
        $parts = explode('.', $percent);

        return (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
    }
}
