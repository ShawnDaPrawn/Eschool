<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Jobs\Platform\SendText;
use App\Models\Platform\MessagingNumber;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Number verification for SMS and WhatsApp (Action Plan 2.7). Before a
 * number is texted, the person it belongs to proves they hold it: they ask
 * for a six-digit code, which goes to the number by SMS through the same
 * Twilio path as every other text, and type it in the app.
 *
 * Codes expire, are kept only as a keyed hash, allow a limited number of
 * wrong tries before a lockout, and are rate-limited per person. A
 * verification belongs to one person, one school and one number (by hash):
 * when the number changes, by anyone, it is no longer verified.
 * engineering/specs/platform/sms-whatsapp.md.
 */
class TextNumbers
{
    /** The delivery type of a verification code (sms.messaging.verification). */
    public const TYPE = 'messaging.verification';

    public function __construct(private readonly UrgentMessaging $messaging) {}

    /** Whether this person has verified this number at the current school. */
    public function verified(User $user, string $number): bool
    {
        return MessagingNumber::query()->where('user_id', $user->id)->where('number_hash', DeliveryLog::hash($number))
            ->whereNotNull('verified_at')->exists();
    }

    /** Whether a code for this number is waiting to be typed (checked again before the code is texted). */
    public function pending(User $user, string $number): bool
    {
        return MessagingNumber::query()->where('user_id', $user->id)->where('number_hash', DeliveryLog::hash($number))
            ->whereNotNull('code_hash')->where('code_expires_at', '>', now())->exists();
    }

    /**
     * What the person sees: whether there is a number, its last three digits
     * (their own number at this school), whether it is verified, and when a
     * code can be asked for or typed.
     *
     * @return array<string, mixed>
     */
    public function status(User $user): array
    {
        $destination = $this->messaging->destination($user);
        $row = MessagingNumber::query()->where('user_id', $user->id)->first();
        $current = $destination !== null && $row !== null && $row->number_hash !== null
            && hash_equals($row->number_hash, DeliveryLog::hash($destination['number']));
        $next = $row?->last_code_sent_at?->copy()->addSeconds($this->setting('resend_seconds', 60));
        if ($row !== null && $row->codes_window_started_at !== null && $row->codes_sent >= $this->setting('codes_per_hour', 3)
            && $row->codes_window_started_at->copy()->addHour()->isFuture()) {
            $next = $row->codes_window_started_at->copy()->addHour();
        }

        return [
            'on_record' => $destination !== null,
            'source' => $destination['source'] ?? null,
            'ending' => $destination === null ? null : substr($destination['number'], -3),
            'verified' => $current && $row->verified_at !== null,
            'code_sent' => $current && $row->code_hash !== null && $row->code_expires_at?->isFuture() === true,
            'code_expires_at' => $current && $row->code_hash !== null ? $row->code_expires_at?->toIso8601String() : null,
            'next_code_at' => $next !== null && $next->isFuture() ? $next->toIso8601String() : null,
            'locked_until' => $row?->locked_until?->isFuture() ? $row->locked_until->toIso8601String() : null,
            'can_verify' => $destination !== null && $this->messaging->anyChannelFor($user),
        ];
    }

