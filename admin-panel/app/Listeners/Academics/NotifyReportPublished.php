<?php

declare(strict_types=1);

namespace App\Listeners\Academics;

use App\Models\Platform\OutboxEvent;
use App\Services\Academics\Reports\ReportNotices;
use App\Services\Platform\Events\OutboxListener;
use App\Services\Platform\Tenancy\CurrentSchool;

class NotifyReportPublished implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        app(CurrentSchool::class)->run((int) $event->school_id, fn () => app(ReportNotices::class)->send((int) $event->payload['batch_id']));
    }
}
