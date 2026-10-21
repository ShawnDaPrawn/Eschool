<?php

declare(strict_types=1);

namespace App\Services\Platform;

use Illuminate\Support\Facades\DB;

/**
 * Whether the queue worker is keeping up, read from the outside: on the
 * database queue, the age of the oldest job that is due and not taken by a
 * worker. A worker that has stopped, crashed in a loop or lost the database
 * shows up as jobs waiting longer and longer.
 */
class QueueHealth
{
    /**
     * Seconds the oldest due, unreserved job has waited; 0 when none waits,
     * null when the default connection is not the database queue (sync runs
     * jobs in the request, so there is nothing to wait for).
     */
    public function oldestWaitSeconds(): ?int
    {
        $connection = (string) config('queue.default');
        $settings = (array) config("queue.connections.$connection", []);
        if (($settings['driver'] ?? null) !== 'database') {
            return null;
        }

        $now = now()->getTimestamp();
        $oldest = DB::connection($settings['connection'] ?? null)
            ->table((string) ($settings['table'] ?? 'jobs'))
            ->whereNull('reserved_at')
            ->where('available_at', '<=', $now)
            ->min('available_at');

        return $oldest === null ? 0 : max(0, $now - (int) $oldest);
    }

    public function maxWaitSeconds(): int
    {
        return (int) config('queue.worker_health.max_wait_seconds', 600);
    }
}
