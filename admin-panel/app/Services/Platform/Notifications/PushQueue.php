<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Jobs\Platform\SendPush;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queues FCM messages, one SendPush job per device token or topic, so a retry
 * never repeats a message another device already received.
 */
class PushQueue
{
    /**
     * @param  array{message: array<string, mixed>}  $message
     */
    public static function toDevice(string $type, int $userId, string $token, array $message): void
    {
        self::queue(DeliveryLog::queued(DeliveryLog::PUSH, 'push.'.$type, $userId, $token), $message);
    }

    /**
     * @param  array{message: array<string, mixed>}  $message
     */
    public static function toTopic(string $type, string $topic, array $message): void
    {
        self::queue(DeliveryLog::queued(DeliveryLog::PUSH, 'push.'.$type, null, 'topic:'.$topic), $message);
    }

    private static function queue(string $deliveryKey, array $message): void
    {
        try {
            $job = new SendPush($message, $deliveryKey);
            if (config('queue.connections.'.config('queue.default').'.driver') === 'database') {
                // The queue row and recipient claim commit together. A worker
                // cannot see either until the enclosing transaction commits.
                $job->beforeCommit();
            }
            dispatch($job);
        } catch (Throwable $e) {
            if (config('queue.default') !== 'sync') {
                throw $e;
            }
            // Only with the sync queue, where the job runs inside dispatch(). A
            // push never fails the action that triggered it.
            Log::error('Push notification failed', ['delivery_key' => $deliveryKey, 'exception' => get_class($e)]);
        }
    }
}
