<?php

declare(strict_types=1);

namespace App\Listeners\Platform;

use App\Jobs\Platform\FanoutNotification;
use App\Models\Platform\OutboxEvent;
use App\Services\Platform\Events\OutboxListener;

class QueueNotificationFanout implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        $job = new FanoutNotification((int) $event->school_id, (int) $event->payload['notification_id'], $event->payload['user_ids']);
        if (config('queue.connections.'.config('queue.default').'.driver') === 'database') {
            $job->beforeCommit();
        }
        dispatch($job);
    }
}
