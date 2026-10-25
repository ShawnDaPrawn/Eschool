<?php

declare(strict_types=1);

namespace App\Listeners\Growth;

use App\Jobs\Growth\CheckProgressBands;
use App\Models\Academics\MarkRelease;
use App\Models\Academics\TeachingGroup;
use App\Models\Platform\OutboxEvent;
use App\Services\Platform\Events\OutboxListener;
use App\Services\Platform\Tenancy\CurrentSchool;

class QueueProgressBands implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        if ($event->school_id === null) {
            return;
        }
        app(CurrentSchool::class)->run((int) $event->school_id, function () use ($event) {
            if ($event->type === 'academics.marks_released') {
                CheckProgressBands::dispatch((int) $event->school_id, null, (int) $event->payload['release_id']);

                return;
            }
            $groups = TeachingGroup::where('active', true);
            if ($event->type === 'growth.progress_bands_configured') {
                $year = (int) $event->payload['session_year_id'];
                $groups->where('session_year_id', $year);
                MarkRelease::where('session_year_id', $year)->whereNotNull('term_id')->select('id')->chunkById(100, function ($rows) use ($event) {
                    foreach ($rows as $release) {
                        CheckProgressBands::dispatch((int) $event->school_id, null, (int) $release->id);
                    }
                });
            } elseif (isset($event->payload['group_id'])) {
                $groups->whereKey((int) $event->payload['group_id']);
            } elseif ($event->type === 'academics.assessment_published') {
                $groups->where('subject_id', (int) $event->payload['subject_id'])->where('grade_level_id', (int) $event->payload['grade_level_id'])
                    ->where('session_year_id', (int) $event->payload['session_year_id']);
            } elseif ($event->type !== 'context.changed' || ($event->payload['reason'] ?? null) !== 'modules') {
                return;
            }
            $groups->select('id')->chunkById(100, function ($rows) use ($event) {
                foreach ($rows as $group) {
                    CheckProgressBands::dispatch((int) $event->school_id, (int) $group->id);
                }
            });
        });
    }
}
