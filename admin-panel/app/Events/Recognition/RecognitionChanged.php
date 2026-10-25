<?php

declare(strict_types=1);

namespace App\Events\Recognition;

use App\Services\Platform\Events\DomainEvent;

/** Private source reference; never publish this on the aggregate school channel. */
final class RecognitionChanged extends DomainEvent
{
    public function __construct(private int $school, private string $kind, private int $source, private int $revision)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'recognition.changed';
    }

    public function schoolId(): int
    {
        return $this->school;
    }

    public function payload(): array
    {
        return ['kind' => $this->kind, 'source_id' => $this->source, 'revision' => $this->revision];
    }
}
