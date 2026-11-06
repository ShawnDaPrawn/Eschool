<?php

declare(strict_types=1);

namespace App\Services\Reading;

/**
 * The seam for AI-written comprehension checks (Action Plan 4.4). They would
 * go through the AI gateway under the tutor's feature policy (moderation,
 * metering, the school's AI controls), asking only about the public-domain
 * text and never sending anything about the learner. The AI provider is not
 * qualified yet, so this stays off: available() is false, with no setting
 * to change that, until the policy is written and qualified. v1 uses the
 * questions HQ content staff write (ReadingCatalogue::addQuestion).
 */
class ReadingAiChecks
{
    public function available(): bool
    {
        return false;
    }
}
