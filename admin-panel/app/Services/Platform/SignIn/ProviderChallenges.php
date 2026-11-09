<?php

declare(strict_types=1);

namespace App\Services\Platform\SignIn;

use App\Models\SignIn\SignInProviderChallenge;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * One-use state, nonce and PKCE verifier for each sign-in or link attempt
 * (Action Plan 5.9), in the current school.
 *
 * Only SHA-256 hashes of the state and nonce are stored, and the verifier is
 * encrypted. A challenge is consumed by an atomic update, so a replayed
 * callback, code or nonce (or two at once) finds it already used. Rows older
 * than a day are deleted as new ones are made.
 */
final class ProviderChallenges
{
    /**
     * @return array{challenge: SignInProviderChallenge, nonce: string, state: ?string, verifier: ?string}
     */
    public function start(string $provider, string $purpose, string $channel, ?User $user = null, ?string $app = null): array
    {
        $this->prune();
        $nonce = $this->random();
        $state = $channel === SignInProviderChallenge::WEB ? $this->random() : null;
        $verifier = $channel === SignInProviderChallenge::WEB ? $this->random(48) : null;
        $challenge = SignInProviderChallenge::query()->create([
            'provider' => $provider,
            'purpose' => $purpose,
            'channel' => $channel,
            'user_id' => $user?->id,
            'nonce_hash' => hash('sha256', $nonce),
            'state_hash' => $state === null ? null : hash('sha256', $state),
            'code_verifier' => $verifier,
            'app' => $app,
            'expires_at' => now()->addMinutes(max(1, (int) config('sign_in_providers.challenge_minutes', 10))),
        ]);

        return ['challenge' => $challenge, 'nonce' => $nonce, 'state' => $state, 'verifier' => $verifier];
    }

    /** The panel's challenge for this state, marked used; null when unknown, used or expired. */
    public function consumeState(string $provider, string $state): ?SignInProviderChallenge
    {
        if ($state === '' || strlen($state) > 128) {
            return null;
        }
        $challenge = SignInProviderChallenge::query()->where('state_hash', hash('sha256', $state))
            ->where('provider', $provider)->where('channel', SignInProviderChallenge::WEB)->first();

        return $this->consume($challenge);
    }

    /** An app's challenge for this nonce and purpose, marked used; null when unknown, used, expired or another account's. */
    public function consumeNonce(string $provider, string $nonce, string $purpose, ?User $user = null): ?SignInProviderChallenge
    {
        if ($nonce === '' || strlen($nonce) > 128) {
            return null;
        }
        $challenge = SignInProviderChallenge::query()->where('nonce_hash', hash('sha256', $nonce))
            ->where('provider', $provider)->where('channel', SignInProviderChallenge::APP)->where('purpose', $purpose)
            ->when($user !== null, fn ($q) => $q->where('user_id', $user->id), fn ($q) => $q->whereNull('user_id'))
            ->first();

        return $this->consume($challenge);
    }

    private function consume(?SignInProviderChallenge $challenge): ?SignInProviderChallenge
    {
        if ($challenge === null) {
            return null;
        }
        $used = SignInProviderChallenge::query()->whereKey($challenge->getKey())->whereNull('consumed_at')
            ->where('expires_at', '>', now())->update(['consumed_at' => now(), 'updated_at' => now()]);

        return $used === 1 ? $challenge->refresh() : null;
    }

    private function prune(): void
    {
        SignInProviderChallenge::query()->where('expires_at', '<', Carbon::now()->subDay())->limit(200)->delete();
    }

    /** URL-safe random text (no padding). */
    private function random(int $bytes = 32): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }
}
