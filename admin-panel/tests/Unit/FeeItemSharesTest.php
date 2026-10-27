<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Money\BillingPreview;
use App\Services\Money\FeeItemShares;
use PHPUnit\Framework\TestCase;

class FeeItemSharesTest extends TestCase
{
    public function test_family_allocation_preserves_total_holder_percentages_and_balanced_learner_totals_without_floats(): void
    {
        foreach ([1, 2, 3, 1001, 1002, 100000000000] as $amount) {
            foreach ([1, 2, 3, 17] as $count) {
                $ids = range(1, $count);
                $holders = [['user_id' => 7, 'name' => 'One', 'share_bp' => 3333], ['user_id' => 8, 'name' => 'Two', 'share_bp' => 3333], ['user_id' => 9, 'name' => 'Three', 'share_bp' => 3334]];
                $rows = FeeItemShares::family($amount, array_reverse($ids), $holders);
                $learnerTotals = [];
                $holderTotals = [];
                foreach ($rows as $row) {
                    $learnerTotals[] = array_sum(array_column($row, 'amount_cents'));
                    foreach ($row as $h) {
                        $this->assertIsInt($h['amount_cents']);
                        $this->assertGreaterThanOrEqual(0, $h['amount_cents']);
                        $holderTotals[$h['user_id']] = ($holderTotals[$h['user_id']] ?? 0) + $h['amount_cents'];
                    }
                }
                $this->assertSame($amount, array_sum($learnerTotals));
                $this->assertLessThanOrEqual(1, max($learnerTotals) - min($learnerTotals));
                foreach (BillingPreview::shares($amount, $holders) as $h) {
                    $this->assertSame($h['amount_cents'], $holderTotals[$h['user_id']]);
                }
            }
        }
    }

    public function test_family_rounding_does_not_repeat_the_same_odd_cent_for_each_split_learner(): void
    {
        $rows = FeeItemShares::family(1002, [12, 11], [['user_id' => 21, 'name' => 'One', 'share_bp' => 5000], ['user_id' => 22, 'name' => 'Two', 'share_bp' => 5000]]);
        $this->assertSame([251, 250], array_column($rows[11], 'amount_cents'));
        $this->assertSame([250, 251], array_column($rows[12], 'amount_cents'));
    }

    public function test_invalid_learner_identities_are_refused(): void
    {
        foreach ([[], [1, 1], [0], [true], ['1']] as $ids) {
            try {
                FeeItemShares::family(1, $ids, [['user_id' => 1, 'name' => 'Synthetic', 'share_bp' => 10000]]);
                $this->fail('Expected invalid learner refusal');
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
