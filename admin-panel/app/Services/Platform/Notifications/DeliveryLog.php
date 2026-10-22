<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\Platform\NotificationDelivery;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes notification_deliveries: a row when a message is queued, one per
 * further attempt, and the provider's later feedback (delivered, bounced,
 * complained). Every status change also writes one log line,
 * "notification_delivery", with ids and status only, for log-based metrics.
 *
 * Addresses and device tokens are never stored or logged: rows carry the
 * recipient's user id where known and a SHA-256 hash of the address.
 */
class DeliveryLog
{
    public const MAIL = 'mail';

    public const PUSH = 'push';

    /**
     * Record a message as queued and return the key its attempts share.
     */
    public static function queued(string $channel, string $type, ?int $userId, ?string $address): string
    {
        $key = (string) Str::uuid();

        try {
            $row = NotificationDelivery::create([
                // The school it was sent for (none for platform messages).
                'school_id' => app(CurrentSchool::class)->idOrNull(),
                'delivery_key' => $key,
                'type' => Str::limit($type, 100, ''),
                'channel' => $channel,
                'recipient_user_id' => $userId,
                'recipient_hash' => $address === null ? null : self::hash($address),
                'status' => NotificationDelivery::QUEUED,
                'attempt' => 1,
                'queued_at' => now(),
            ]);
            self::metric($row);
        } catch (Throwable $e) {
            // The record must never stop the message itself.
            self::bookkeepingFailed('queued', $key, $e);
        }

        return $key;
    }

    /**
     * The row for this attempt of a queued message: the queued row for the
     * first attempt, a new row (same recipient and type) for each retry.
     */
    public static function attempt(string $deliveryKey, int $attempt): ?NotificationDelivery
    {
        $attempt = max(1, $attempt);

        try {
            $row = NotificationDelivery::where('delivery_key', $deliveryKey)->where('attempt', $attempt)->first();
            if ($row) {
                return $row;
            }

            $first = NotificationDelivery::where('delivery_key', $deliveryKey)->orderBy('attempt')->first();
            if (! $first) {
                return null;
            }

            return NotificationDelivery::create([
                'school_id' => $first->school_id,
                'delivery_key' => $deliveryKey,
                'type' => $first->type,
                'channel' => $first->channel,
                'recipient_user_id' => $first->recipient_user_id,
                'recipient_hash' => $first->recipient_hash,
                'status' => NotificationDelivery::QUEUED,
                'attempt' => $attempt,
                'queued_at' => $first->queued_at,
            ]);
        } catch (Throwable $e) {
            self::bookkeepingFailed('attempt', $deliveryKey, $e);

            return null;
        }
    }

    public static function sent(?NotificationDelivery $row, string $provider, ?string $providerMessageId): void
    {
        if (! $row) {
            return;
        }

        try {
            $row->forceFill([
                'status' => NotificationDelivery::SENT,
                'provider' => Str::limit($provider, 30, ''),
                'provider_message_id' => $providerMessageId === null ? null : Str::limit($providerMessageId, 191, ''),
                'sent_at' => now(),
                'error_summary' => null,
            ])->save();
            self::metric($row);
        } catch (Throwable $e) {
            self::bookkeepingFailed('sent', $row->delivery_key, $e);
        }
    }

    public static function failed(?NotificationDelivery $row, ?string $provider, Throwable|string $error): void
    {
        if (! $row) {
            return;
        }

        try {
            $row->forceFill([
                'status' => NotificationDelivery::FAILED,
                'provider' => $provider === null ? $row->provider : Str::limit($provider, 30, ''),
                'error_summary' => self::summarise($error),
                'failed_at' => now(),
            ])->save();
            self::metric($row);
        } catch (Throwable $e) {
            self::bookkeepingFailed('failed', $row->delivery_key, $e);
        }
    }

    /**
     * Record what the provider reported later about a sent message. Returns
     * the number of rows changed (0 when the message id is unknown).
     */
    public static function feedback(string $providerMessageId, string $status, ?string $summary = null): int
    {
        $rows = NotificationDelivery::where('provider_message_id', $providerMessageId)->get();
        foreach ($rows as $row) {
            // A bounce or complaint is final; a late delivery notice doesn't undo it.
            if ($status === NotificationDelivery::DELIVERED
                && in_array($row->status, [NotificationDelivery::BOUNCED, NotificationDelivery::COMPLAINED], true)) {
                continue;
            }
            $row->forceFill([
                'status' => $status,
                'error_summary' => $summary === null ? $row->error_summary : self::summarise($summary),
                'feedback_at' => now(),
            ])->save();
            self::metric($row);
        }

        return $rows->count();
    }

    /** The stored form of an address, token or topic. */
    public static function hash(string $address): string
    {
        return hash('sha256', Str::lower(trim($address)));
    }

    /**
     * A short error description without addresses: SMTP and FCM errors often
     * quote the recipient.
     */
    public static function summarise(Throwable|string $error): string
    {
        $text = $error instanceof Throwable
            ? class_basename($error).': '.$error->getMessage()
            : $error;
        $text = (string) preg_replace('/[^\s<>"\'(),;:]+@[^\s<>"\'(),;:]+/', '[address]', $text);
        $text = (string) preg_replace('/\s+/', ' ', $text);

        return Str::limit(trim($text), 490);
    }

    private static function bookkeepingFailed(string $step, string $deliveryKey, Throwable $e): void
    {
        Log::error('notification_delivery_record_failed', [
            'step' => $step,
            'delivery_key' => $deliveryKey,
            'exception' => get_class($e),
        ]);
    }

    private static function metric(NotificationDelivery $row): void
    {
        Log::info('notification_delivery', [
            'delivery_id' => $row->id,
            'delivery_key' => $row->delivery_key,
            'channel' => $row->channel,
            'type' => $row->type,
            'status' => $row->status,
            'provider' => $row->provider,
            'attempt' => $row->attempt,
            'user_id' => $row->recipient_user_id,
        ]);
    }
}
