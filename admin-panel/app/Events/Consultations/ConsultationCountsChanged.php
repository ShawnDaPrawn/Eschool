<?php

declare(strict_types=1);

namespace App\Events\Consultations;

use App\Services\Platform\Events\DomainEvent;

/** Public aggregate invalidation only; never a ballot or participant identifier. */
class ConsultationCountsChanged extends DomainEvent
{
    public function __construct(private int $school, private int $consultation, private int $revision)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'consultations.counts_changed';
    }

    public function schoolId(): ?int
    {
        return $this->school;
    }

    public function payload(): array
    {
        return ['consultation_id' => $this->consultation, 'revision' => $this->revision];
    }
}
