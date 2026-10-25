<?php

declare(strict_types=1);

namespace App\Jobs\Live;

use App\Services\Platform\Events\Outbox;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DispatchLiveOutbox implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 1;

    public int $timeout = 20;

    public function __construct(public readonly int $eventId) {}

    public function handle(): void
    {
        Outbox::dispatchOne($this->eventId);
    }
}
