<?php

declare(strict_types=1);

namespace App\Listeners\Live;

use App\Jobs\Live\PublishLiveInvalidation;
use App\Models\Platform\OutboxEvent;
use App\Services\Platform\Events\OutboxListener;
use Illuminate\Support\Facades\DB;

/** A database-queue insertion commits atomically with the outbox claim. */
class QueueLiveInvalidation implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        if (! config('live.enabled', false)) {
            return;
        }
        // An external queue could release a job before this database transaction commits.
        abort_unless(config('queue.connections.database.driver') === 'database'
            && (config('queue.connections.database.connection') ?? DB::getDefaultConnection()) === DB::getDefaultConnection(), 503);
        PublishLiveInvalidation::dispatch((int) $event->school_id, (int) $event->id)
            ->onConnection('database')->onQueue('live')->beforeCommit();
    }
}
