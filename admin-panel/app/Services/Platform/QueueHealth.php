<?php

declare(strict_types=1);

namespace App\Services\Platform;

use DateTimeInterface;
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
     * Seconds the oldest due job has waited; 0 when none waits, null when the
     * default connection is not the database queue (sync runs jobs in the
     * request, so there is nothing to wait for).
     *
     * A job is due when it is unreserved and available, or when its worker
     * reserved it more than `retry_after` seconds ago (the worker died, and
     * Laravel hands the job out again from then on), so a crashed worker's
     * job counts as waiting too.
     */
    public function oldestWaitSeconds(): ?int
    {
        $connection = (string) config('queue.default');
        $settings = (array) config("queue.connections.$connection", []);
        if (($settings['driver'] ?? null) !== 'database') {
            return null;
        }

        $now = now()->getTimestamp();
        $retryAfter = (int) ($settings['retry_after'] ?? 90);
        $jobs = fn () => DB::connection($settings['connection'] ?? null)
            ->table((string) ($settings['table'] ?? 'jobs'));

        $oldestAvailable = $jobs()
            ->whereNull('reserved_at')
            ->where('available_at', '<=', $now)
            ->min('available_at');
        $oldestExpiredReservation = $jobs()
            ->where('reserved_at', '<=', $now - $retryAfter)
            ->min('reserved_at');

        $dueSince = array_filter([
            $oldestAvailable === null ? null : (int) $oldestAvailable,
            $oldestExpiredReservation === null ? null : (int) $oldestExpiredReservation + $retryAfter,
        ], fn (?int $at) => $at !== null);

        return $dueSince === [] ? 0 : max(0, $now - min($dueSince));
    }

    public function maxWaitSeconds(): int
    {
        return (int) config('queue.worker_health.max_wait_seconds', 600);
    }

    /**
     * Jobs waiting on the database queue: due and not taken by a live
     * worker, as oldestWaitSeconds() counts them. Null when the default
     * connection is not the database queue. Counts only: no payload is read.
     */
    public function backlog(): ?int
    {
        $connection = (string) config('queue.default');
        $settings = (array) config("queue.connections.$connection", []);
        if (($settings['driver'] ?? null) !== 'database') {
            return null;
        }
        $now = now()->getTimestamp();

        return DB::connection($settings['connection'] ?? null)->table((string) ($settings['table'] ?? 'jobs'))
            ->where(fn ($due) => $due->where(fn ($free) => $free->whereNull('reserved_at')->where('available_at', '<=', $now))
                ->orWhere('reserved_at', '<=', $now - (int) ($settings['retry_after'] ?? 90)))
            ->count();
    }

    /**
     * Jobs that failed since a moment, and all still kept, from the failed
     * jobs table. Counts only: no payload or exception text is read.
     *
     * @return array{since: int, kept: int}
     */
    public function failedJobs(DateTimeInterface $since): array
    {
        $failed = fn () => DB::connection(config('queue.failed.database'))->table((string) config('queue.failed.table', 'failed_jobs'));

        return ['since' => $failed()->where('failed_at', '>=', $since)->count(), 'kept' => $failed()->count()];
    }
}
