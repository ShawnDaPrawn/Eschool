<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceModerator;
use App\Services\Ai\Inference\ModerationContext;
use App\Services\Ai\Providers\OpenAiModerator;

/** Retrieval inputs get the tutor's local safeguarding guard and ordinary moderation; vectors get structural checks. */
class TutorRetrievalModeration implements InferenceModerator
{
    public function preflight(Capability $capability, string|array $input, ModerationContext $context): void
    {
        app(OpenAiModerator::class)->preflight($capability, $input, $context);
    }

    public function flags(Capability $capability, string $direction, string|array $content, ModerationContext $context): array
    {
        if ($direction === 'input' && is_string($content)) {
            $local = app(TutorModeration::class)->localFlags($content);
            if ($local !== []) {
                return $local;
            }
        }

        return app(OpenAiModerator::class)->flags($capability, $direction, $content, $context);
    }
}
