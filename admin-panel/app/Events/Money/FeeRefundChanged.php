<?php

declare(strict_types=1);

namespace App\Events\Money;

use App\Services\Platform\Events\DomainEvent;

final class FeeRefundChanged extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $refund, private readonly int $cents, private readonly string $action)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'fees.refund_changed';
    }

    public function schoolId(): int
    {
        return $this->school;
    }

    public function payload(): array
    {
        return ['refund_id' => $this->refund, 'amount_cents' => $this->cents, 'action' => $this->action];
    }
}
