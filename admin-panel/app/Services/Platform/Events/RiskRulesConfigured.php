<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

class RiskRulesConfigured extends DomainEvent
{
    public function __construct(private readonly int $school)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'growth.risk_rules_configured';
    }

    public function payload(): array
    {
        return [];
    }

    public function schoolId(): int
    {
        return $this->school;
    }
}
