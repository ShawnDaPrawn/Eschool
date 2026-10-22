<?php

declare(strict_types=1);

namespace App\Services\Platform\Ledger;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Casts an integer cents column to Money: `'amount_cents' => MoneyCast::class`.
 *
 * Reads give Money (or null); writes take Money, an integer number of cents
 * or null, never a float. The currency is the model's moneyCurrency() when
 * it has one, else the platform default.
 *
 * @implements CastsAttributes<Money|null, Money|int|null>
 */
class MoneyCast implements CastsAttributes
{
    /** Money is immutable, so there is nothing to write back from a cached object. */
    public bool $withoutObjectCaching = true;

    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Money::ofCents((int) $value, self::currencyOf($model));
    }

    /**
     * @return array<string, int|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        return [$key => match (true) {
            $value === null => null,
            $value instanceof Money => $value->cents,
            is_int($value) => $value,
            is_string($value) && preg_match('/^-?\d+$/', $value) === 1 => (int) $value,
            default => throw new InvalidArgumentException("{$key} takes Money or whole cents, not ".get_debug_type($value)),
        }];
    }

    private static function currencyOf(Model $model): ?string
    {
        return method_exists($model, 'moneyCurrency') ? $model->moneyCurrency() : null;
    }
}
