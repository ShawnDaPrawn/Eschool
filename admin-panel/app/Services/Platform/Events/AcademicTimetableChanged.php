<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

class AcademicTimetableChanged extends DomainEvent
{
    /**
     * $previous: the placements lessons were moved away from (teacher, group or
     * homeroom ids, never a learner list), so the people who lost a lesson are
     * refreshed too (1.9).
     *
     * @param  list<int>  $lessons
     * @param  list<array{teacher_id: int, teaching_group_id: ?int, class_section_id: ?int, session_year_id: int}>  $previous
     */
    public function __construct(private readonly int $school, private readonly array $lessons, private readonly array $previous = [])
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'academics.timetable_changed';
    }

    public function payload(): array
    {
        return ['lesson_ids' => $this->lessons] + ($this->previous === [] ? [] : ['previous' => $this->previous]);
    }

    public function schoolId(): int
    {
        return $this->school;
    }
}
