<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

class AcademicAssignmentChanged extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly string $kind, private readonly int $id, private readonly ?int $group)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'academics.assignment_changed';
    }

    public function payload(): array
    {
        return ['kind' => $this->kind, 'id' => $this->id, 'group_id' => $this->group];
    }

    public function schoolId(): int
    {
        return $this->school;
    }
}
