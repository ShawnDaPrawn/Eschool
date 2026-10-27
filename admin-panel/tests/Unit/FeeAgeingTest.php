<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Money\FeeAgeing;
use PHPUnit\Framework\TestCase;

class FeeAgeingTest extends TestCase
{
    public function test_exact_calendar_boundaries_do_not_mix_due_today_with_overdue_money(): void
    {
        $today = new \DateTimeImmutable('2026-06-15');
        foreach ([-1 => 'current', 0 => 'current', 1 => 'days_1_29', 29 => 'days_1_29', 30 => 'days_30_59', 59 => 'days_30_59', 60 => 'days_60_89', 89 => 'days_60_89', 90 => 'days_90_plus', 365 => 'days_90_plus'] as $days => $expected) {
            $due = $today->modify(($days > 0 ? '-' : '+').abs($days).' days')->format('Y-m-d');
            $this->assertSame(max(0, $days), FeeAgeing::days($due, '2026-06-15'));
            $this->assertSame($expected, FeeAgeing::bucket(FeeAgeing::days($due, '2026-06-15')));
        }
        $this->assertSame(1, FeeAgeing::days('2024-02-29', '2024-03-01'));
        $this->assertSame(2, FeeAgeing::days('2026-03-28', '2026-03-30'));
    }
}
