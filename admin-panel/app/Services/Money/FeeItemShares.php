<?php

declare(strict_types=1);

namespace App\Services\Money;

use InvalidArgumentException;

final class FeeItemShares
{
    /** Preserve both whole-family holder shares and equally divided learner totals. */
    public static function family(int $amount, array $learners, array $holders): array
    {
        if (array_filter($learners, fn ($id) => ! is_int($id) || $id < 1) !== []) {
            throw new InvalidArgumentException('Choose distinct family learners.');
        }
        sort($learners);
        $count = count($learners);
        if ($count < 1 || $count > 1200 || count(array_unique($learners)) !== $count || min($learners) < 1) {
            throw new InvalidArgumentException('Choose distinct family learners.');
        }
        $totals = BillingPreview::shares($amount, $holders);
        $rows = array_fill_keys($learners, []);
        $cursor = 0;
        foreach ($totals as $holder) {
            $base = intdiv($holder['amount_cents'], $count);
            $remaining = $holder['amount_cents'] % $count;
            $extra = [];
            for ($i = 0; $i < $remaining; $i++) {
                $extra[$learners[($cursor + $i) % $count]] = true;
            }
            $cursor = ($cursor + $remaining) % $count;
            foreach ($learners as $learner) {
                $rows[$learner][] = [...$holder, 'amount_cents' => $base + (isset($extra[$learner]) ? 1 : 0)];
            }
        }

        return $rows;
    }
}
