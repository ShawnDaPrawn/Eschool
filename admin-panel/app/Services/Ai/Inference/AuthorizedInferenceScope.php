<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

use InvalidArgumentException;

/** Only a registered server policy constructs the purpose-specific minimized scope. */
final readonly class AuthorizedInferenceScope
{
    public function __construct(
        public int $schoolId,
        public int $actorId,
        public InferenceRequest $inference,
        public ?int $learnerId = null,
        public ?int $subjectId = null,
        public int $timeoutMilliseconds = 10000,
        public InferenceAudience $audience = InferenceAudience::Family,
    ) {
        if ($schoolId < 1 || $actorId < 1 || ($learnerId !== null && $learnerId < 1) || ($subjectId !== null && $subjectId < 1)
            || $timeoutMilliseconds < 100 || $timeoutMilliseconds > 10000) {
            throw new InvalidArgumentException('Invalid feature authorization scope.');
        }
    }
}
