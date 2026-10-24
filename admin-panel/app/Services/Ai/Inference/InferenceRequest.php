<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

use Illuminate\Support\Str;
use InvalidArgumentException;

/** A feature must resolve its subject/source/media access before invoking this boundary. */
final readonly class InferenceRequest
{
    public function __construct(
        public string $feature,
        public string $operationId,
        public string $prompt,
        public string|array $input,
        public array $sources = [],
    ) {
        if (! preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $feature) || ! Str::isUuid($operationId)
            || strlen(json_encode($input, JSON_THROW_ON_ERROR)) > 65536 || count($sources) > 64) {
            throw new InvalidArgumentException('Invalid bounded inference request.');
        }
        foreach ($sources as $source) {
            if (! is_string($source) || strlen($source) > 128) {
                throw new InvalidArgumentException('Invalid source reference.');
            }
        }
    }
}
