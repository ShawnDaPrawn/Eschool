<?php

declare(strict_types=1);

namespace App\Listeners\Wellbeing;

use App\Models\Platform\OutboxEvent;
use App\Services\Platform\Events\OutboxListener;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Wellbeing\WellbeingNotices;

class QueuePrivateNotice implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        app(CurrentSchool::class)->run((int) $event->school_id, fn () => app(WellbeingNotices::class)->queue((string) ($event->payload['intent_id'] ?? '')));
    }
}
