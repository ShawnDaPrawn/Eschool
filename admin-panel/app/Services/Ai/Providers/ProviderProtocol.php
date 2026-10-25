<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\InferenceNotDispatched;
use App\Services\Ai\Inference\ProviderRequest;
use Illuminate\Support\Str;

class ProviderProtocol
{
    public static function input(ProviderRequest $request): void
    {
        if (! Str::isUuid($request->operationId) || ! preg_match('/^[a-f0-9]{64}$/D', $request->anonymousActor)
            || ! preg_match('/^[a-zA-Z0-9._:-]{1,128}$/D', $request->model)
            || ! is_string($request->input) || ! mb_check_encoding($request->input, 'UTF-8') || str_contains($request->input, "\0")
            || strlen($request->input) > 65536 || strlen($request->systemPrompt) > 32768 || ! mb_check_encoding($request->systemPrompt, 'UTF-8')
            || $request->maximumTokens < 1 || $request->maximumTokens > 32768 || $request->maximumTotalTokens > 131072
            || $request->maximumTotalTokens < strlen($request->input) + strlen($request->systemPrompt) + 256 + $request->maximumTokens) {
            throw new InferenceNotDispatched('provider_request_invalid');
        }
    }

    public static function tokens(array $usage, string $inputKey, string $outputKey, ProviderRequest $request): int
    {
        $input = $usage[$inputKey] ?? null;
        $output = $usage[$outputKey] ?? null;
        if (! is_int($input) || ! is_int($output) || $input < 0 || $output < 0 || $output > $request->maximumTokens
            || $input + $output < 1 || $input + $output > $request->maximumTotalTokens
            || (array_key_exists('total_tokens', $usage) && $usage['total_tokens'] !== $input + $output)) {
            throw new AiUnavailable('provider_usage_invalid');
        }

        return $input + $output;
    }

    public static function text(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '' || strlen($value) > 262144 || ! mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")) {
            throw new AiUnavailable('provider_protocol_invalid');
        }

        return $value;
    }
}
