<?php

declare(strict_types=1);

namespace App\Services\Wellbeing;

use App\Services\Platform\Events\DomainEvent;

/** Private outbox work, never a schoolwide live event. */
class NoticeRequested extends DomainEvent
{
    public function __construct(private int $school, private string $intent)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'wellbeing.notice_requested';
    }

    public function schoolId(): int
    {
        return $this->school;
    }

    public function payload(): array
    {
        return ['intent_id' => $this->intent];
    }
}
