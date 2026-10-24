<?php

declare(strict_types=1);

namespace App\Listeners\Academics;

use App\Models\Platform\OutboxEvent;
use App\Services\Academics\Attendance\RegisterNotices;
use App\Services\Platform\Events\OutboxListener;
use App\Services\Platform\Tenancy\CurrentSchool;

class NotifyRegisterAmended implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        if ($event->school_id === null) {
            return;
        }
        app(CurrentSchool::class)->run((int) $event->school_id, function () use ($event) {
            foreach ($event->payload['entries'] ?? [] as $entry) {
                app(RegisterNotices::class)->entry((int) $entry['id'], 'amended', (int) $entry['revision']);
            }
        });
    }
}
