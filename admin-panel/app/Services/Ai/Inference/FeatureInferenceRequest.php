<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

use Illuminate\Support\Str;
use InvalidArgumentException;

/** Untrusted domain input, never an authorized source scope. */
final readonly class FeatureInferenceRequest
{
    public function __construct(public string $feature, public string $operationId, public array $payload)
    {
        if (! preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $feature) || ! Str::isUuid($operationId)
            || strlen(json_encode($payload, JSON_THROW_ON_ERROR, 16)) > 32768) {
            throw new InvalidArgumentException('Invalid bounded feature request.');
        }
    }
}
