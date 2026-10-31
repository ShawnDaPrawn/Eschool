<?php

declare(strict_types=1);

namespace App\Listeners\Academics;

use App\Models\Platform\OutboxEvent;
use App\Services\Academics\TimetableRefreshPush;
use App\Services\Platform\Events\OutboxListener;
use App\Services\Platform\Tenancy\CurrentSchool;

/** academics.timetable_changed (timetable saves and short days): silent refresh pushes (1.9). */
class RefreshTimetables implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        if ($event->school_id === null) {
            return;
        }
        app(CurrentSchool::class)->run((int) $event->school_id, fn () => app(TimetableRefreshPush::class)->queue(array_values(array_filter(array_map('intval', (array) ($event->payload['lesson_ids'] ?? []))))));
    }
}
