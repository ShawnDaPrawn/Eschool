<?php

declare(strict_types=1);

namespace App\Events\Activities;

use App\Services\Platform\Events\DomainEvent;

class ActivityGroupChanged extends DomainEvent
{
    public function __construct(private int $school, private int $group, private int $revision)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'activity.group_changed';
    }

    public function schoolId(): ?int
    {
        return $this->school;
    }

    public function payload(): array
    {
        return ['group_id' => $this->group, 'revision' => $this->revision];
    }
}
