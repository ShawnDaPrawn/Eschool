<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

class ReportPublished extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $batch)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'academics.report_published';
    }

    public function schoolId(): int
    {
        return $this->school;
    }

    public function payload(): array
    {
        return ['batch_id' => $this->batch];
    }
}
