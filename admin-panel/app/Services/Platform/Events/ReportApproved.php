<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

class ReportApproved extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $batch, private readonly string $fingerprint)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'academics.report_approved';
    }

    public function schoolId(): int
    {
        return $this->school;
    }

    public function payload(): array
    {
        return ['batch_id' => $this->batch, 'fingerprint' => $this->fingerprint];
    }
}
