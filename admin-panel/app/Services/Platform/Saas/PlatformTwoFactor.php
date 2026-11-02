<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\PlatformStaffTwoFactor;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mandatory two-factor sign-in for platform staff (Action Plan SA.2). Every
 * platform staff member sets up an authenticator app (TOTP, see Totp) before
 * any /saas page opens, and passes a challenge with a code from it, or a
 * single-use recovery code, once in each new browser session.
 *
 * The passed state lives in the session, bound to the session id, the
 * account's current password and the current enrolment, so a new session, a
 * password change or a reset all ask again; signing in or out forgets it.
 * The secret is stored encrypted and shown only while it is being set up;
 * recovery codes are stored as keyed hashes and shown once.
 *
 * Enrolment, every challenge passed or failed, recovery code use and resets
 * are platform_two_factor_* rows in the HQ audit log, never holding a code or
 * the secret. engineering/specs/platform/hq-two-factor.md.
 */
class PlatformTwoFactor
{
    public const SESSION_KEY = 'platform_two_factor';

    /**
     * The staff member's two-factor generation when this session signed in.
     * A reset raises the generation on the platform staff identity, so every
     * older session is signed out on its next /saas request, whatever the
     * session driver keeps sessions in.
     */
    public const GENERATION_KEY = 'platform_two_factor_generation';

    /** Where to go once the challenge is passed (a /saas address only). */
    public const INTENDED_KEY = 'platform_two_factor_intended';

    public const RECOVERY_CODES = 10;

    /** Failed codes allowed per staff member and address each minute. */
    public const ATTEMPTS = 5;

    public const ISSUER = 'NavigateEd HQ';

    public function __construct(private readonly CurrentSchool $currentSchool, private readonly PlatformStaffAccess $access) {}

    /** Whether the account has a confirmed authenticator app. */
    public function enrolled(User $user): bool
    {
        return PlatformStaffTwoFactor::query()->where('user_id', $user->id)->whereNotNull('confirmed_at')->exists();
    }

    /** Whether this session has passed the challenge for this account, as it is now. */
    public function passed(?Session $session, User $user): bool
    {
        $marker = $session?->get(self::SESSION_KEY);
        if ($session === null || ! is_array($marker)) {
            return false;
        }
        $proof = $this->proof($user);
        $valid = $this->current($session, $user)
            && ($marker['user'] ?? null) === (int) $user->id
            && is_string($marker['session'] ?? null) && hash_equals($marker['session'], $session->getId())
            && $proof !== null && is_string($marker['proof'] ?? null) && hash_equals($proof, $marker['proof']);
        if (! $valid) {
            $session->forget(self::SESSION_KEY);
        }

        return $valid;
    }

    /**
     * Records that this session passed, bound to its current id. Callers
     * regenerate the session id first (PlatformTwoFactorController).
     */
    public function remember(Session $session, User $user): void
    {
        $proof = $this->proof($user);
        abort_if($proof === null, 409);
        $session->put(self::SESSION_KEY, ['user' => (int) $user->id, 'session' => $session->getId(), 'proof' => $proof]);
    }

    public function forget(Session $session): void
    {
        $session->forget([self::SESSION_KEY, self::INTENDED_KEY]);
    }

    /**
     * Records the staff member's current generation in a session that has
     * just signed in (ForgetPlatformTwoFactor, on Login). Only a sign-in with
     * the password does this; nothing in /saas can raise a session's stamp.
     */
    public function stamp(Session $session, User $user): void
    {
        $session->put(self::GENERATION_KEY, $this->generation($user));
    }

    /**
     * Whether this session signed in after the staff member's last reset. A
     * session from before any reset carries no stamp and counts as
     * generation 0, which is only current while nobody has reset them.
     */
    public function current(?Session $session, User $user): bool
    {
        $stamp = $session?->get(self::GENERATION_KEY, 0);

        return $session !== null && is_int($stamp) && $stamp === $this->generation($user);
    }

    /** The response for a session from before a reset: signed out, back to the sign-in page. */
    public function signOut(Request $request): Response
    {
        auth()->guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        $message = __('Your two-factor sign-in was reset. Sign in again to set it up.');

        return $request->expectsJson()
            ? response()->json(['error' => true, 'message' => $message], 401)
            : redirect()->route('login')->with('error', $message);
    }

    private function generation(User $user): int
    {
        return (int) PlatformStaffIdentity::query()->where('user_id', $user->id)->value('two_factor_generation');
    }

