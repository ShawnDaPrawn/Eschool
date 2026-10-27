<?php

declare(strict_types=1);

namespace App\Events\Money;

use App\Services\Platform\Events\DomainEvent;

final class FeeMoneyChanged extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $payment, private readonly int $cents, private readonly ?int $allocation = null)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return $this->allocation === null ? 'fees.payment_recorded' : 'fees.payment_allocated';
    }

    public function schoolId(): int
    {
        return $this->school;
    }

    public function payload(): array
    {
        return ['payment_id' => $this->payment, 'amount_cents' => $this->cents, 'allocation_run_id' => $this->allocation];
    }
}
