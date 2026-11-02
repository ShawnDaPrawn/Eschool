<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\Platform\NotificationDelivery;
use Illuminate\Support\Facades\Log;

/**
 * Twilio's status callbacks for SMS and WhatsApp, as posted to
 * POST /webhook/messaging/twilio (Action Plan 2.7).
 *
 * A callback is acted on only when its X-Twilio-Signature verifies with the
 * platform's auth token against the exact callback URL Twilio was given, and
 * it names our account. It then moves the delivery with that message id to
 * sent, delivered, failed or undelivered. An unknown message id changes
 * nothing. Status updates are idempotent, so a repeated callback is harmless.
 */
class TwilioStatus
{
    public function __construct(private readonly TwilioClient $twilio) {}

    /**
     * @param  array<string, mixed>  $params  the form-encoded body
     * @return array{0: int, 1: string} HTTP status and a short message
     */
    public function handle(array $params, string $signature): array
    {
        if (! filled(config('services.twilio.auth_token')) || ! filled(config('services.twilio.account_sid'))) {
            return $this->reject(401, 'Twilio is not configured');
        }
        if (! $this->twilio->validSignature($this->twilio->statusCallbackUrl(), $params, $signature)) {
            return $this->reject(401, 'Invalid signature');
        }
        if (! hash_equals($this->twilio->accountSid(), (string) ($params['AccountSid'] ?? ''))) {
            return $this->reject(403, 'Account not allowed');
        }
        $sid = $params['MessageSid'] ?? $params['SmsSid'] ?? null;
        $reported = $params['MessageStatus'] ?? $params['SmsStatus'] ?? null;
        if (! is_string($sid) || $sid === '' || ! is_string($reported)) {
            return $this->reject(400, 'Invalid callback');
        }

        $status = match ($reported) {
            'sent' => NotificationDelivery::SENT,
            // WhatsApp reports "read" after "delivered".
            'delivered', 'read' => NotificationDelivery::DELIVERED,
            'failed' => NotificationDelivery::FAILED,
            'undelivered' => NotificationDelivery::UNDELIVERED,
            default => null,
        };
        if ($status === null) {
            Log::info('twilio_status', ['status' => $reported, 'result' => 'ignored', 'deliveries_updated' => 0]);

            return [200, 'Ignored'];
        }
        $code = $params['ErrorCode'] ?? null;
        $summary = in_array($status, [NotificationDelivery::FAILED, NotificationDelivery::UNDELIVERED], true)
            ? 'Twilio '.$reported.(is_numeric($code) ? ', error '.(int) $code : '').'.'
            : null;
        $segments = isset($params['NumSegments']) && is_numeric($params['NumSegments']) ? (int) $params['NumSegments'] : null;
        $price = TwilioClient::priceCents($params['Price'] ?? null);
        $currency = is_string($params['PriceUnit'] ?? null) ? $params['PriceUnit'] : null;

        $updated = DeliveryLog::textFeedback($sid, $status, $summary, $segments, $price, $currency);
        Log::info('twilio_status', ['status' => $reported, 'result' => $updated === 0 ? 'unknown_message' : 'recorded', 'deliveries_updated' => $updated]);

        return [200, $updated === 0 ? 'Ignored' : 'Recorded'];
    }

    /**
     * A form-encoded body exactly as sent: names kept as they are (parse_str
     * would rename some) and repeated names collected.
     *
     * @return array<string, string|list<string>>
     */
    public static function parseForm(string $body): array
    {
        $params = [];
        foreach ($body === '' ? [] : explode('&', $body) as $pair) {
            [$name, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $name = urldecode($name);
            $value = urldecode($value);
            if ($name === '') {
                continue;
            }
            if (! array_key_exists($name, $params)) {
                $params[$name] = $value;
            } else {
                $params[$name] = [...(array) $params[$name], $value];
            }
        }

        return $params;
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function reject(int $status, string $reason): array
    {
        Log::warning('twilio_status_rejected', ['status' => $status, 'reason' => $reason]);

        return [$status, $reason];
    }
}