    /**
     * Text a new code to the person's number at the current school. Nothing
     * is sent when the number is already verified.
     *
     * @return array<string, mixed>
     */
    public function requestCode(User $user): array
    {
        $destination = $this->messaging->destination($user);
        if ($destination === null) {
            throw ValidationException::withMessages(['number' => __('There is no mobile number for you at this school. Ask the school office to add it.')]);
        }
        if (! $this->messaging->anyChannelFor($user)) {
            throw ValidationException::withMessages(['number' => __('This school does not send SMS or WhatsApp messages to you yet, so there is nothing to verify.')]);
        }
        if (! app(TwilioClient::class)->configured(DeliveryLog::SMS)) {
            throw ValidationException::withMessages(['number' => __('NavigateEd has not finished setting up SMS yet. Try again later.')]);
        }
        $number = $destination['number'];
        $hash = DeliveryLog::hash($number);
        $code = null;
        $row = DB::transaction(function () use ($user, $destination, $hash, &$code): MessagingNumber {
            $row = $this->lock($user, $destination['source'], $hash);
            if ($row->verified_at !== null) {
                return $row;
            }
            $now = CarbonImmutable::now();
            if ($row->locked_until !== null && $row->locked_until->isFuture()) {
                throw ValidationException::withMessages(['code' => __('Too many wrong codes. Try again after :time.', ['time' => $this->clock($row->locked_until)])]);
            }
            $resend = $this->setting('resend_seconds', 60);
            if ($row->last_code_sent_at !== null && $row->last_code_sent_at->copy()->addSeconds($resend)->isFuture()) {
                $this->tooSoon((int) ceil($now->diffInSeconds($row->last_code_sent_at->copy()->addSeconds($resend))));
            }
            if ($row->codes_window_started_at === null || ! $row->codes_window_started_at->copy()->addHour()->isFuture()) {
                $row->forceFill(['codes_sent' => 0, 'codes_window_started_at' => $now]);
            }
            if ($row->codes_sent >= $this->setting('codes_per_hour', 3)) {
                $this->tooSoon((int) ceil($now->diffInSeconds($row->codes_window_started_at->copy()->addHour())));
            }
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $row->forceFill([
                'code_hash' => $this->codeHash($row, $code), 'code_expires_at' => $now->addMinutes($this->setting('code_minutes', 10)),
                'attempts' => 0, 'codes_sent' => $row->codes_sent + 1, 'last_code_sent_at' => $now,
            ])->save();
            Audit::record('messaging.verification_code_sent', $row, [], ['source' => $row->source, 'codes_sent' => $row->codes_sent]);

            return $row;
        });
        if ($code !== null) {
            $this->send($user, $number, $code);
        }

        return $this->status($user);
    }

    /**
     * Check a code the person typed. A wrong code counts towards the lockout;
     * the right one verifies the number.
     *
     * @return array<string, mixed>
     */
    public function confirm(User $user, string $code): array
    {
        if (preg_match('/^\d{6}$/D', $code) !== 1) {
            throw ValidationException::withMessages(['code' => __('Type the six-digit code from the SMS.')]);
        }
        $destination = $this->messaging->destination($user);
        if ($destination === null) {
            throw ValidationException::withMessages(['number' => __('There is no mobile number for you at this school. Ask the school office to add it.')]);
        }
        $hash = DeliveryLog::hash($destination['number']);
        // A wrong try must be counted even though the request fails, so the
        // outcome is decided inside the transaction and reported after it.
        $outcome = DB::transaction(function () use ($user, $destination, $hash, $code): array {
            $row = $this->lock($user, $destination['source'], $hash);
            if ($row->verified_at !== null) {
                return ['verified'];
            }
            if ($row->locked_until !== null && $row->locked_until->isFuture()) {
                return ['locked', $row->locked_until];
            }
            if ($row->code_hash === null) {
                return ['none'];
            }
            if ($row->code_expires_at === null || ! $row->code_expires_at->isFuture()) {
                $row->forceFill(['code_hash' => null, 'code_expires_at' => null])->save();

                return ['expired'];
            }
            if (! hash_equals($row->code_hash, $this->codeHash($row, $code))) {
                $attempts = $row->attempts + 1;
                $max = $this->setting('max_attempts', 5);
                if ($attempts >= $max) {
                    $until = CarbonImmutable::now()->addMinutes($this->setting('lockout_minutes', 60));
                    $row->forceFill(['attempts' => 0, 'code_hash' => null, 'code_expires_at' => null, 'locked_until' => $until])->save();
                    Audit::record('messaging.verification_locked', $row, [], ['source' => $row->source, 'locked_until' => $until->toIso8601String()]);

                    return ['locked', $until];
                }
                $row->forceFill(['attempts' => $attempts])->save();

                return ['wrong', $max - $attempts];
            }
            $row->forceFill(['verified_at' => now(), 'code_hash' => null, 'code_expires_at' => null, 'attempts' => 0, 'locked_until' => null])->save();
            Audit::record('messaging.number_verified', $row, ['verified' => false], ['verified' => true, 'source' => $row->source]);

            return ['verified'];
        });

        return match ($outcome[0]) {
            'verified' => $this->status($user),
            'locked' => throw ValidationException::withMessages(['code' => __('Too many wrong codes. Try again after :time.', ['time' => $this->clock($outcome[1])])]),
            'none' => throw ValidationException::withMessages(['code' => __('Ask for a code first.')]),
            'expired' => throw ValidationException::withMessages(['code' => __('This code has expired. Ask for a new one.')]),
            default => throw ValidationException::withMessages(['code' => trans_choice('That code is not right. You have :count more try.|That code is not right. You have :count more tries.', $outcome[1], ['count' => $outcome[1]])]),
        };
    }

