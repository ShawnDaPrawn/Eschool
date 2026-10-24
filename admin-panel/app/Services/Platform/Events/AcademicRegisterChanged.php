<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

class AcademicRegisterChanged extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $register, private readonly array $entries)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'academics.register_changed';
    }

    public function payload(): array
    {
        return ['register_id' => $this->register, 'entries' => $this->entries];
    }

    public function schoolId(): int
    {
        return $this->school;
    }
}
