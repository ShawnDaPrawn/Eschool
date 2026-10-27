<?php

declare(strict_types=1);

namespace App\Events\Money;

use App\Services\Platform\Events\DomainEvent;

final class FeeCreditPosted extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $note, private readonly int $cents)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'fees.credit_posted';
    }

    public function schoolId(): int
    {
        return $this->school;
    }

    public function payload(): array
    {
        return ['credit_note_id' => $this->note, 'amount_cents' => $this->cents];
    }
}
