<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

use Carbon\CarbonImmutable;

/**
 * Something that happened in one domain that another may react to (a mark
 * posted, a learner enrolled, a payment allocated). Subclass it per event,
 * record it with Outbox::record() inside the transaction that made the
 * change, and register listeners for its type in config/platform.php.
 *
 * The payload is stored as JSON: ids and values, never models or personal
 * data the listeners can look up themselves.
 */
abstract class DomainEvent
{
    public readonly CarbonImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = CarbonImmutable::now();
    }

    /** Dotted name listeners subscribe to, e.g. "fees.payment_recorded". */
    abstract public function type(): string;

    /** @return array<string, mixed> */
    abstract public function payload(): array;

    /** The school the event belongs to; null for platform events (and until Phase 1). */
    public function schoolId(): ?int
    {
        return null;
    }
}
