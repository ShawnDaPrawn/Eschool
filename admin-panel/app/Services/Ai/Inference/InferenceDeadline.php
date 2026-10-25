<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

/** Monotonic overall allowance shared by all outbound stages. */
final readonly class InferenceDeadline
{
    public function __construct(public int $expiresAtNanoseconds) {}

    public function remainingSeconds(): float
    {
        $remaining = ($this->expiresAtNanoseconds - hrtime(true)) / 1000000000;
        if ($remaining <= 0) {
            throw new AiUnavailable('inference_deadline_exceeded');
        }

        return $remaining;
    }
}
