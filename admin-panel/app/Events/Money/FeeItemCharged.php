<?php

declare(strict_types=1);

namespace App\Events\Money;

use App\Services\Platform\Events\DomainEvent;

final class FeeItemCharged extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $charge, private readonly int $cents)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'fees.item_charged';
    }

    public function schoolId(): int
    {
        return $this->school;
    }

    public function payload(): array
    {
        return ['fee_charge_id' => $this->charge, 'amount_cents' => $this->cents];
    }
}
