<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceProvider;
use App\Services\Ai\Inference\ProviderRequest;
use App\Services\Ai\Inference\ProviderResponse;

class DeepSeekProvider implements InferenceProvider
{
    public function infer(ProviderRequest $request): ProviderResponse
    {
        ProviderProtocol::input($request);
        if ($request->capability !== Capability::Complete) {
            throw new AiUnavailable('capability_unavailable');
        }
        $data = app(ProviderTransport::class)->post('deepseek', '/chat/completions', ['model' => $request->model,
            'messages' => [['role' => 'system', 'content' => $request->systemPrompt], ['role' => 'user', 'content' => $request->input]],
            'max_tokens' => $request->maximumTokens, 'stream' => false], $request->operationId);

        return app(OpenAiProvider::class)->chat($data, $request);
    }
}