    /**
     * A number changed: the verification of the old one no longer counts.
     * For a parent record, at that school; for an account number, at every
     * school where the person is texted on it.
     */
    public static function numberChanged(int $userId, string $source, ?int $schoolId = null): void
    {
        $reset = ['verified_at' => null, 'code_hash' => null, 'code_expires_at' => null, 'number_hash' => null, 'updated_at' => now()];
        try {
            $current = app(CurrentSchool::class);
            if ($schoolId !== null) {
                $current->run($schoolId, fn () => MessagingNumber::query()->where('user_id', $userId)->where('source', $source)->toBase()->update($reset));
            } else {
                $current->acrossSchools(fn () => MessagingNumber::acrossSchools()->where('user_id', $userId)->where('source', $source)->toBase()->update($reset));
            }
        } catch (Throwable $e) {
            // Before the migration has run there is nothing to reset; the hash check still stops a changed number.
            Log::warning('messaging_number_reset_failed', ['user_id' => $userId, 'source' => $source, 'exception' => get_class($e)]);
        }
    }

    /** The person's row at the current school, locked, about this number (a different number starts afresh). */
    private function lock(User $user, string $source, string $hash): MessagingNumber
    {
        $row = MessagingNumber::query()->where('user_id', $user->id)->lockForUpdate()->first();
        if ($row === null) {
            try {
                DB::transaction(fn () => MessagingNumber::query()->create(['user_id' => $user->id, 'source' => $source, 'number_hash' => $hash]));
            } catch (UniqueConstraintViolationException) {
                // Another request made it first.
            }
            $row = MessagingNumber::query()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
        }
        if ($row->number_hash === null || ! hash_equals($row->number_hash, $hash) || $row->source !== $source) {
            // A different number: what was verified or sent before is about the old one.
            $row->forceFill(['source' => $source, 'number_hash' => $hash, 'verified_at' => null, 'code_hash' => null, 'code_expires_at' => null, 'attempts' => 0])->save();
        }

        return $row;
    }

    private function send(User $user, string $number, string $code): void
    {
        $school = trim((string) app(CurrentSchool::class)->school()?->name) ?: 'your school';
        $minutes = $this->setting('code_minutes', 10);
        $body = __('Your NavigateEd code for :school is :code. It expires in :minutes minutes. Never share it.', ['school' => mb_substr($school, 0, 60), 'code' => $code, 'minutes' => $minutes]);
        $key = DeliveryLog::queued(DeliveryLog::SMS, DeliveryLog::SMS.'.'.self::TYPE, (int) $user->id, $number);
        $job = new SendText(DeliveryLog::SMS, self::TYPE, $number, ['body' => $body], $key);

        try {
            dispatch($job);
        } catch (Throwable $e) {
            if (config('queue.default') !== 'sync') {
                throw $e;
            }
            Log::error('Text notification failed', ['delivery_key' => $key, 'exception' => get_class($e)]);
        }
    }

    private function codeHash(MessagingNumber $row, string $code): string
    {
        return hash_hmac('sha256', $row->id.'|'.$row->number_hash.'|'.$code, (string) config('app.key'));
    }

    private function tooSoon(int $seconds): never
    {
        $seconds = max(1, $seconds);

        throw new ThrottleRequestsException(__('Wait a little before asking for another code.'), null, ['Retry-After' => (string) $seconds]);
    }

    private function clock(\DateTimeInterface $at): string
    {
        $zone = (string) (app(CurrentSchool::class)->school()?->timezone ?: 'Africa/Johannesburg');

        return CarbonImmutable::instance($at)->setTimezone($zone)->format('H:i');
    }

    private function setting(string $key, int $default): int
    {
        return max(1, (int) config('urgent_messaging.verification.'.$key, $default));
    }
}
