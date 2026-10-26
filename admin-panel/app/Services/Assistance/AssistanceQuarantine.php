<?php

declare(strict_types=1);

namespace App\Services\Assistance;

use App\Models\Ai\AiUsage;
use App\Models\Assistance\AssistanceTurn;
use App\Services\Ai\Quarantine\QuarantineParticipant;
use App\Services\Platform\Tenancy\CurrentSchool;

class AssistanceQuarantine implements QuarantineParticipant
{
    public function purgeForUsage(AiUsage $usage): void
    {
        abort_unless(in_array($usage->feature, ['school_assistant', 'message_translation'], true)
            && (int) $usage->school_id === app(CurrentSchool::class)->id(), 403);
        $turn = AssistanceTurn::whereKey($usage->operation_id)->where('user_id', $usage->user_id)->where('learner_id', $usage->learner_id)->first();
        if ($turn !== null) {
            abort_unless(app(AssistanceInference::class)->feature($turn) === $usage->feature, 403);
            app(AssistanceInference::class)->erase($turn);
        }
    }
}
