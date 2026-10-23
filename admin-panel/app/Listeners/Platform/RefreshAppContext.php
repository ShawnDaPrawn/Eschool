<?php

declare(strict_types=1);

namespace App\Listeners\Platform;

use App\Models\Platform\OutboxEvent;
use App\Services\Platform\Events\OutboxListener;
use App\Services\Platform\Notifications\PushQueue;
use App\Services\Platform\Notifications\PushTopics;
use App\Services\Platform\Tenancy\CurrentSchool;

class RefreshAppContext implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        if ($event->school_id === null) {
            return;
        }
        app(CurrentSchool::class)->run((int) $event->school_id, function () use ($event) {
            // Silent invalidation contains no school data. Native apps fetch
            // their authorised context again; browser apps refresh on resume.
            foreach (PushTopics::for((int) $event->school_id, 'all') as $target) {
                PushQueue::toTopic('context.changed', $target['topic'], ['message' => [
                    'topic' => $target['topic'],
                    'data' => ['type' => 'context.changed', 'school_id' => (string) $event->school_id],
                    'android' => ['priority' => 'high'],
                    'apns' => ['headers' => ['apns-push-type' => 'background', 'apns-priority' => '5'], 'payload' => ['aps' => ['content-available' => 1]]],
                ]]);
            }
        });
    }
}
