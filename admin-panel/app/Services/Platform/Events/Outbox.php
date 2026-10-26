<?php

declare(strict_types=1);

namespace App\Services\Platform\Events;

use App\Models\Platform\OutboxEvent;
use App\Services\Live\LiveDispatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

/**
 * The transactional outbox. record() writes a domain event in the caller's
 * transaction, so the event exists exactly when the change it describes
 * does; dispatchPending() (the DispatchOutbox job, every minute) hands each
 * event to its listeners once.
 */
class Outbox
{
    /**
     * Store an event. Must be called inside the database transaction that
     * makes the change, so a rollback removes the event too.
     *
     * @throws LogicException outside a transaction
     */
    public static function record(DomainEvent $event): OutboxEvent
    {
        if (! self::inTransaction()) {
            throw new LogicException('Outbox::record() must be called inside the database transaction that makes the change ('.$event->type().').');
        }

        $row = OutboxEvent::create([
            'school_id' => $event->schoolId(),
            'type' => $event->type(),
            'payload' => $event->payload(),
            'occurred_at' => $event->occurredAt,
            'attempts' => 0,
        ]);
        LiveDispatch::afterCommit($row);

        return $row;
    }

    /**
     * Hand undispatched events (oldest first) to their listeners.
     *
     * @return array{dispatched: int, failed: int, skipped: int}
     */
    public static function dispatchPending(?int $limit = null): array
    {
        $limit ??= max(1, (int) config('platform.outbox.batch', 100));
        $ids = OutboxEvent::query()
            ->whereNull('dispatched_at')
            ->where('attempts', '<', self::maxAttempts())
            ->where(fn ($query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $stats = ['dispatched' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($ids as $id) {
            $stats[self::dispatchOne((int) $id)]++;
        }

        return $stats;
    }

    /**
     * Run one event's listeners and mark it dispatched, in one transaction.
     * The row lock and the dispatched_at check mean two runners never
     * dispatch the same event; a failing listener rolls back the others'
     * writes and the event is retried after a backoff.
     *
     * @return 'dispatched'|'failed'|'skipped'
     */
    public static function dispatchOne(int $id): string
    {
        $type = null;

        try {
            return DB::transaction(function () use ($id, &$type) {
                $event = OutboxEvent::whereKey($id)->whereNull('dispatched_at')->lockForUpdate()->first();
                if (! $event || ($event->available_at && $event->available_at->isFuture())) {
                    return 'skipped';
                }
                $type = $event->type;

                foreach (self::listeners($event->type) as $listener) {
                    $listener->handle($event);
                }

                $event->forceFill(['dispatched_at' => now(), 'last_error' => null])->save();

                return 'dispatched';
            });
        } catch (Throwable $e) {
            self::recordFailure($id, $type, $e);

            return 'failed';
        }
    }

    /**
     * @return list<OutboxListener>
     */
    private static function listeners(string $type): array
    {
        $classes = (array) (config('platform.outbox.listeners', [])[$type] ?? []);

        return array_map(static function (string $class) {
            $listener = app($class);
            if (! $listener instanceof OutboxListener) {
                throw new LogicException($class.' does not implement '.OutboxListener::class);
            }

            return $listener;
        }, array_values($classes));
    }

    private static function recordFailure(int $id, ?string $type, Throwable $e): void
    {
        $event = OutboxEvent::find($id);
        if (! $event || $event->dispatched_at) {
            return;
        }
        $attempts = $event->attempts + 1;
        $backoff = array_values((array) config('platform.outbox.backoff', [60]));
        $delay = (int) ($backoff[min($attempts, count($backoff)) - 1] ?? 60);

        $event->forceFill([
            'attempts' => $attempts,
            'last_error' => Str::limit(class_basename($e).': '.$e->getMessage(), 490),
            'available_at' => now()->addSeconds($delay),
        ])->save();

        $context = ['event_id' => $id, 'type' => $type ?? $event->type, 'attempt' => $attempts, 'exception' => get_class($e)];
        Log::error('outbox_listener_failed', $context);
        if ($attempts >= self::maxAttempts()) {
            Log::error('outbox_event_parked', $context);
        }
    }

    private static function maxAttempts(): int
    {
        return max(1, (int) config('platform.outbox.max_attempts', 10));
    }

    /**
     * Whether the caller has a transaction open. Laravel's test transaction
     * (RefreshDatabase) wraps every test and does not count: the transaction
     * manager leaves it out of the transactions after-commit callbacks wait
     * for, and so does this check.
     */
    private static function inTransaction(): bool
    {
        $connection = DB::connection(OutboxEvent::query()->getModel()->getConnectionName());
        if ($connection->transactionLevel() === 0) {
            return false;
        }
        if (! app()->bound('db.transactions')) {
            return true;
        }

        return app('db.transactions')->callbackApplicableTransactions()
            ->contains(fn ($transaction) => $transaction->connection === $connection->getName());
    }
}
