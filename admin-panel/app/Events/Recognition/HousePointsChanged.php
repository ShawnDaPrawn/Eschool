<?php

declare(strict_types=1);

namespace App\Events\Recognition;

use App\Services\Platform\Events\DomainEvent;

final class HousePointsChanged extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $year, private readonly int $house, private readonly int $revision)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'house_points.changed';
    }

    public function schoolId(): ?int
    {
        return $this->school;
    }

    public function payload(): array
    {
        return ['school_id' => $this->school, 'session_year_id' => $this->year, 'house_id' => $this->house, 'revision' => $this->revision];
    }
}
