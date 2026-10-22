<?php

declare(strict_types=1);

namespace App\Jobs\Platform;

use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\FcmClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends one FCM message (to one device token or one topic), off the web
 * request. Each attempt has a notification_deliveries row. Network errors and
 * FCM's 429/5xx answers are retried with backoff; a message FCM refuses, or a
 * school without push configured, is recorded as failed and not retried.
 *
 * Queue it through PushQueue, which builds nothing new: the message is the
 * one notification_helper.php always sent.
 */
class SendPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries;

    public int $timeout = 30;

    /**
     * @param  array{message: array<string, mixed>}  $message
     */
    public function __construct(public array $message, public string $deliveryKey)
    {
        $this->tries = max(1, (int) config('platform.deliveries.tries', 3));
        $this->onQueue(config('platform.deliveries.queue'));
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return array_values((array) config('platform.deliveries.backoff', [60]));
    }

    public function handle(FcmClient $fcm): void
    {
        $row = DeliveryLog::attempt($this->deliveryKey, $this->attempts());

        try {
            $result = $fcm->send($this->message);
        } catch (Throwable $e) {
            DeliveryLog::failed($row, 'fcm', $e);

            throw $e;
        }

        if ($result['status'] === 'sent') {
            DeliveryLog::sent($row, 'fcm', $result['id'] ?? null);

            return;
        }

        DeliveryLog::failed($row, 'fcm', $result['error'] ?? $result['status']);
    }

    /**
     * Called once the last attempt has failed. The worker's generic failed-job
     * line covers the job; this one says which message was given up on.
     */
    public function failed(?Throwable $e): void
    {
        Log::error('notification_delivery_abandoned', [
            'job' => class_basename($this),
            'delivery_key' => $this->deliveryKey,
            'attempts' => $this->tries,
            'exception' => $e ? get_class($e) : null,
        ]);
    }
}
