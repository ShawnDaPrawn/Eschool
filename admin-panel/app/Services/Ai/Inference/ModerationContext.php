<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

/** No raw identifiers cross the moderation provider boundary. */
final readonly class ModerationContext
{
    public function __construct(public string $operationId, public string $anonymousActor) {}
}
