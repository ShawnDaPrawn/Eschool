<?php

declare(strict_types=1);

namespace App\Jobs\Platform;

use App\Services\Platform\Notifications\DeliveryLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\SentMessage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends one mail through the configured mailer (SES, SMTP, ...), off the web
 * request. Each attempt has a notification_deliveries row; a failed attempt
 * is retried with backoff (config/platform.php, deliveries).
 *
 * Welcome mails carry new logins, so the queued payload is encrypted with the
 * app key. Queue it through MailService, which also records it as queued.
 */
class SendMail implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries;

    public int $timeout = 60;

    public function __construct(public Mailable $mailable, public string $deliveryKey)
    {
        $this->tries = max(1, (int) config('platform.deliveries.tries', 3));
        $this->onQueue(config('platform.deliveries.queue'));
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return array_values((array) config('platform.deliveries.backoff', [60]));
    }

    public function handle(): void
    {
        $row = DeliveryLog::attempt($this->deliveryKey, $this->attempts());
        $mailer = (string) config('mail.default');

        try {
            $sent = Mail::mailer()->send($this->mailable);
        } catch (Throwable $e) {
            DeliveryLog::failed($row, $mailer, $e);

            throw $e;
        }

        DeliveryLog::sent($row, $mailer, self::providerMessageId($sent));
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

    /**
     * The id the provider gives the message: SES's message id (what its
     * bounce and complaint notifications quote), otherwise the Message-ID.
     */
    public static function providerMessageId(?SentMessage $sent): ?string
    {
        if (! $sent) {
            return null;
        }
        $header = $sent->getOriginalMessage()->getHeaders()->get('X-SES-Message-ID');

        return $header ? $header->getBodyAsString() : $sent->getMessageId();
    }
}
