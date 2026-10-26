<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Ai\AiUsage;
use App\Models\Learning\ExamAnswer;
use App\Services\Ai\Quarantine\QuarantineParticipant;

class ExamAnswerQuarantine implements QuarantineParticipant
{
    public function purgeForUsage(AiUsage $usage): void
    {
        abort_unless($usage->feature === 'exam_coach', 422);
        foreach (ExamAnswer::where('operation_id', $usage->operation_id)->where('user_id', $usage->user_id)->where('learner_id', $usage->learner_id)->get() as $row) {
            app(ExamWorkingPhotos::class)->purge($row);
            $row->update(['state' => 'purged']);
        }
    }
}
