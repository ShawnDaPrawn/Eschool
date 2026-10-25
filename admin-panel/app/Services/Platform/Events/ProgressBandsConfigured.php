<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

class ProgressBandsConfigured extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $year)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'growth.progress_bands_configured';
    }

    public function payload(): array
    {
        return ['session_year_id' => $this->year];
    }

    public function schoolId(): int
    {
        return $this->school;
    }
}
