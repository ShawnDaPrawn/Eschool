<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

interface InferenceModerator
{
    /** Check qualification before any external input moderation or inference. */
    public function preflight(Capability $capability, string|array $input, ModerationContext $context): void;

    /** Return stable quarantine codes; throw if assessment is unavailable. */
    public function flags(Capability $capability, string $direction, string|array $content, ModerationContext $context): array;
}
