<?php

declare(strict_types=1);

namespace App\Listeners\Academics;

use App\Jobs\Academics\RecomputeMarkSnapshots;
use App\Models\Academics\TeachingGroup;
use App\Models\Platform\OutboxEvent;
use App\Services\Platform\Events\OutboxListener;
use App\Services\Platform\Tenancy\CurrentSchool;

class QueueAssessmentRecompute implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        if ($event->school_id === null) {
            return;
        }
        app(CurrentSchool::class)->run((int) $event->school_id, function () use ($event) {
            $group = TeachingGroup::query()->where('subject_id', $event->payload['subject_id'])->where('grade_level_id', $event->payload['grade_level_id'])
                ->where('session_year_id', $event->payload['session_year_id'])->orderBy('id')->value('id');
            if ($group !== null) {
                RecomputeMarkSnapshots::dispatch((int) $event->school_id, (int) $group, (int) $event->payload['term_id']);
            }
        });
    }
}
