<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\Platform\SendMail;
use App\Mail\Platform\MessageRecorder;
use App\Mail\Platform\TemplateMail;
use App\Mail\Platform\ViewMail;
use App\Services\Platform\Notifications\DeliveryLog;
use BadMethodCallException;
use Exception;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends the app's mail off the web request: each mail is queued as a
 * SendMail job (retried with backoff) and gets notification_deliveries rows.
 *
 * send() and sendWithFallback() keep their old signatures: the view and data
 * are queued as a ViewMail with what the callback set (addresses, subject,
 * attachments). They return true once the mail is queued and false when it
 * could not be built, logging why as before. A callback that does something
 * that can't be queued is sent at once, the old way.
 */
class MailService
{
    /**
     * Queue a mail built from a view, its data and a callback that addresses it.
     */
    public static function send($view, $data, $callback)
    {
        $data = is_array($data) ? $data : [];

        try {
            $recorder = new MessageRecorder;
            $callback($recorder);
            $mail = new ViewMail((string) $view, $data, $recorder);
        } catch (BadMethodCallException) {
            return self::sendNow($view, $data, $callback);
        } catch (Throwable $e) {
            self::logFailure($view, $data, $e);

            return false;
        }

        return self::queue($mail, null, $view, $data);
    }

    /**
     * Same as send(), and logs the mail's (redacted) data when it fails.
     */
    public static function sendWithFallback($view, $data, $callback)
    {
        $sent = self::send($view, $data, $callback);

        if (! $sent) {
            Log::info('Email logged instead of sent', [
                'view' => $view,
                'data' => self::loggableData($data),
                'timestamp' => now(),
            ]);
        }

        return $sent;
    }

    /**
     * Queue a Mailable. Returns false (and logs) when it can't be built, for
     * example a template or view error, so nothing broken is queued.
     *
     * @param  int|null  $recipientUserId  the recipient's users.id, when known
     */
    public static function queue(Mailable $mail, ?int $recipientUserId = null, $view = null, $data = null): bool
    {
        $view ??= $mail instanceof TemplateMail ? $mail->viewName : class_basename($mail);
        $data ??= $mail instanceof TemplateMail ? $mail->data : [];

        try {
            // Template and view errors surface here, not after three retries.
            $mail->render();
        } catch (Throwable $e) {
            self::logFailure($view, $data, $e);

            return false;
        }

        $address = $mail->to[0]['address'] ?? null;
        $key = DeliveryLog::queued(DeliveryLog::MAIL, self::deliveryType($mail), $recipientUserId, $address);

        if (! is_string($address) || filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            DeliveryLog::failed(DeliveryLog::attempt($key, 1), null, 'No valid recipient address');
            self::logFailure($view, $data, new Exception('No valid recipient address'));

            return false;
        }

        $mail->metadata('delivery_key', $key);

        try {
            SendMail::dispatch($mail, $key);
        } catch (Throwable $e) {
            // Only with the sync queue, where the job runs inside dispatch().
            self::logFailure($view, $data, $e);

            return false;
        }

        return true;
    }

    /**
     * Send at once, on the request, and say whether the mailer accepted it.
     * Only for the mail settings test, whose answer depends on the result.
     */
    public static function sendNow($view, $data, $callback): bool
    {
        $data = is_array($data) ? $data : [];
        $recorder = new MessageRecorder;
        try {
            $callback($recorder);
        } catch (Throwable) {
            // Recording is only for the delivery row; the send below decides.
        }
        $address = collect($recorder->calls)->firstWhere(0, 'to')[1][0] ?? null;
        $key = DeliveryLog::queued(DeliveryLog::MAIL, 'mail.'.$view, null, is_string($address) ? $address : null);
        $row = DeliveryLog::attempt($key, 1);
        $mailer = (string) config('mail.default');

        try {
            $sent = Mail::send($view, $data, $callback);
            DeliveryLog::sent($row, $mailer, SendMail::providerMessageId($sent));

            return true;
        } catch (Exception $e) {
            DeliveryLog::failed($row, $mailer, $e);
            self::logFailure($view, $data, $e);

            return false;
        }
    }

    private static function deliveryType(Mailable $mail): string
    {
        return match (true) {
            $mail instanceof TemplateMail, $mail instanceof ViewMail => $mail->deliveryType(),
            default => 'mail.'.class_basename($mail),
        };
    }

    private static function logFailure($view, $data, Throwable $e): void
    {
        Log::error('Email sending failed: '.$e->getMessage());

        // Log the email content for debugging
        Log::info('Failed email details', [
            'view' => $view,
            'data' => self::loggableData($data),
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * Welcome emails carry the new login's password, both in its own fields and
     * inside the rendered body, so neither is written to the log.
     */
    private static function loggableData($data): array
    {
        if (! is_array($data)) {
            return [];
        }
        if (array_key_exists('body', $data)) {
            $data['body'] = '[redacted]';
        }

        return ResponseService::loggable($data);
    }

    /**
     * Check if email is properly configured
     */
    public static function isEmailConfigured()
    {
        try {
            $mailConfig = config('mail.default');

            if ($mailConfig === 'log') {
                return true; // Log driver is always available
            }

            if ($mailConfig === 'smtp') {
                return ! empty(config('mail.mailers.smtp.username')) &&
                       ! empty(config('mail.mailers.smtp.password'));
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
