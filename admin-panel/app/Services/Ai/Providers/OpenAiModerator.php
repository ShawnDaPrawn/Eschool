<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceModerator;
use App\Services\Ai\Inference\ModerationContext;

/** Fail-closed text moderation; vision waits for the private media resolver. */
class OpenAiModerator implements InferenceModerator
{
    public function flags(Capability $capability, string $direction, string|array $content, ModerationContext $context): array
    {
        if (! in_array($direction, ['input', 'output'], true) || ! preg_match('/^[a-f0-9]{64}$/D', $context->anonymousActor)) {
            throw new AiUnavailable('moderation_context_invalid');
        }
        if ($capability === Capability::Embed && $direction === 'output' && is_array($content) && array_is_list($content) && count($content) > 0 && count($content) <= 4096) {
            foreach ($content as $value) {
                if ((! is_int($value) && ! is_float($value)) || ! is_finite((float) $value)) {
                    throw new AiUnavailable('moderation_invalid');
                }
            }

            return []; // Numeric embedding outputs have structural, not semantic, output checks.
        }
        if (! is_string($content) || strlen($content) > 262144 || ! mb_check_encoding($content, 'UTF-8')) {
            throw new AiUnavailable('moderation_invalid');
        }
        $model = config('ai.providers.openai.moderation_model');
        if (! is_string($model) || $model === '') {
            throw new AiUnavailable('moderation_unavailable');
        }
        $data = app(ProviderTransport::class)->post('openai', '/moderations', ['model' => $model, 'input' => $content], $context->operationId, $direction);
        if (! is_array($data['results'] ?? null) || count($data['results']) !== 1 || ! is_bool($data['results'][0]['flagged'] ?? null)) {
            throw new AiUnavailable('moderation_invalid');
        }

        return $data['results'][0]['flagged'] ? ['provider_content_flag'] : [];
    }
}
