<?php

declare(strict_types=1);

namespace App\Services\Activities\Fixtures;

use App\Models\Ai\AiUsage;
use App\Services\Ai\Quarantine\QuarantineParticipant;
use App\Services\Platform\Tenancy\CurrentSchool;

/** Quarantine retention for AI feedback on reasoning: erases what is left of the review, by its immutable operation and requester. */
class ReasoningFeedbackQuarantine implements QuarantineParticipant
{
    public function purgeForUsage(AiUsage $usage): void
    {
        abort_unless($usage->feature === ReasoningReviews::FEATURE && (int) $usage->school_id === app(CurrentSchool::class)->id(), 403);
        app(ReasoningReviews::class)->erase((string) $usage->operation_id, (int) $usage->user_id);
    }
}
