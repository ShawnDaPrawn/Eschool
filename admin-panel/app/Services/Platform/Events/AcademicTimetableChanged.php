<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

class AcademicTimetableChanged extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly array $lessons)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'academics.timetable_changed';
    }

    public function payload(): array
    {
        return ['lesson_ids' => $this->lessons];
    }

    public function schoolId(): int
    {
        return $this->school;
    }
}
