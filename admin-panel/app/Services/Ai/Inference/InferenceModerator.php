<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

interface InferenceModerator
{
    /** Return stable quarantine codes; throw if assessment is unavailable. */
    public function flags(Capability $capability, string $direction, string|array $content, ModerationContext $context): array;
}
