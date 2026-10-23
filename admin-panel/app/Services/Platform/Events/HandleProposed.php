<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

class HandleProposed extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $proposal)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'handle.proposed';
    }

    public function schoolId(): int
    {
        return $this->school;
    }

    public function payload(): array
    {
        return ['proposal_id' => $this->proposal];
    }
}
