<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

class ContentUploaded extends DomainEvent
{
    public function __construct(private readonly int $school, private readonly int $version, private readonly string $generation)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'academics.content_uploaded';
    }

    public function schoolId(): int
    {
        return $this->school;
    }

    public function payload(): array
    {
        return ['version_id' => $this->version, 'generation_id' => $this->generation];
    }
}
