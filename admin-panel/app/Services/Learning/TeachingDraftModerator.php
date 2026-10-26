<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\TeachingDraft;
use App\Models\User;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceModerator;
use App\Services\Ai\Inference\ModerationContext;
use App\Services\Ai\Providers\OpenAiModerator;

class TeachingDraftModerator implements InferenceModerator
{
    public function preflight(Capability $capability, string|array $input, ModerationContext $context): void
    {
        app(OpenAiModerator::class)->preflight($capability, $input, $context);
    }

    public function flags(Capability $capability, string $direction, string|array $content, ModerationContext $context): array
    {
        $flags = app(OpenAiModerator::class)->flags($capability, $direction, $content, $context);
        if ($flags !== [] || $direction !== 'output') {
            return $flags;
        }
        $meta = TeachingDraft::where('operation_id', $context->operationId)->select('id', 'author_id')->firstOrFail();
        $actor = User::findOrFail($meta->author_id);
        $draft = app(TeachingDraftFeaturePolicy::class)->draft($actor, $meta->id);
        try {
            if (! is_string($content)) {
                throw new AiUnavailable('teaching_output_invalid');
            }
            app(TeachingDraftDefinitions::class)->parse($actor, $draft, $content);
        } catch (AiUnavailable) {
            return ['teaching_output_invalid'];
        }

        return [];
    }
}
