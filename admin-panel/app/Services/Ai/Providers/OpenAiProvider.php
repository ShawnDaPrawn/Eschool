<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceNotDispatched;
use App\Services\Ai\Inference\InferenceProvider;
use App\Services\Ai\Inference\ProviderRequest;
use App\Services\Ai\Inference\ProviderResponse;

class OpenAiProvider implements InferenceProvider
{
    private function payload(ProviderRequest $request): array
    {
        ProviderProtocol::input($request);
        if ($request->capability === Capability::Embed) {
            return ['/embeddings', ['model' => $request->model, 'input' => $request->input, 'user' => $request->anonymousActor, 'encoding_format' => 'float']];
        }
        if (! in_array($request->capability, [Capability::Complete, Capability::Vision], true)) {
            throw new InferenceNotDispatched('media_policy_required');
        }
        $content = $request->input;
        if ($request->capability === Capability::Vision) {
            $content = [['type' => 'text', 'text' => $request->input['text']]];
            foreach ($request->input['images'] as $image) {
                $content[] = ['type' => 'image_url', 'image_url' => ['url' => 'data:image/png;base64,'.$image['data'], 'detail' => 'high']];
            }
        }

        return ['/chat/completions', ['model' => $request->model,
            'messages' => [['role' => 'system', 'content' => $request->systemPrompt], ['role' => 'user', 'content' => $content]],
            'max_completion_tokens' => $request->maximumTokens, 'n' => 1, 'stream' => false, 'store' => false, 'user' => $request->anonymousActor]];
    }

    public function preflight(ProviderRequest $request): void
    {
        [$path, $payload] = $this->payload($request);
        app(ProviderTransport::class)->preflight('openai', $path, $payload, $request->operationId, deadline: $request->deadline, vision: $request->capability === Capability::Vision);
    }

    public function infer(ProviderRequest $request): ProviderResponse
    {
        [$path, $payload] = $this->payload($request);
        $data = app(ProviderTransport::class)->post('openai', $path, $payload, $request->operationId, deadline: $request->deadline, vision: $request->capability === Capability::Vision);
        if ($request->capability === Capability::Embed) {
            $vectors = $data['data'] ?? null;
            $tokens = $data['usage']['total_tokens'] ?? null;
            if (($data['model'] ?? null) !== $request->model || ! is_array($vectors) || count($vectors) !== 1 || ($vectors[0]['index'] ?? null) !== 0
                || ! is_array($vectors[0]['embedding'] ?? null) || ! array_is_list($vectors[0]['embedding']) || count($vectors[0]['embedding']) < 1
                || count($vectors[0]['embedding']) > 4096 || ! is_int($tokens) || $tokens < 1 || $tokens > $request->maximumTotalTokens) {
                throw new AiUnavailable('provider_protocol_invalid');
            }
            foreach ($vectors[0]['embedding'] as $value) {
                if ((! is_int($value) && ! is_float($value)) || ! is_finite((float) $value)) {
                    throw new AiUnavailable('provider_protocol_invalid');
                }
            }

            return new ProviderResponse($vectors[0]['embedding'], $tokens);
        }

        return $this->chat($data, $request);
    }

    public function chat(array $data, ProviderRequest $request): ProviderResponse
    {
        if (($data['model'] ?? null) !== $request->model || ! is_array($data['choices'] ?? null) || count($data['choices']) !== 1 || ($data['choices'][0]['finish_reason'] ?? null) !== 'stop'
            || ($data['choices'][0]['message']['role'] ?? null) !== 'assistant' || ! empty($data['choices'][0]['message']['tool_calls'])
            || ! empty($data['choices'][0]['message']['function_call']) || ! empty($data['choices'][0]['message']['refusal']) || ! is_array($data['usage'] ?? null)) {
            throw new AiUnavailable('provider_protocol_invalid');
        }

        return new ProviderResponse(ProviderProtocol::text($data['choices'][0]['message']['content'] ?? null), ProviderProtocol::tokens($data['usage'], 'prompt_tokens', 'completion_tokens', $request));
    }
}
