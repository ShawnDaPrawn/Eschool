<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceNotDispatched;
use App\Services\Ai\Inference\InferenceProvider;
use App\Services\Ai\Inference\ProviderRequest;
use App\Services\Ai\Inference\ProviderResponse;

class DeepSeekProvider implements InferenceProvider
{
    private function payload(ProviderRequest $request): array
    {
        ProviderProtocol::input($request);
        if ($request->capability !== Capability::Complete) {
            throw new InferenceNotDispatched('capability_unavailable');
        }

        return ['model' => $request->model,
            'messages' => [['role' => 'system', 'content' => $request->systemPrompt], ['role' => 'user', 'content' => $request->input]],
            'max_tokens' => $request->maximumTokens, 'stream' => false];
    }

    public function preflight(ProviderRequest $request): void
    {
        app(ProviderTransport::class)->preflight('deepseek', '/chat/completions', $this->payload($request), $request->operationId, deadline: $request->deadline);
    }

    public function infer(ProviderRequest $request): ProviderResponse
    {
        $data = app(ProviderTransport::class)->post('deepseek', '/chat/completions', $this->payload($request), $request->operationId, deadline: $request->deadline);

        return app(OpenAiProvider::class)->chat($data, $request);
    }
}
