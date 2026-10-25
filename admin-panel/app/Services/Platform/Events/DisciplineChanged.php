<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

/** IDs only; no infraction, learner label, action or private reason. */
class DisciplineChanged extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $year, private readonly ?int $grade = null,
        private readonly ?int $record = null, private readonly ?int $revision = null)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return $this->record === null ? 'discipline.framework_published' : 'discipline.record_changed';
    }

    public function payload(): array
    {
        return array_filter(['session_year_id' => $this->year, 'grade_level_id' => $this->grade, 'record_id' => $this->record, 'revision' => $this->revision], fn ($v) => $v !== null);
    }

    public function schoolId(): int
    {
        return $this->school;
    }
}
