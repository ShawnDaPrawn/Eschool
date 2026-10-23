<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

class NotificationRequested extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $notification, private readonly ?array $recipients)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'notification.requested';
    }

    public function schoolId(): int
    {
        return $this->school;
    }

    public function payload(): array
    {
        return ['notification_id' => $this->notification, 'user_ids' => $this->recipients];
    }
}
