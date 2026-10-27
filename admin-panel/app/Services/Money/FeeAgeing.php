<?php

declare(strict_types=1);

namespace App\Services\Money;

use DateTimeImmutable;

final class FeeAgeing
{
    public const BUCKETS = ['current', 'days_1_29', 'days_30_59', 'days_60_89', 'days_90_plus'];

    public static function days(string $dueOn, string $today): int
    {
        return $dueOn >= $today ? 0 : (int) (new DateTimeImmutable($dueOn))->diff(new DateTimeImmutable($today))->days;
    }

    public static function bucket(int $days): string
    {
        return match (true) {
            $days <= 0 => 'current', $days < 30 => 'days_1_29', $days < 60 => 'days_30_59', $days < 90 => 'days_60_89', default => 'days_90_plus',
        };
    }

    public static function empty(): array
    {
        return array_fill_keys(self::BUCKETS, 0);
    }
}
