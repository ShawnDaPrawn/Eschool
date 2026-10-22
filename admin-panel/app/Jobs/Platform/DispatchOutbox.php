<?php

declare(strict_types=1);

namespace App\Jobs\Platform;

use App\Services\Platform\Events\Outbox;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Hands undispatched domain events to their listeners. Scheduled every
 * minute (routes/console.php) and runnable on demand with
 * `php artisan outbox:dispatch`. Runs that overlap are safe: each event is
 * locked while it is dispatched. Retries are per event (Outbox), so the job
 * itself is not retried.
 */
class DispatchOutbox implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 110;

    /**
     * @return array{dispatched: int, failed: int, skipped: int}
     */
    public function handle(): array
    {
        $stats = Outbox::dispatchPending();
        if ($stats['dispatched'] + $stats['failed'] > 0) {
            Log::info('outbox_dispatch', $stats);
        }

        return $stats;
    }

    public function failed(?Throwable $e): void
    {
        Log::error('job_failed', ['job' => class_basename($this), 'exception' => $e ? get_class($e) : null]);
    }
}
