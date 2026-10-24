<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

class AcademicMarksChanged extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $group, private readonly int $term)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'academics.marks_changed';
    }

    public function payload(): array
    {
        return ['group_id' => $this->group, 'term_id' => $this->term];
    }

    public function schoolId(): int
    {
        return $this->school;
    }
}
