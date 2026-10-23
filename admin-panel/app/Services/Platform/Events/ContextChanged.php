<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

class ContextChanged extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly string $reason)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'context.changed';
    }

    public function payload(): array
    {
        return ['reason' => $this->reason];
    }

    public function schoolId(): int
    {
        return $this->school;
    }
}
