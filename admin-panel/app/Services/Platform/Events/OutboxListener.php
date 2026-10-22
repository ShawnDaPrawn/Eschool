<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

use App\Models\Platform\OutboxEvent;

/**
 * Reacts to a stored domain event. Registered per event type in
 * config/platform.php (outbox.listeners).
 *
 * handle() runs inside the transaction that marks the event dispatched, so
 * its database writes commit only if every listener of the event succeeds;
 * when one throws, they are rolled back and the event is retried later. A
 * listener with effects outside the database (mail, HTTP) should queue a job
 * rather than act directly, so a retry does not repeat them.
 */
interface OutboxListener
{
    public function handle(OutboxEvent $event): void;
}
