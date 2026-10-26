<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Ai\AiUsage;
use App\Models\Learning\TeachingDraft;
use App\Services\Ai\Quarantine\QuarantineParticipant;
use App\Services\Platform\Tenancy\CurrentSchool;

class TeachingDraftQuarantineParticipant implements QuarantineParticipant
{
    public function purgeForUsage(AiUsage $usage): void
    {
        abort_unless($usage->feature === 'teaching_assistant' && (int) $usage->school_id === app(CurrentSchool::class)->id(), 403);
        foreach (TeachingDraft::where('author_id', $usage->user_id)->where('operation_id', $usage->operation_id)->get() as $draft) {
            app(TeachingDraftRetention::class)->purge($draft);
        }
    }
}
