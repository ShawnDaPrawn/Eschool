<?php

declare(strict_types=1);

namespace App\Jobs\Platform;

use App\Models\Platform\NotificationDelivery;
use App\Services\Platform\Notifications\DeliveryGuard;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\TwilioClient;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Sends one SMS or WhatsApp message to one number through Twilio, off the web
 * request (Action Plan 2.7). One job and one delivery key per message and
 * number; each attempt has a notification_deliveries row. A key that already
 * has a provider message id is never sent again, so a retry after Twilio
 * accepted the message cannot repeat it. Network errors and Twilio's 429/5xx
 * answers are retried with backoff; a refusal, or no Twilio configuration, is
 * recorded as failed and not retried.
 *
 * The number and text travel encrypted. Never subject to quiet hours: only
 * urgent and safety types are texted. Queue it through UrgentMessaging.
 */
class SendText implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries;

    public int $timeout = 30;

    /**
     * @param  array{body?: string, content_sid?: string, variables?: array<string, string>}  $content
     * @param  array<string, mixed>  $context  the notice's delivery payload, rechecked by DeliveryGuard
     */
    public function __construct(
        public string $channel,
        public string $type,
        public string $to,
        public array $content,
        public string $deliveryKey,
        public array $context = [],
    ) {
        $this->tries = max(1, (int) config('platform.deliveries.tries', 3));
        $this->onQueue(config('platform.deliveries.queue'));
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return array_values((array) config('platform.deliveries.backoff', [60]));
    }

    public function handle(TwilioClient $twilio): void
    {
        // Twilio accepted an earlier attempt: never send it twice.
        if (NotificationDelivery::query()->where('delivery_key', $this->deliveryKey)->whereNotNull('provider_message_id')->exists()) {
            return;
        }
        $row = DeliveryLog::attempt($this->deliveryKey, $this->attempts());
        if ($row?->school_id) {
            app(CurrentSchool::class)->run($row->school_id, fn () => $this->deliver($twilio, $row));
        } else {
            // A text always belongs to a school; without its row it is not sent.
            DeliveryLog::suppressed($row);
        }
    }

    private function deliver(TwilioClient $twilio, NotificationDelivery $row): void
    {
        if (! app(DeliveryGuard::class)->allows($row, $this->type, $this->channel, $this->to, $this->context)) {
            DeliveryLog::suppressed($row);

            return;
        }
        if (! $twilio->configured($this->channel)) {
            DeliveryLog::failed($row, TwilioClient::PROVIDER, 'Twilio is not configured for '.$this->channel.'.');

            return;
        }
        if ($this->channel === DeliveryLog::WHATSAPP && ($this->content['content_sid'] ?? '') === '') {
            DeliveryLog::failed($row, TwilioClient::PROVIDER, 'No approved WhatsApp template for this notice.');

            return;
        }

        try {
            $result = $twilio->send($this->channel, $this->to, $this->content);
        } catch (RuntimeException $e) {
            DeliveryLog::failed($row, TwilioClient::PROVIDER, $e->getMessage());

            throw $e;
        }

        if ($result['status'] === 'sent') {
            DeliveryLog::sent($row, TwilioClient::PROVIDER, $result['sid'] ?? null);
            DeliveryLog::charged($row, $result['segments'] ?? null, $result['price_cents'] ?? null, $result['currency'] ?? null);

            return;
        }

        DeliveryLog::failed($row, TwilioClient::PROVIDER, $result['error'] ?? $result['status']);
        if ($result['status'] === 'retry') {
            throw new RuntimeException((string) ($result['error'] ?? 'Twilio asked to retry.'));
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
