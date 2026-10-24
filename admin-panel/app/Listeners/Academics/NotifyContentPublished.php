<?php

declare(strict_types=1);

namespace App\Listeners\Academics;

use App\Jobs\Academics\NotifyContentReaders;
use App\Models\Platform\OutboxEvent;
use App\Services\Platform\Events\OutboxListener;

class NotifyContentPublished implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        NotifyContentReaders::dispatch((int) $event->school_id, (int) $event->payload['version_id']);
    }
}
