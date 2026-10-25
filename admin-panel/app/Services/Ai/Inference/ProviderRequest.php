<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

/** Provider metadata contains pseudonyms, never account, school or learner IDs. */
final readonly class ProviderRequest
{
    public function __construct(
        public Capability $capability,
        public string $operationId,
        public string $model,
        public string $anonymousActor,
        public string $systemPrompt,
        public string|array $input,
        public int $maximumTokens,
        public int $maximumTotalTokens,
        public ?InferenceDeadline $deadline = null,
    ) {}
}
