<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

class ContentPublished extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $version)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'academics.content_published';
    }

    public function schoolId(): int
    {
        return $this->school;
    }

    public function payload(): array
    {
        return ['version_id' => $this->version];
    }
}
