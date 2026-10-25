<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceNotDispatched;
use App\Services\Ai\Inference\InferenceProvider;
use App\Services\Ai\Inference\ProviderRequest;
use App\Services\Ai\Inference\ProviderResponse;

class AnthropicProvider implements InferenceProvider
{
    private function payload(ProviderRequest $request): array
    {
        ProviderProtocol::input($request);
        if ($request->capability !== Capability::Complete) {
            throw new InferenceNotDispatched('media_policy_required');
        }

        return ['model' => $request->model, 'system' => $request->systemPrompt,
            'messages' => [['role' => 'user', 'content' => $request->input]], 'metadata' => ['user_id' => $request->anonymousActor],
            'max_tokens' => $request->maximumTokens, 'stream' => false];
    }

    public function preflight(ProviderRequest $request): void
    {
        app(ProviderTransport::class)->preflight('anthropic', '/messages', $this->payload($request), $request->operationId, deadline: $request->deadline);
    }

    public function infer(ProviderRequest $request): ProviderResponse
    {
        $data = app(ProviderTransport::class)->post('anthropic', '/messages', $this->payload($request), $request->operationId, deadline: $request->deadline);
        if (($data['model'] ?? null) !== $request->model || ($data['role'] ?? null) !== 'assistant' || ! in_array($data['stop_reason'] ?? null, ['end_turn', 'stop_sequence'], true)
            || ! is_array($data['content'] ?? null) || count($data['content']) < 1 || count($data['content']) > 16 || ! is_array($data['usage'] ?? null)) {
            throw new AiUnavailable('provider_protocol_invalid');
        }
        $text = '';
        foreach ($data['content'] as $block) {
            if (! is_array($block) || ($block['type'] ?? null) !== 'text') {
                throw new AiUnavailable('provider_protocol_invalid');
            }
            $text .= ProviderProtocol::text($block['text'] ?? null);
        }
        $usage = $data['usage'];
        // Cached input is billed too; it must fit the same conservative reservation.
        foreach (['cache_creation_input_tokens', 'cache_read_input_tokens'] as $key) {
            if (isset($usage[$key])) {
                if (! is_int($usage[$key]) || $usage[$key] < 0 || ! is_int($usage['input_tokens'] ?? null)) {
                    throw new AiUnavailable('provider_usage_invalid');
                }
                $usage['input_tokens'] += $usage[$key];
            }
        }

        return new ProviderResponse(ProviderProtocol::text($text), ProviderProtocol::tokens($usage, 'input_tokens', 'output_tokens', $request));
    }
}
