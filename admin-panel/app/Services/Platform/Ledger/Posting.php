<?php

declare(strict_types=1);

namespace App\Services\Platform\Ledger;

use InvalidArgumentException;

final readonly class Posting
{
    public function __construct(public int $accountId, public Money $amount, public string $direction)
    {
        if ($accountId < 1 || $amount->cents < 1 || ! in_array($direction, ['debit', 'credit'], true)) {
            throw new InvalidArgumentException('A posting requires an account, positive integer cents and debit or credit.');
        }
    }

    public static function debit(int $accountId, Money $amount): self
    {
        return new self($accountId, $amount, 'debit');
    }

    public static function credit(int $accountId, Money $amount): self
    {
        return new self($accountId, $amount, 'credit');
    }
}
