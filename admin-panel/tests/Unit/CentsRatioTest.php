<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Platform\Ledger\CentsRatio;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CentsRatioTest extends TestCase
{
    #[DataProvider('vectors')]
    public function test_exact_half_up_proportions_including_products_beyond_int64(int $cents, int $part, int $whole, int $expected): void
    {
        $this->assertSame($expected, CentsRatio::portion($cents, $part, $whole));
    }

    public static function vectors(): array
    {
        // Golden results computed with independent arbitrary-precision integers.
        return [
            [0, 0, 1, 0],
            [1, 0, 1, 0],
            [1, 1, 1, 1],
            [1, 1, 2, 1],
            [11, 1, 2, 6],
            [1001, 1500, 10000, 150],
            [1001, 1500, 11500, 131],
            [129, 501, 1001, 65],
            [9000000000000000, 8999999999999999, 9000000000000000, 8999999999999999],
            [8999999999999999, 4500000000000001, 9000000000000000, 4500000000000000],
            [8999999999999991, 1234567890123456, 8999999999999987, 1234567890123457],
            [1234567890123456, 8999999999999998, 8999999999999999, 1234567890123456],
        ];
    }

    public function test_invalid_bounds_and_ratios_are_refused(): void
    {
        foreach ([[-1, 1, 1], [1, -1, 1], [1, 2, 1], [1, 0, 0], [9000000000000001, 1, 1], [1, 1, 9000000000000001]] as $input) {
            try {
                CentsRatio::portion(...$input);
                $this->fail('Invalid proportion accepted');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
