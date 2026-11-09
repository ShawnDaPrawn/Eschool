<?php

declare(strict_types=1);

namespace App\Services\Platform\Subscriptions;

use App\Models\Platform\Subscriptions\LearnerSubscription;
use App\Services\Platform\Ledger\Money;
use RuntimeException;

/**
 * The learning plan prices in config/subscriptions.php, in integer cents.
 * They are placeholders until the owner decides them (pricing_confirmed).
 */
final class SubscriptionPricing
{
    public static function currency(): string
    {
        return (string) config('subscriptions.currency', 'ZAR');
    }

    public static function periodMonths(): int
    {
        return max(1, min(12, (int) config('subscriptions.period_months', 1)));
    }

    /** The price of one period for a per-child or family subscription. */
    public static function personal(string $plan): int
    {
        return match ($plan) {
            LearnerSubscription::PER_CHILD => self::cents('per_child_cents'),
            LearnerSubscription::FAMILY => self::cents('additional_child_cents'),
            default => throw new RuntimeException('Only per-child and family plans have a list price.'),
        };
    }

    /** What HQ's licence and sponsorship forms start from. */
    public static function licencePerLearner(): int
    {
        return self::cents('school_licence_per_learner_cents');
    }

    public static function sponsoredPlace(): int
    {
        return self::cents('sponsored_place_cents');
    }

    /** @return list<array{plan: string, price_cents: int, period_months: int}> */
    public static function guardianPlans(): array
    {
        return [
            ['plan' => LearnerSubscription::PER_CHILD, 'price_cents' => self::personal(LearnerSubscription::PER_CHILD), 'period_months' => self::periodMonths()],
            ['plan' => LearnerSubscription::FAMILY, 'price_cents' => self::personal(LearnerSubscription::FAMILY), 'period_months' => self::periodMonths()],
        ];
    }

    public static function rands(int $cents): string
    {
        return Money::ofCents($cents, self::currency())->format('R');
    }

    private static function cents(string $key): int
    {
        $value = config('subscriptions.prices.'.$key);
        if (! is_int($value) || $value < 1 || $value > 100000000) {
            throw new RuntimeException('subscriptions.prices.'.$key.' must be a whole number of cents.');
        }

        return $value;
    }
}
