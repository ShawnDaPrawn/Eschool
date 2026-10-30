<?php

declare(strict_types=1);

namespace App\Jobs\Platform;

use App\Models\Platform\NotificationDelivery;
use App\Services\Platform\Notifications\DeliveryGuard;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\FcmClient;
use App\Services\Platform\Notifications\PushDevices;
use App\Services\Platform\Notifications\QuietHours;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
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
class SendPush implements ShouldBeEncrypted, ShouldQueue
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
        if ($row?->school_id) {
            app(CurrentSchool::class)->run($row->school_id, fn () => $this->deliver($fcm, $row));
        } else {
            $this->deliver($fcm, $row);
        }
    }

    private function deliver(FcmClient $fcm, ?NotificationDelivery $row): void
    {
        $type = $this->message['message']['data']['notice_type'] ?? null;
        if ($type !== null && ! app(DeliveryGuard::class)->allows($row, $type, 'push', $this->message['message']['token'] ?? '', $this->message['message']['data'] ?? [])) {
            DeliveryLog::suppressed($row);

            return;
        }
        // Quiet hours (1.5): hold the push until they end, as a fresh job for
        // the same delivery, so the wait never uses up a retry.
        $until = $type === null || $row?->recipient_user_id === null ? null : app(QuietHours::class)->holdUntil((int) $row->recipient_user_id, (string) $type);
        if ($until !== null) {
            self::dispatch($this->message, $this->deliveryKey)->delay($until);
            Log::info('notification_held_for_quiet_hours', ['delivery_key' => $this->deliveryKey, 'user_id' => $row?->recipient_user_id, 'until' => $until->utc()->toIso8601String()]);

            return;
        }

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
        if (str_contains($result['error'] ?? '', 'UNREGISTERED') && $row?->recipient_user_id && isset($this->message['message']['token'])) {
            app(PushDevices::class)->forget((int) $row->recipient_user_id, (string) $this->message['message']['token']);
        }
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
