<?php

declare(strict_types=1);

namespace App\Services\Platform\Ledger;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * An amount of money: integer cents plus an ISO 4217 currency (Build Bible
 * principle 5, "money is ledger-grade"). Immutable; every operation returns a
 * new Money. No method takes or returns a float: amounts come in as cents or
 * decimal strings and rounding is explicit, half up (away from zero).
 *
 * Amounts in different currencies never mix: plus(), minus() and the
 * comparisons throw when the currencies differ.
 */
final class Money implements JsonSerializable, Stringable
{
    private function __construct(public readonly int $cents, public readonly string $currency) {}

    public static function ofCents(int $cents, ?string $currency = null): self
    {
        return new self($cents, self::currencyCode($currency));
    }

    public static function zero(?string $currency = null): self
    {
        return new self(0, self::currencyCode($currency));
    }

    /**
     * From a decimal string such as "1500", "1234.5" or "-0.125". More than two
     * decimals are rounded half up: "0.125" is 13 cents, "-0.125" is -13.
     *
     * @throws InvalidArgumentException for anything but a plain decimal number
     */
    public static function fromDecimalString(string $amount, ?string $currency = null): self
    {
        $amount = trim($amount);
        if (! preg_match('/^([+-]?)(\d*)(?:\.(\d*))?$/', $amount, $match) || ($match[2] === '' && ($match[3] ?? '') === '')) {
            throw new InvalidArgumentException("Not a decimal amount: \"{$amount}\"");
        }
        $negative = $match[1] === '-';
        $whole = ltrim($match[2], '0');
        $fraction = $match[3] ?? '';

        if (strlen($whole) > 16) {
            throw new InvalidArgumentException("Amount too large: \"{$amount}\"");
        }
        $cents = (int) ($whole === '' ? '0' : $whole) * 100 + (int) str_pad(substr($fraction, 0, 2), 2, '0');
        if (strlen($fraction) > 2 && (int) $fraction[2] >= 5) {
            $cents++;
        }

        return new self($negative ? -$cents : $cents, self::currencyCode($currency));
    }

    /**
     * numerator / denominator cents, rounded half up: ofFraction(100000, 3)
     * is R333.33. For shares worked out exactly before the one rounding.
     */
    public static function ofFraction(int $numerator, int $denominator, ?string $currency = null): self
    {
        if ($denominator === 0) {
            throw new InvalidArgumentException('Cannot divide money by zero');
        }

        return new self(self::divideHalfUp($numerator, $denominator), self::currencyCode($currency));
    }

    /** The currency of amounts stored without one (config/platform.php). */
    public static function defaultCurrency(): string
    {
        return strtoupper((string) config('platform.money.currency', 'ZAR'));
    }

    public function plus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->cents + $other->cents, $this->currency);
    }

    public function minus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->cents - $other->cents, $this->currency);
    }

    public function times(int $factor): self
    {
        return new self($this->cents * $factor, $this->currency);
    }

    /**
     * A percentage of this amount, rounded half up: 15 % of R10.01 is R1.50.
     *
     * @param  int|string  $percent  a whole number or a decimal string such as "12.5"
     */
    public function percent(int|string $percent): self
    {
        [$numerator, $denominator] = self::fraction((string) $percent);

        return new self(self::divideHalfUp($this->cents * $numerator, $denominator * 100), $this->currency);
    }

    /**
     * This amount divided by a whole number, rounded half up. Use allocate()
     * when the parts must add back up to the whole.
     */
    public function dividedBy(int $divisor): self
    {
        if ($divisor === 0) {
            throw new InvalidArgumentException('Cannot divide money by zero');
        }

        return new self(self::divideHalfUp($this->cents, $divisor), $this->currency);
    }

    /**
     * Split into $parts amounts that add up exactly to this one; the first
     * parts carry the extra cents.
     *
     * @return list<self>
     */
    public function allocate(int $parts): array
    {
        if ($parts < 1) {
            throw new InvalidArgumentException('Allocate into at least one part');
        }
        $base = intdiv($this->cents, $parts);
        $remainder = $this->cents - $base * $parts;
        $step = $remainder < 0 ? -1 : 1;
        $result = [];
        for ($i = 0; $i < $parts; $i++) {
            $extra = $i < abs($remainder) ? $step : 0;
            $result[] = new self($base + $extra, $this->currency);
        }

        return $result;
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function isPositive(): bool
    {
        return $this->cents > 0;
    }

    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    /** -1, 0 or 1. */
    public function compareTo(self $other): int
    {
        $this->assertSameCurrency($other);

        return $this->cents <=> $other->cents;
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency && $this->cents === $other->cents;
    }

    public function greaterThan(self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function lessThan(self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    /** "1234.56", "-0.05": always two decimals, no grouping. */
    public function toDecimalString(): string
    {
        $abs = abs($this->cents);

        return ($this->cents < 0 ? '-' : '').intdiv($abs, 100).'.'.str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * For people: "R 1,234.56" with a symbol, "ZAR 1,234.56" without.
     */
    public function format(?string $symbol = null, string $thousands = ',', string $decimal = '.'): string
    {
        $abs = abs($this->cents);
        $number = number_format(intdiv($abs, 100), 0, '', $thousands).$decimal.str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);

        return ($this->cents < 0 ? '-' : '').($symbol ?? $this->currency).' '.$number;
    }

    /** @return array{cents: int, currency: string} */
    public function jsonSerialize(): array
    {
        return ['cents' => $this->cents, 'currency' => $this->currency];
    }

    public function __toString(): string
    {
        return $this->toDecimalString();
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException("Cannot combine {$this->currency} and {$other->currency}");
        }
    }

    private static function currencyCode(?string $currency): string
    {
        $code = strtoupper(trim((string) ($currency ?? self::defaultCurrency())));
        if (! preg_match('/^[A-Z]{3}$/', $code)) {
            throw new InvalidArgumentException("Not an ISO 4217 currency code: \"{$code}\"");
        }

        return $code;
    }

    /**
     * "12.5" => [125, 10].
     *
     * @return array{0: int, 1: int}
     */
    private static function fraction(string $value): array
    {
        if (! preg_match('/^([+-]?\d+)(?:\.(\d{1,6}))?$/', trim($value), $match)) {
            throw new InvalidArgumentException("Not a decimal number: \"{$value}\"");
        }
        $decimals = $match[2] ?? '';
        $denominator = 10 ** strlen($decimals);
        $whole = (int) $match[1];
        $part = $decimals === '' ? 0 : (int) $decimals;

        return [$whole * $denominator + ($whole < 0 || str_starts_with($match[1], '-') ? -$part : $part), $denominator];
    }

    /** Integer division rounded half away from zero. */
    private static function divideHalfUp(int $dividend, int $divisor): int
    {
        if ($divisor < 0) {
            [$dividend, $divisor] = [-$dividend, -$divisor];
        }
        $quotient = intdiv($dividend, $divisor);
        $remainder = $dividend % $divisor;
        if (abs($remainder) * 2 >= $divisor) {
            $quotient += $dividend < 0 ? -1 : 1;
        }

        return $quotient;
    }
}
