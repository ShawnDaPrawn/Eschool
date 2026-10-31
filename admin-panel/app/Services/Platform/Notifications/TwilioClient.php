<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Twilio's Messages API (SMS and WhatsApp) through Laravel's HTTP client, for
 * the one NavigateEd account (config/services.php, twilio). Nothing here ever
 * returns, logs or throws the account SID, the auth token, a phone number or
 * a message body: errors are reduced to the HTTP status and Twilio's error
 * code. engineering/specs/platform/sms-whatsapp.md.
 */
class TwilioClient
{
    public const PROVIDER = 'twilio';

    /** Whether the account and a sender for this channel are configured. */
    public function configured(string $channel): bool
    {
        if (! $this->credentials()) {
            return false;
        }
        $config = (array) config('services.twilio');

        return $channel === DeliveryLog::WHATSAPP
            ? filled($config['whatsapp_from'] ?? null) || filled($config['messaging_service_sid'] ?? null)
            : filled($config['messaging_service_sid'] ?? null) || filled($config['sms_from'] ?? null);
    }

    /**
     * Send one message. $content is ['body' => text] for SMS, or
     * ['content_sid' => HX..., 'variables' => ['1' => ...]] for a WhatsApp
     * template.
     *
     * @param  array{body?: string, content_sid?: string, variables?: array<string, string>}  $content
     * @return array{status: 'sent'|'retry'|'refused', sid?: string, segments?: int|null, price_cents?: int|null, currency?: string|null, error?: string}
     */
    public function send(string $channel, string $to, array $content): array
    {
        if (! $this->configured($channel)) {
            return ['status' => 'refused', 'error' => 'Twilio is not configured for '.$channel.'.'];
        }
        $config = (array) config('services.twilio');
        $whatsapp = $channel === DeliveryLog::WHATSAPP;
        $params = ['To' => ($whatsapp ? 'whatsapp:' : '').$to, 'StatusCallback' => $this->statusCallbackUrl()];
        if ($whatsapp && filled($config['whatsapp_from'] ?? null)) {
            $from = (string) $config['whatsapp_from'];
            $params['From'] = str_starts_with($from, 'whatsapp:') ? $from : 'whatsapp:'.$from;
        } elseif (filled($config['messaging_service_sid'] ?? null)) {
            $params['MessagingServiceSid'] = (string) $config['messaging_service_sid'];
        } else {
            $params['From'] = (string) $config['sms_from'];
        }
        if (isset($content['content_sid'])) {
            $params['ContentSid'] = $content['content_sid'];
            $params['ContentVariables'] = (string) json_encode((object) ($content['variables'] ?? []), JSON_UNESCAPED_UNICODE);
        } else {
            $params['Body'] = (string) ($content['body'] ?? '');
        }

        $response = $this->call(fn (PendingRequest $http) => $http->asForm()->post($this->accountUrl('/Messages.json'), $params));
        if ($response->successful() && is_string($response->json('sid'))) {
            return ['status' => 'sent'] + $this->resource($response);
        }

        return ['status' => $response->status() === 429 || $response->serverError() ? 'retry' : 'refused', 'error' => $this->error($response)];
    }

    /**
     * A sent message's current status, segments and price (null when Twilio
     * does not know the message or cannot be asked now).
     *
     * @return array{sid: string, status: string|null, segments: int|null, price_cents: int|null, currency: string|null}|null
     */
    public function fetch(string $sid): ?array
    {
        if (! $this->credentials() || ! preg_match('/^(SM|MM)[0-9a-fA-F]{32}$/', $sid)) {
            return null;
        }
        try {
            $response = $this->call(fn (PendingRequest $http) => $http->get($this->accountUrl('/Messages/'.$sid.'.json')));
        } catch (RuntimeException) {
            return null;
        }

        return $response->successful() ? $this->resource($response) + ['status' => is_string($response->json('status')) ? $response->json('status') : null] : null;
    }

    /**
     * Twilio's X-Twilio-Signature for a form-encoded request: base64 of the
     * HMAC-SHA1, keyed with the auth token, of the full URL followed by each
     * parameter name and value in name order.
     *
     * @param  array<string, mixed>  $params
     */
    public function validSignature(string $url, array $params, string $signature): bool
    {
        $token = (string) config('services.twilio.auth_token');
        if ($token === '' || $signature === '') {
            return false;
        }

        return hash_equals(self::signature($token, $url, $params), $signature);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public static function signature(string $token, string $url, array $params): string
    {
        ksort($params, SORT_STRING);
        $data = $url;
        foreach ($params as $name => $value) {
            $values = is_array($value) ? $value : [$value];
            sort($values, SORT_STRING);
            foreach ($values as $one) {
                $data .= $name.(is_scalar($one) ? (string) $one : '');
            }
        }

        return base64_encode(hash_hmac('sha1', $data, $token, true));
    }

    /** The exact URL Twilio is given, and therefore signs, for status callbacks. */
    public function statusCallbackUrl(): string
    {
        $configured = (string) config('services.twilio.status_callback_url');

        return $configured !== '' ? $configured : rtrim((string) config('app.url'), '/').'/webhook/messaging/twilio';
    }

    public function accountSid(): string
    {
        return (string) config('services.twilio.account_sid');
    }

    /**
     * Twilio's price ("-0.07920", in price_unit) as whole cents, rounded half
     * up, without floating point. Null when there is no price yet.
     */
    public static function priceCents(mixed $price): ?int
    {
        if (! is_string($price) && ! is_int($price)) {
            return null;
        }
        if (! preg_match('/^-?(\d{1,9})(?:\.(\d+))?$/', trim((string) $price), $parts)) {
            return null;
        }
        $fraction = str_pad($parts[2] ?? '', 3, '0');

        return (int) $parts[1] * 100 + (int) substr($fraction, 0, 2) + ((int) $fraction[2] >= 5 ? 1 : 0);
    }

    private function credentials(): bool
    {
        return filled(config('services.twilio.account_sid')) && filled(config('services.twilio.auth_token'));
    }

    private function accountUrl(string $path): string
    {
        return rtrim((string) config('services.twilio.base_url'), '/').'/Accounts/'.rawurlencode($this->accountSid()).$path;
    }

    /**
     * @param  callable(PendingRequest): Response  $request
     */
    private function call(callable $request): Response
    {
        try {
            return $request(Http::withBasicAuth($this->accountSid(), (string) config('services.twilio.auth_token'))
                ->acceptJson()->connectTimeout(5)->timeout(15));
        } catch (ConnectionException) {
            // The exception's message quotes the URL, which names the account.
            throw new RuntimeException('Twilio could not be reached.');
        }
    }

    /**
     * @return array{sid: string, segments: int|null, price_cents: int|null, currency: string|null}
     */
    private function resource(Response $response): array
    {
        $segments = $response->json('num_segments');

        return [
            'sid' => (string) $response->json('sid'),
            'segments' => is_numeric($segments) && (int) $segments > 0 ? (int) $segments : null,
            'price_cents' => self::priceCents($response->json('price')),
            'currency' => is_string($response->json('price_unit')) ? strtoupper(substr($response->json('price_unit'), 0, 3)) : null,
        ];
    }

    /** HTTP status and Twilio's error code only: Twilio's messages quote numbers. */
    private function error(Response $response): string
    {
        $code = $response->json('code');

        return 'Twilio answered HTTP '.$response->status().(is_numeric($code) ? ', error '.(int) $code : '').'.';
    }
}
