<?php

declare(strict_types=1);

namespace App\Jobs\Live;

use App\Services\Live\LivePublisher;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** The job carries only source references; fresh authorization runs on each attempt. */
class PublishLiveInvalidation implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 4;

    public int $timeout = 20;

    public function __construct(public readonly int $schoolId, public readonly int $eventId) {}

    public function backoff(): array
    {
        return [1, 5, 30];
    }

    public function handle(LivePublisher $publisher): void
    {
        if (DB::transactionLevel() !== 0) {
            throw new RuntimeException('Live publication requires a committed source transaction.');
        }
        app(CurrentSchool::class)->run($this->schoolId, fn () => $publisher->event($this->eventId));
    }
}
