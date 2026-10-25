<?php

declare(strict_types=1);

namespace App\Services\Live;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

/** Credential metadata remains inside the encrypted, short-lived server lease. */
class LiveCredentials
{
    private function seal(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    public function current(Request $request, User $actor): array
    {
        // The authenticated request owns the credential; fresh actor copies carry no token object.
        $token = $request->user()?->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            abort_unless($token->id > 0 && $token->can('*') && (int) $token->tokenable_id === (int) $actor->id, 403);
            $credential = ['kind' => 'token', 'id' => (int) $token->id, 'purpose' => $token->name, 'fingerprint' => $this->seal((string) $token->token)];
        } else {
            abort_unless($request->hasSession() && $request->session()->isStarted(), 403);
            $id = $request->session()->getId();
            abort_unless(preg_match('/^[a-zA-Z0-9]{20,100}$/D', $id) === 1 && config('session.driver') !== 'cookie', 403);
            $credential = ['kind' => 'cookie', 'id' => $id, 'guard_key' => Auth::guard('web')->getName(), 'purpose' => (string) $request->session()->get('portal_app', 'panel')];
        }
        $credential['user_id'] = (int) $actor->id;
        $credential['password'] = $this->seal((string) $actor->getAuthPassword());
        abort_unless($this->valid($credential, $actor), 403);

        return $credential;
    }

    public function binding(array $credential, int $school): string
    {
        return $this->seal(json_encode([$school, $credential['user_id'], $credential['kind'], $credential['id'], $credential['purpose'], $credential['password']], JSON_THROW_ON_ERROR));
    }

    public function valid(array $credential, User $actor): bool
    {
        if (($credential['user_id'] ?? null) !== (int) $actor->id || ! is_string($credential['password'] ?? null)
            || ! hash_equals($credential['password'], $this->seal((string) $actor->getAuthPassword()))) {
            return false;
        }
        if (($credential['kind'] ?? null) === 'token') {
            $token = PersonalAccessToken::find($credential['id'] ?? 0);
            $expiration = config('sanctum.expiration');
            $withinLifetime = ! $expiration || (is_numeric($expiration) && (int) $expiration > 0
                && $token?->created_at?->gt(now()->subMinutes((int) $expiration)));

            return $token !== null && $withinLifetime && $token->can('*') && (int) $token->tokenable_id === (int) $actor->id
                && $token->tokenable_type === $actor->getMorphClass() && $token->name === ($credential['purpose'] ?? null)
                && ($token->expires_at === null || $token->expires_at->isFuture())
                && is_string($credential['fingerprint'] ?? null) && hash_equals($credential['fingerprint'], $this->seal((string) $token->token));
        }
        if (($credential['kind'] ?? null) !== 'cookie' || ! is_string($credential['id'] ?? null)
            || preg_match('/^[a-zA-Z0-9]{20,100}$/D', $credential['id']) !== 1 || config('session.driver') === 'cookie') {
            return false;
        }
        try {
            $bytes = app('session')->driver()->getHandler()->read($credential['id']);
            if (! is_string($bytes) || $bytes === '' || strlen($bytes) > 2097152) {
                return false;
            }
            if (config('session.encrypt')) {
                $bytes = Crypt::decrypt($bytes);
            }
            $session = config('session.serialization') === 'json' ? json_decode($bytes, true, 64, JSON_THROW_ON_ERROR) : @unserialize($bytes, ['allowed_classes' => false]);

            return is_array($session) && (int) ($session[$credential['guard_key'] ?? ''] ?? 0) === (int) $actor->id
                && ($session['portal_app'] ?? 'panel') === ($credential['purpose'] ?? null)
                && (! isset($session['password_hash_web']) || (is_string($session['password_hash_web']) && hash_equals((string) $actor->getAuthPassword(), $session['password_hash_web'])));
        } catch (Throwable) {
            return false;
        }
    }
}
