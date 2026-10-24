<?php

declare(strict_types=1);

namespace App\Listeners\Academics;

use App\Jobs\Academics\RecomputeMarkSnapshots;
use App\Models\Platform\OutboxEvent;
use App\Services\Platform\Events\OutboxListener;

class QueueMarkRecompute implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        if ($event->school_id === null || empty($event->payload['group_id'])) {
            return;
        }
        RecomputeMarkSnapshots::dispatch((int) $event->school_id, (int) $event->payload['group_id'],
            isset($event->payload['term_id']) ? (int) $event->payload['term_id'] : null, $event->type === 'academics.assignment_changed');
    }
}
