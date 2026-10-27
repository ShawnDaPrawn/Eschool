<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Money\FeeTaxAmounts;
use App\Services\Platform\Ledger\CentsRatio;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FeeTaxAmountsTest extends TestCase
{
    public function test_explicit_inclusive_exclusive_zero_and_no_vat_prices_are_exact(): void
    {
        $inclusive = FeeTaxAmounts::price(11500, 'vat_inclusive', 1500);
        $exclusive = FeeTaxAmounts::price(10000, 'vat_exclusive', 1500);
        foreach ([$inclusive, $exclusive] as $price) {
            $this->assertSame(11500, $price['gross_cents']);
            $this->assertSame(10000, $price['net_cents']);
            $this->assertSame(1500, $price['vat_cents']);
        }
        foreach (['no_vat', 'vat_zero', 'vat_exempt'] as $treatment) {
            $price = FeeTaxAmounts::price(1001, $treatment, 0);
            $this->assertSame(1001, $price['gross_cents']);
            $this->assertSame(0, $price['vat_cents']);
        }
        $this->assertSame(131, FeeTaxAmounts::price(1001, 'vat_inclusive', 1500)['vat_cents']);
        $this->assertSame(1, FeeTaxAmounts::price(1, 'vat_inclusive', 10000)['vat_cents']);
        $this->assertSame(0, FeeTaxAmounts::price(1, 'vat_inclusive', 10000)['net_cents']);
    }

    public function test_holder_and_learner_tax_splits_preserve_every_cent_including_zero_shares(): void
    {
        $this->assertSame([1 => 8, 2 => 7, 3 => 0], FeeTaxAmounts::split(15, [1 => 58, 2 => 57, 3 => 0]));
        $this->assertSame([10 => 1, 20 => 0], FeeTaxAmounts::split(1, [10 => 1, 20 => 1]));
        $tax = 13043478261;
        $shares = [1 => 33333333333, 2 => 33333333333, 3 => 33333333334];
        $split = FeeTaxAmounts::split($tax, $shares);
        $this->assertSame($tax, array_sum($split));
        foreach ($split as $key => $cents) {
            $this->assertGreaterThanOrEqual(0, $cents);
            $this->assertLessThanOrEqual($shares[$key], $cents);
        }
        $this->assertSame([8999999999999999, 0], CentsRatio::quotientAndRemainder(9000000000000000, 8999999999999999, 9000000000000000));
    }

    public function test_invalid_rates_treatments_and_gross_bounds_refuse(): void
    {
        foreach ([[100, 'no_vat', 1500], [100, 'vat_exempt', 1], [100, 'vat_zero', 1], [100, 'vat_inclusive', 0], [100, 'unknown', 0], [100000000000, 'vat_exclusive', 10000]] as $input) {
            try {
                FeeTaxAmounts::price(...$input);
                $this->fail('Invalid VAT price accepted');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
        foreach (['15.001', '-1', '100.01', '1e1'] as $input) {
            try {
                FeeTaxAmounts::rate($input);
                $this->fail('Invalid rate accepted');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame(1500, FeeTaxAmounts::rate('15'));
        $this->assertSame(1525, FeeTaxAmounts::rate('15.25'));
    }
}
