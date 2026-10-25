<?php

declare(strict_types=1);

namespace App\Listeners\Consultations;

use App\Models\Platform\OutboxEvent;
use App\Services\Consultations\ConsultationNotices;
use App\Services\Platform\Events\OutboxListener;
use App\Services\Platform\Tenancy\CurrentSchool;

class NotifyConsultationOpened implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        app(CurrentSchool::class)->run((int) $event->school_id, fn () => app(ConsultationNotices::class)->opened((int) ($event->payload['consultation_id'] ?? 0), (int) ($event->payload['revision'] ?? 0)));
    }
}
