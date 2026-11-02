<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\Platform\NotificationDelivery;
use App\Services\Platform\Mail\MailStreams;
use Aws\Sns\Exception\InvalidSnsMessageException;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Amazon SES feedback, as SNS posts it to POST /webhook/mail/ses.
 *
 * A message is accepted only when its SNS signature verifies against the
 * signing certificate (fetched over HTTPS from an sns.<region>.amazonaws.com
 * host, cached for a day) and it comes from a topic listed in
 * services.ses_notifications.topic_arns: anyone can sign messages from their
 * own SNS topic, so the topic check is what makes it ours. Subscription
 * requests are confirmed; Delivery, Bounce and Complaint notices update the
 * notification_deliveries rows of the SES message id they quote, and SES
 * tenant sending status events (EventBridge, through the same topic) are
 * recorded on the school's mail streams (Action Plan 2.3). Each SNS message
 * id is processed once.
 */
class SesNotifications
{
    private const PROVIDER = 'ses';

    private const SNS_HOST = '/^sns\.[a-zA-Z0-9\-]{3,}\.amazonaws\.com(\.cn)?$/';

    /**
     * @return array{0: int, 1: string} HTTP status and a short message
     */
    public function handle(string $body): array
    {
        $allowedTopics = (array) config('services.ses_notifications.topic_arns', []);
        if ($allowedTopics === []) {
            return $this->reject(401, 'No SNS topic configured for SES notifications');
        }

        try {
            $message = Message::fromJsonString($body);
        } catch (Throwable) {
            return $this->reject(400, 'Invalid payload');
        }

        try {
            (new MessageValidator(fn (string $url) => $this->certificate($url)))->validate($message);
        } catch (InvalidSnsMessageException) {
            return $this->reject(401, 'Invalid signature');
        }

        if (! in_array($message['TopicArn'], $allowedTopics, true)) {
            return $this->reject(403, 'Topic not allowed');
        }

        return match ($message['Type']) {
            'SubscriptionConfirmation' => $this->confirmSubscription($message),
            'Notification' => $this->notification($message),
            default => [200, 'Ignored'],
        };
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function confirmSubscription(Message $message): array
    {
        $url = (string) $message['SubscribeURL'];
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https' || ! preg_match(self::SNS_HOST, (string) ($parts['host'] ?? ''))) {
            return $this->reject(400, 'Invalid subscribe URL');
        }

        try {
            Http::connectTimeout(5)->timeout(10)->get($url)->throw();
        } catch (Throwable $e) {
            Log::error('ses_notification', ['type' => 'SubscriptionConfirmation', 'result' => 'confirm_failed', 'exception' => get_class($e)]);

            return [502, 'Could not confirm the subscription'];
        }
        Log::info('ses_notification', ['type' => 'SubscriptionConfirmation', 'result' => 'confirmed']);

        return [200, 'Subscription confirmed'];
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function notification(Message $message): array
    {
        $payload = json_decode((string) $message['Message'], true);
        if (! is_array($payload)) {
            return $this->reject(400, 'Invalid notification');
        }

        // EventBridge events from SES (tenant sending status, Action Plan 2.3)
        // reach the same topic through an EventBridge rule with an SNS target.
        if (($payload['source'] ?? null) === 'aws.ses' && isset($payload['detail-type'])) {
            return $this->eventBridge($message, $payload);
        }

        // Identity notifications say notificationType; configuration set events say eventType.
        $type = (string) ($payload['notificationType'] ?? $payload['eventType'] ?? '');
        $sesMessageId = $payload['mail']['messageId'] ?? null;
        [$status, $summary] = match ($type) {
            'Delivery' => [NotificationDelivery::DELIVERED, null],
            'Bounce' => [NotificationDelivery::BOUNCED, 'Bounce: '.($payload['bounce']['bounceType'] ?? 'Undetermined').'/'.($payload['bounce']['bounceSubType'] ?? 'Undetermined')],
            'Complaint' => [NotificationDelivery::COMPLAINED, 'Complaint: '.($payload['complaint']['complaintFeedbackType'] ?? 'unspecified')],
            // SES refused to send it (for example a virus in an attachment).
            'Reject' => [NotificationDelivery::FAILED, 'Reject: '.($payload['reject']['reason'] ?? 'unspecified')],
            default => [null, null],
        };

        $updated = DB::transaction(function () use ($message, $type, $status, $summary, $sesMessageId) {
            $first = DB::table('webhook_receipts')->insertOrIgnore([
                'provider' => self::PROVIDER,
                'event_id' => (string) $message['MessageId'],
                'event_type' => mb_substr($type, 0, 100),
                'received_at' => now(),
            ]);
            if ($first === 0) {
                return null;
            }
            if ($status === null || ! is_string($sesMessageId) || $sesMessageId === '') {
                return 0;
            }

            return DeliveryLog::feedback($sesMessageId, $status, $summary);
        });

        if ($updated === null) {
            return [200, 'Already processed'];
        }
        Log::info('ses_notification', ['type' => $type, 'result' => $status === null ? 'ignored' : 'recorded', 'deliveries_updated' => $updated]);

        return [200, $status === null ? 'Ignored' : 'Recorded'];
    }

    /**
     * A tenant's sending status changed (detail-types "Sending Status Enabled"
     * and "Sending Status Disabled"; the second also carries REINSTATED). The
     * tenant is the resource ARN arn:aws:ses:<region>:<account>:tenant/<name>/<id>.
     * Other SES EventBridge events are accepted and ignored.
     *
     * @param  array<string, mixed>  $event
     * @return array{0: int, 1: string}
     */
    private function eventBridge(Message $message, array $event): array
    {
        $type = (string) $event['detail-type'];
        $record = (array) ($event['detail']['data']['record'] ?? []);
        $tenant = null;
        foreach ((array) ($event['resources'] ?? []) as $arn) {
            if (is_string($arn) && preg_match('#^arn:aws[a-z-]*:ses:[a-z0-9-]+:\d{12}:tenant/([A-Za-z0-9_-]{1,64})/[A-Za-z0-9_-]+$#', $arn, $m) === 1) {
                $tenant = $m[1];
            }
        }
        $statusEvent = in_array($type, ['Sending Status Enabled', 'Sending Status Disabled'], true) && $tenant !== null && is_string($record['status'] ?? null);
        try {
            $at = isset($event['time']) && is_string($event['time']) ? Carbon::parse($event['time'])->setTimezone((string) config('app.timezone')) : now();
        } catch (Throwable) {
            $at = now();
        }

        $updated = DB::transaction(function () use ($message, $type, $statusEvent, $tenant, $record, $event, $at) {
            $first = DB::table('webhook_receipts')->insertOrIgnore([
                'provider' => self::PROVIDER,
                'event_id' => (string) $message['MessageId'],
                'event_type' => mb_substr($type, 0, 100),
                'received_at' => now(),
            ]);
            if ($first === 0) {
                return null;
            }

            return $statusEvent ? app(MailStreams::class)->tenantStatus((string) $tenant, (string) $record['status'],
                (string) ($event['detail']['data']['origin'] ?? ''), (string) ($record['cause'] ?? ''), $at) : 0;
        });

        if ($updated === null) {
            return [200, 'Already processed'];
        }
        Log::info('ses_notification', ['type' => $type, 'result' => $updated > 0 ? 'recorded' : 'ignored', 'streams_updated' => $updated]);

        return [200, $updated > 0 ? 'Recorded' : 'Ignored'];
    }

    /**
     * The signing certificate. MessageValidator has already checked that the
     * URL is HTTPS on an SNS host and ends in .pem.
     */
    private function certificate(string $url): string|false
    {
        try {
            return Cache::remember('sns-signing-cert:'.sha1($url), now()->addDay(), fn () => Http::connectTimeout(5)->timeout(10)->get($url)->throw()->body());
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function reject(int $status, string $reason): array
    {
        Log::warning('ses_notification_rejected', ['status' => $status, 'reason' => $reason]);

        return [$status, $reason];
    }
}
