<?php

declare(strict_types=1);

namespace App\Jobs\Platform;

use App\Mail\Platform\ChecksRecipient;
use App\Mail\Platform\NotificationMail;
use App\Mail\Platform\ViewMail;
use App\Models\Platform\NotificationDelivery;
use App\Models\User;
use App\Services\Platform\Mail\MailStreams;
use App\Services\Platform\Mail\SendingDomains;
use App\Services\Platform\Notifications\DeliveryGuard;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\SentMessage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
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

    /** Names the SES configuration set a message is sent through (SendRawEmail and SMTP). */
    public const CONFIGURATION_SET_HEADER = 'X-SES-CONFIGURATION-SET';

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
        if ($row?->school_id) {
            app(CurrentSchool::class)->run($row->school_id, fn () => $this->deliver($row));
        } else {
            $this->deliver($row);
        }
    }

    private function deliver(?NotificationDelivery $row): void
    {
        // Old unbound invitation jobs must not disclose learner details after
        // the upgrade; the school reissues them with recipient authority.
        $checks = $this->mailable instanceof ChecksRecipient;
        if (($checks && ! $this->mailable->mayDeliver($row))
            || (! $checks && $this->mailable instanceof ViewMail && $this->mailable->viewName === 'emails.learner-invitation')) {
            DeliveryLog::suppressed($row);

            return;
        }
        $mailer = (string) config('mail.default');
        if ($this->mailable instanceof NotificationMail) {
            if (! app(DeliveryGuard::class)->allows($row, $this->mailable->notificationType, 'email', $this->mailable->to[0]['address'] ?? '', $this->mailable->deliveryContext)) {
                DeliveryLog::suppressed($row);

                return;
            }
            if (User::acrossSchools()->whereKey($row->recipient_user_id)->value('email_verified_at') === null) {
                $this->mailable->noticeTitle = __('School notification');
                $this->mailable->noticeBody = __('Open NavigateEd to view this notice.');
            }
            // A school notice comes from the school's own verified domain
            // (Action Plan 2.2), decided now and only for the delivery's own
            // school; otherwise the platform From address. Account mail
            // (passwords, invitations) is never a NotificationMail.
            $this->mailable->from = [];
            $from = app(SendingDomains::class)->noticeAddress($row?->school_id === null ? null : (int) $row->school_id);
            if ($from !== null) {
                $this->mailable->from($from, $this->mailable->schoolName !== '' ? $this->mailable->schoolName : null);
            }
        }

        // The SES stream and configuration set (Action Plan 2.3): the
        // delivery row's own school and the mail's stream decide it, never
        // the ambient school. The header works for the SES API and SMTP alike.
        $streams = app(MailStreams::class);
        $stream = MailStreams::streamFor($this->mailable);
        $configurationSet = $streams->configurationSetFor($row?->school_id === null ? null : (int) $row->school_id, $stream);
        DeliveryLog::routed($row, $stream, $configurationSet);
        $this->mailable->withSymfonyMessage(function (Email $message) use ($configurationSet) {
            $message->getHeaders()->remove(self::CONFIGURATION_SET_HEADER);
            if ($configurationSet !== null) {
                $message->getHeaders()->addTextHeader(self::CONFIGURATION_SET_HEADER, $configurationSet);
            }
        });

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