    /**
     * Where a staff member who has not passed is sent: set up the app, or the
     * challenge. A JSON caller gets 403. A page they asked for is kept.
     */
    public function redirect(Request $request, User $user): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => true, 'message' => __('Two-factor sign-in is required for the platform area.')], 403);
        }
        if ($request->isMethod('GET') && $request->hasSession()) {
            $request->session()->put(self::INTENDED_KEY, $request->fullUrl());
        }

        return redirect()->route($this->enrolled($user) ? 'saas.two-factor.challenge' : 'saas.two-factor.enrol');
    }

    /** The /saas page the person asked for before the challenge, or the school list. */
    public function intended(Session $session): string
    {
        $url = $session->pull(self::INTENDED_KEY);
        $base = url('/saas');

        return is_string($url) && ($url === $base || str_starts_with($url, $base.'/') || str_starts_with($url, $base.'?')) ? $url : route('saas.index');
    }

    /**
     * Starts (or continues) setting up the app: the pending secret, its
     * otpauth:// address and the QR code as inline SVG. Shown only to the
     * person setting it up, and only until it is confirmed.
     *
     * @return array{secret: string, uri: string, qr: string}
     */
    public function begin(User $user): array
    {
        $this->ensureCanEnrol($user);
        $row = DB::transaction(function () use ($user): PlatformStaffTwoFactor {
            $row = PlatformStaffTwoFactor::query()->where('user_id', $user->id)->lockForUpdate()->first();
            abort_if($row?->confirmed_at !== null, 409, __('Two-factor sign-in is already set up.'));

            return $row ?? PlatformStaffTwoFactor::query()->create(['user_id' => $user->id, 'secret' => Totp::generateSecret()]);
        });
        $uri = Totp::uri($row->secret, (string) $user->email, self::ISSUER);

        return ['secret' => $row->secret, 'uri' => $uri, 'qr' => $this->qr($uri)];
    }

    /**
     * Confirms the app with the account's current password and a code from
     * the app, and returns the ten recovery codes, in plain text this once;
     * only their keyed hashes are kept. The password is asked for every
     * time, so a session alone (a stolen one, or one left open) can never
     * attach someone else's authenticator to the account.
     *
     * @return list<string>
     */
    public function confirm(User $user, string $password, string $code, string $ip): array
    {
        $this->ensureCanEnrol($user);
        $code = $this->validCode($code);
        $this->throttle($user, $ip);
        $hash = $this->currentSchool->acrossSchools(fn () => User::query()->whereKey($user->id)->value('password'));
        if (! is_string($hash) || ! Hash::check($password, $hash)) {
            RateLimiter::hit($this->limiterKey($user, $ip), 60);
            Audit::record('platform_two_factor_failed', $this->identity($user), [], ['user_id' => (int) $user->id, 'during' => 'enrolment_password']);

            throw ValidationException::withMessages(['current_password' => __('That is not your current password.')]);
        }
        $codes = DB::transaction(function () use ($user, $code): ?array {
            $row = PlatformStaffTwoFactor::query()->where('user_id', $user->id)->lockForUpdate()->first();
            abort_if($row === null, 409, __('Start setting up two-factor sign-in again.'));
            abort_if($row->confirmed_at !== null, 409, __('Two-factor sign-in is already set up.'));
            $step = Totp::verify($row->secret, $code, now()->getTimestamp());
            if ($step === null) {
                return null;
            }
            $codes = $this->newRecoveryCodes();
            $row->forceFill(['confirmed_at' => now(), 'last_used_step' => $step, 'recovery_codes' => array_map(fn (string $c): string => $this->hashRecoveryCode($c), $codes)])->save();
            Audit::record('platform_two_factor_enrolled', $this->identity($user), [], ['user_id' => (int) $user->id, 'recovery_codes' => count($codes)]);

            return $codes;
        });
        if ($codes === null) {
            $this->failed($user, $ip, 'enrolment');
        }
        RateLimiter::clear($this->limiterKey($user, $ip));

        return $codes;
    }

    /**
     * Checks a code from the app (6 digits) or a recovery code. A code works
     * once: its time step, or the recovery code, is used up.
     *
     * @return array{method: string, remaining: int}
     */
    public function challenge(User $user, string $code, string $ip): array
    {
        $code = $this->validCode($code);
        $this->throttle($user, $ip);
        $result = DB::transaction(function () use ($user, $code): ?array {
            $row = PlatformStaffTwoFactor::query()->where('user_id', $user->id)->whereNotNull('confirmed_at')->lockForUpdate()->first();
            abort_if($row === null, 409, __('Set up two-factor sign-in first.'));
            $remaining = count((array) $row->recovery_codes);
            if (preg_match('/^\d{'.Totp::DIGITS.'}$/D', $code) === 1) {
                $step = Totp::verify($row->secret, $code, now()->getTimestamp(), $row->last_used_step);
                if ($step === null) {
                    return null;
                }
                $row->forceFill(['last_used_step' => $step])->save();
                Audit::record('platform_two_factor_passed', $this->identity($user), [], ['user_id' => (int) $user->id, 'method' => 'app']);

                return ['method' => 'app', 'remaining' => $remaining];
            }
            $left = $this->useRecoveryCode((array) $row->recovery_codes, $code);
            if ($left === null) {
                return null;
            }
            $row->forceFill(['recovery_codes' => $left])->save();
            Audit::record('platform_two_factor_recovery_code_used', $this->identity($user), ['recovery_codes' => $remaining], ['user_id' => (int) $user->id, 'method' => 'recovery_code', 'recovery_codes' => count($left)]);

            return ['method' => 'recovery_code', 'remaining' => count($left)];
        });
        if ($result === null) {
            $this->failed($user, $ip, 'challenge');
        }
        RateLimiter::clear($this->limiterKey($user, $ip));

        return $result;
    }

    /**
     * An Owner resets another staff member's two-factor sign-in from HQ, with
     * a reason: their app and recovery codes are removed, every session of
     * theirs is signed out (the generation, whatever the session driver), and
     * they sign in with their password and set it up again. Nobody
     * resets their own here (platform:reset-two-factor is the break-glass).
     */
    public function reset(User $actor, int $userId, string $reason): void
    {
        abort_unless($this->access->can($actor, 'staff.manage'), 403);
        $reason = $this->validReason($reason);
        abort_if((int) $actor->id === $userId, 403, __('You cannot reset your own two-factor sign-in.'));
        $this->currentSchool->acrossSchools(function () use ($actor, $userId, $reason): void {
            abort_unless(PlatformStaffIdentity::query()->where('user_id', $userId)->exists(), 404);
            $this->clear(User::query()->findOrFail($userId), $reason, 'hq', (int) $actor->id);
        });
    }

    /** The operator console's break-glass reset (platform:reset-two-factor), for anyone, an Owner included. */
    public function breakGlass(User $user, string $reason): void
    {
        abort_unless(app()->runningInConsole(), 403);
        $reason = $this->validReason($reason);
        $this->currentSchool->acrossSchools(function () use ($user, $reason): void {
            abort_unless(PlatformStaffIdentity::query()->where('user_id', $user->id)->exists(), 404);
            $this->clear($user, $reason, 'console', null);
        });
    }

    /** @return array<int, bool> user id => set up, for the staff page */
    public function enrolledIds(): array
    {
        return PlatformStaffTwoFactor::query()->whereNotNull('confirmed_at')->pluck('user_id')->mapWithKeys(fn ($id) => [(int) $id => true])->all();
    }

    private function clear(User $user, string $reason, string $via, ?int $actorId): void
    {
        DB::transaction(function () use ($user, $reason, $via, $actorId): void {
            $row = PlatformStaffTwoFactor::query()->where('user_id', $user->id)->lockForUpdate()->first();
            abort_if($row === null, 409, __('This person has not set up two-factor sign-in.'));
            $wasConfirmed = $row->confirmed_at !== null;
            $row->delete();
            // Signs them out everywhere, whatever the session driver: a lost or
            // taken phone, or an open session, may be in someone else's hands.
            // EnsurePlatformStaff signs out any session stamped before this.
            PlatformStaffIdentity::query()->where('user_id', $user->id)->increment('two_factor_generation');
            // Database-stored sessions are also removed at once.
            DB::table('sessions')->where('user_id', $user->id)->delete();
            Audit::record('platform_two_factor_reset', $this->identity($user), ['set_up' => $wasConfirmed], array_filter([
                'user_id' => (int) $user->id, 'via' => $via, 'reason' => $reason, 'changed_by' => $actorId,
            ], fn ($value) => $value !== null));
        });
    }

    /** Only someone whose password is settled sets up the app, so a default password cannot claim it. */
    private function ensureCanEnrol(User $user): void
    {
        abort_unless($this->access->allowed($user), 403);
    }

    /**
     * What a valid passed marker must hold: the account, its current password
     * and the current enrolment, keyed with the app key. Null when not set up.
     */
    private function proof(User $user): ?string
    {
        $row = PlatformStaffTwoFactor::query()->where('user_id', $user->id)->whereNotNull('confirmed_at')->first(['id', 'confirmed_at']);
        if ($row === null) {
            return null;
        }
        $password = $this->currentSchool->acrossSchools(fn () => User::query()->whereKey($user->id)->value('password'));

        // The stored enrolment marker is global; parsing it in a school's
        // current timezone must never change an already passed session proof.
        return hash_hmac('sha256', implode('|', [(int) $user->id, (string) $password, (int) $row->id, (string) $row->getRawOriginal('confirmed_at')]), $this->key('platform-two-factor-session', (string) config('app.key')));
    }

    private function throttle(User $user, string $ip): void
    {
        $key = $this->limiterKey($user, $ip);
        if (RateLimiter::tooManyAttempts($key, self::ATTEMPTS)) {
            throw ValidationException::withMessages(['two_factor_code' => __('Too many codes tried. Wait :seconds seconds and try again.', ['seconds' => RateLimiter::availableIn($key)])])->status(429);
        }
    }

    private function failed(User $user, string $ip, string $during): never
    {
        RateLimiter::hit($this->limiterKey($user, $ip), 60);
        Audit::record('platform_two_factor_failed', $this->identity($user), [], ['user_id' => (int) $user->id, 'during' => $during]);

        throw ValidationException::withMessages(['two_factor_code' => __('That code is not right, or it has already been used. Try the newest code from your app.')]);
    }

    private function limiterKey(User $user, string $ip): string
    {
        return 'platform-two-factor:'.$user->id.'|'.$ip;
    }

    private function validCode(string $code): string
    {
        $code = strtoupper((string) preg_replace('/[\s-]+/', '', $code));
        if (preg_match('/^(\d{'.Totp::DIGITS.'}|[A-Z2-7]{16})$/D', $code) !== 1) {
            throw ValidationException::withMessages(['two_factor_code' => __('Enter the 6-digit code from your app, or a recovery code.')]);
        }

        return $code;
    }

    private function validReason(string $reason): string
    {
        return (string) Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'min:10', 'max:500']])->validate()['reason'];
    }

    /** @return list<string> e.g. "ABCD-EFGH-IJKL-MNOP", 80 random bits each */
    private function newRecoveryCodes(): array
    {
        $codes = [];
        while (count($codes) < self::RECOVERY_CODES) {
            $codes[] = implode('-', str_split(Totp::base32Encode(random_bytes(10)), 4));
        }

        return $codes;
    }

    /**
     * The stored hashes without the one this code matches, or null when it
     * matches none. Compared in constant time against the current app key and
     * any previous key (APP_PREVIOUS_KEYS), so rotating the key keeps them.
     *
     * @param  array<int, mixed>  $hashes
     * @return list<string>|null
     */
    private function useRecoveryCode(array $hashes, string $code): ?array
    {
        $candidates = array_map(fn (string $key): string => $this->hashRecoveryCode($code, $key), $this->appKeys());
        $match = null;
        foreach (array_values($hashes) as $index => $hash) {
            foreach ($candidates as $candidate) {
                if (is_string($hash) && hash_equals($hash, $candidate) && $match === null) {
                    $match = $index;
                }
            }
        }
        if ($match === null) {
            return null;
        }
        $left = array_values(array_filter(array_values($hashes), fn ($hash, $index) => $index !== $match && is_string($hash), ARRAY_FILTER_USE_BOTH));

        return $left;
    }

    /**
     * HMAC-SHA256 with a pepper derived from the app key. Recovery codes are
     * 80 random bits, so a slow password hash would add nothing but ten slow
     * checks per attempt; the key means a copy of the database alone cannot
     * test a guess.
     */
    private function hashRecoveryCode(string $code, ?string $appKey = null): string
    {
        $normal = strtoupper((string) preg_replace('/[^A-Za-z2-7]/', '', $code));

        return hash_hmac('sha256', $normal, $this->key('platform-two-factor-recovery', $appKey ?? (string) config('app.key')));
    }

    /** @return list<string> */
    private function appKeys(): array
    {
        return array_values(array_filter([(string) config('app.key'), ...array_map('strval', (array) config('app.previous_keys', []))]));
    }

    private function key(string $purpose, string $appKey): string
    {
        return hash_hmac('sha256', $purpose, $appKey, true);
    }

    private function identity(User $user): PlatformStaffIdentity
    {
        return PlatformStaffIdentity::query()->where('user_id', $user->id)->firstOrFail();
    }

    private function qr(string $uri): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle(220, 2), new SvgImageBackEnd)))->writeString($uri);

        return trim((string) preg_replace('/^<\?xml[^>]*\?>/', '', $svg));
    }
}
