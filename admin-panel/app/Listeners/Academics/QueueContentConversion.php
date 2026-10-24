<?php

declare(strict_types=1);

namespace App\Listeners\Academics;

use App\Jobs\Academics\ConvertContent;
use App\Models\Academics\ContentVersion;
use App\Models\Platform\OutboxEvent;
use App\Services\Platform\Events\OutboxListener;
use App\Services\Platform\Tenancy\CurrentSchool;

class QueueContentConversion implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        app(CurrentSchool::class)->run((int) $event->school_id, function () use ($event) {
            $v = ContentVersion::where('state', 'queued')->where('generation_id', $event->payload['generation_id'])->find($event->payload['version_id']);
            if ($v !== null) {
                ConvertContent::dispatch((int) $event->school_id, (int) $v->id, $v->generation_id);
            }
        });
    }
}
