<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

class AcademicMarksReleased extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $release)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'academics.marks_released';
    }

    public function payload(): array
    {
        return ['release_id' => $this->release];
    }

    public function schoolId(): int
    {
        return $this->school;
    }
}
