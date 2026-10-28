<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Money\FeeLifecyclePolicy;
use App\Services\Money\FeePaymentSchedules;
use App\Services\Money\FeeProration;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FeeProrationTest extends TestCase
{
    #[DataProvider('cases')]
    public function test_exact_inclusive_daily_monthly_and_mixed_units(string $yearStart, string $yearEnd, string $starts, ?string $ends, string $join, string $leaver, int $annual, int $expected, string $periodStart, string $periodEnd): void
    {
        $policy = new FeeLifecyclePolicy(['join_basis' => $join, 'leaver_basis' => $leaver, 'revision' => 7]);
        $row = app(FeeProration::class)->charge($annual, $yearStart, $yearEnd, $starts, $ends, $policy);
        $this->assertSame($expected, $row['amount_cents']);
        $this->assertSame($periodStart, $row['period_start']);
        $this->assertSame($periodEnd, $row['period_end']);
    }

    public static function cases(): array
    {
        return [
            ['2026-01-01', '2026-12-31', '2026-06-15', null, 'daily', 'daily', 1001, 548, '2026-06-15', '2026-12-31'],
            ['2024-01-01', '2024-12-31', '2024-02-29', '2024-02-29', 'daily', 'daily', 36600, 100, '2024-02-29', '2024-02-29'],
            ['2026-07-01', '2027-06-30', '2026-12-31', '2027-01-01', 'daily', 'daily', 36500, 200, '2026-12-31', '2027-01-01'],
            ['2026-01-01', '2026-12-31', '2026-06-15', '2026-08-02', 'calendar_month', 'calendar_month', 1001, 250, '2026-06-01', '2026-08-31'],
            ['2026-01-01', '2026-12-31', '2026-06-15', '2026-08-02', 'full_year', 'no_credit', 1001, 1001, '2026-01-01', '2026-12-31'],
            ['2026-01-01', '2026-12-31', '2026-12-31', '2026-12-31', 'daily', 'daily', 100000000000, 273972603, '2026-12-31', '2026-12-31'],
            ['2026-01-15', '2026-12-12', '2026-02-28', '2026-03-01', 'calendar_month', 'calendar_month', 1200, 200, '2026-02-01', '2026-03-31'],
            // 36500 - 3100 (31 daily join days) - 30416.666... (10 uncharged months) = 2983.333...
            ['2026-01-01', '2026-12-31', '2026-02-01', '2026-02-28', 'daily', 'calendar_month', 36500, 2983, '2026-02-01', '2026-02-28'],
        ];
    }

    #[DataProvider('invalidCases')]
    public function test_invalid_calendar_dates_bounds_and_reversed_periods_are_refused(int $cents, string $yearStart, string $yearEnd, string $start, ?string $end): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(FeeProration::class)->charge($cents, $yearStart, $yearEnd, $start, $end, null);
    }

    public static function invalidCases(): array
    {
        return [
            [1001, '2026-01-01', '2026-12-31', '2026-02-31', null],
            [1001, '2026-01-01', '2026-12-31', '2026-06-15', '2026-06-14'],
            [1001, '2026-12-31', '2026-01-01', '2026-06-15', null],
            [1001, '2026-01-01', '2026-12-31', '2027-01-01', null],
            [1001, '2026-01-01', '2026-12-31', '2025-01-01', '2025-12-31'],
            [-1, '2026-01-01', '2026-12-31', '2026-01-01', null],
            [100000000001, '2026-01-01', '2026-12-31', '2026-01-01', null],
        ];
    }

    public function test_a_free_plan_needs_no_invented_policy_or_financial_posting(): void
    {
        $row = app(FeeProration::class)->charge(0, '2026-01-01', '2026-12-31', '2026-06-15', null, null);
        $this->assertSame(0, $row['amount_cents']);
    }

    public function test_remaining_schedule_weights_preserve_cents_without_double_rounding(): void
    {
        $this->assertSame([334, 333, 334], FeePaymentSchedules::weighted(1001, [3333, 3333, 3334]));
        $this->assertSame([501, 500], FeePaymentSchedules::weighted(1001, [3333, 3333]));
        $this->assertSame([1001], FeePaymentSchedules::weighted(1001, [3334]));
    }
}
