<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

/** IDs/revisions only; no learner, location, attendance or private reason in the event. */
class DisciplineDetentionChanged extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $session, private readonly int $revision)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'discipline.detention_changed';
    }

    public function payload(): array
    {
        return ['session_id' => $this->session, 'revision' => $this->revision];
    }

    public function schoolId(): int
    {
        return $this->school;
    }
}
