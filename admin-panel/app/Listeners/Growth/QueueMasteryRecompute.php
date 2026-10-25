<?php

declare(strict_types=1);

namespace App\Listeners\Growth;

use App\Jobs\Growth\RecomputeMastery;
use App\Models\Academics\MarkRelease;
use App\Models\Academics\TeachingGroup;
use App\Models\Platform\OutboxEvent;
use App\Services\Platform\Events\OutboxListener;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

class QueueMasteryRecompute implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        if ($event->school_id === null) {
            return;
        }
        app(CurrentSchool::class)->run((int) $event->school_id, function () use ($event) {
            $groups = TeachingGroup::query();
            if ($event->type === 'context.changed') {
                if (($event->payload['reason'] ?? null) !== 'modules') {
                    return;
                }
                $gate = app(ModuleGate::class);
                $gate->forget();
                if (! $gate->allows('mastery')) {
                    return;
                }
                $groups->where('active', true);
            } elseif ($event->type === 'academics.marks_released') {
                $release = MarkRelease::query()->findOrFail($event->payload['release_id']);
                $groups->where('subject_id', $release->subject_id)->where('grade_level_id', $release->grade_level_id)->where('session_year_id', $release->session_year_id);
            } elseif (isset($event->payload['group_id'])) {
                $groups->whereKey((int) $event->payload['group_id']);
            } elseif ($event->type === 'academics.assessment_published' && isset($event->payload['subject_id'], $event->payload['grade_level_id'], $event->payload['session_year_id'])) {
                $groups->where('subject_id', (int) $event->payload['subject_id'])->where('grade_level_id', (int) $event->payload['grade_level_id'])
                    ->where('session_year_id', (int) $event->payload['session_year_id']);
            } else {
                return;
            }
            foreach ($groups->pluck('id') as $group) {
                RecomputeMastery::dispatch((int) $event->school_id, (int) $group);
            }
        });
    }
}
