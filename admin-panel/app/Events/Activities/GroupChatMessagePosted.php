<?php

declare(strict_types=1);

namespace App\Events\Activities;

use App\Services\Platform\Events\DomainEvent;

class GroupChatMessagePosted extends DomainEvent
{
    public function __construct(private int $school, private int $group, private string $thread, private int $message, private int $revision)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'group_chat.message_posted';
    }

    public function schoolId(): ?int
    {
        return $this->school;
    }

    public function payload(): array
    {
        return ['group_id' => $this->group, 'thread_id' => $this->thread, 'message_id' => $this->message, 'revision' => $this->revision];
    }
}
