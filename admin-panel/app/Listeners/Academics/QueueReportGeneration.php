<?php

declare(strict_types=1);

namespace App\Listeners\Academics;

use App\Jobs\Academics\GenerateReportCard;
use App\Models\Academics\ReportBatch;
use App\Models\Platform\OutboxEvent;
use App\Services\Platform\Events\OutboxListener;
use App\Services\Platform\Tenancy\CurrentSchool;

class QueueReportGeneration implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        app(CurrentSchool::class)->run((int) $event->school_id, function () use ($event) {
            $batch = ReportBatch::find($event->payload['batch_id']);
            if ($batch === null || ! in_array($batch->state, ['approved', 'ready'], true) || $batch->fingerprint !== $event->payload['fingerprint']) {
                return;
            }
            foreach ($batch->cards()->pluck('id') as $id) {
                GenerateReportCard::dispatch((int) $event->school_id, (int) $id, $batch->fingerprint);
            }
        });
    }
}
