<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\Platform\UserDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The push destinations of one human: every device they are signed in on,
 * not only the last one. Specification: engineering/specs/platform/push-devices.md.
 *
 * A device is active while it is not revoked and, when it was registered by an
 * app sign-in, while that sign-in token still exists. Ending a sign-in (sign
 * out, password change, account revocation) therefore ends its notifications
 * without any separate bookkeeping. users.fcm_id / device_type remain a
 * compatibility projection of the latest registration and count as a device.
 */
class PushDevices
{
    public const PLATFORMS = ['android', 'ios'];

    /** Registrations kept per account; the least recently seen beyond this are revoked. */
    public const MAX_ACTIVE = 10;

    /**
     * Records that this account receives notifications on this device. The same
     * token moves to this account if another account held it (a shared phone),
     * so a device never receives two people's notifications.
     */
    public function register(User $user, string $token, string $platform, ?string $app = null, ?int $accessTokenId = null): ?UserDevice
    {
        $token = trim($token);
        if ($token === '' || mb_strlen($token) > 1024 || ! in_array($platform, self::PLATFORMS, true)) {
            return null;
        }
        $hash = hash('sha256', $token);

        return DB::transaction(function () use ($user, $token, $platform, $app, $accessTokenId, $hash) {
            $device = UserDevice::query()->where('token_hash', $hash)->lockForUpdate()->first() ?? new UserDevice(['token_hash' => $hash]);
            $previousOwner = $device->exists ? (int) $device->user_id : null;
            $device->forceFill([
                'user_id' => $user->id, 'token' => $token, 'platform' => $platform, 'app' => $app,
                'personal_access_token_id' => $accessTokenId, 'last_seen_at' => now(), 'revoked_at' => null,
            ])->save();

            if ($previousOwner !== null && $previousOwner !== (int) $user->id) {
                // The previous account's projection must not keep pointing at a device it left.
                User::acrossSchools()->whereKey($previousOwner)->where('fcm_id', $token)->update(['fcm_id' => null]);
            }
            User::acrossSchools()->whereKey($user->id)->update(['fcm_id' => $token, 'device_type' => $platform]);
            $user->forceFill(['fcm_id' => $token, 'device_type' => $platform])->syncOriginalAttributes(['fcm_id', 'device_type']);

            $this->prune((int) $user->id);

            return $device;
        });
    }

    /**
     * Every active destination of the account, newest first, de-duplicated.
     *
     * @return list<array{token: string, platform: string}>
     */
    public function destinations(User $user): array
    {
        $found = [];
        foreach ($this->activeQuery((int) $user->id)->orderByDesc('last_seen_at')->orderByDesc('id')->get() as $device) {
            $found[$device->token_hash] = ['token' => (string) $device->token, 'platform' => (string) $device->platform];
        }
        $legacy = is_string($user->fcm_id) ? trim($user->fcm_id) : '';
        if ($legacy !== '' && in_array($user->device_type, self::PLATFORMS, true)) {
            $hash = hash('sha256', $legacy);
            // The legacy value counts only for a token with no registration at
            // all. A registered token is decided by its registration: revoked,
            // signed out, or another account's device all mean "do not send".
            if (! isset($found[$hash]) && ! UserDevice::query()->where('token_hash', $hash)->exists()) {
                $found[$hash] = ['token' => $legacy, 'platform' => (string) $user->device_type];
            }
        }

        return array_values($found);
    }

    /** Whether a queued message's token is still one of the account's destinations. */
    public function isCurrent(User $user, string $token): bool
    {
        if ($token === '') {
            return false;
        }
        foreach ($this->destinations($user) as $destination) {
            if (hash_equals($destination['token'], $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ends the notifications of the device bound to one sign-in token.
     *
     * @return int how many devices were revoked
     */
    public function revokeForAccessToken(int $accessTokenId): int
    {
        $devices = UserDevice::query()->where('personal_access_token_id', $accessTokenId)->whereNull('revoked_at')->get();
        foreach ($devices as $device) {
            $this->revoke($device);
        }

        return $devices->count();
    }

    /**
     * Signing out of a sign-in from before device registrations existed: that
     * sign-in has no bound device, and the account then had one device per app,
     * the one in users.fcm_id. End that device as sign-out always did.
     */
    public function revokeLegacy(User $user): void
    {
        $legacy = is_string($user->fcm_id) ? trim($user->fcm_id) : '';
        if ($legacy !== '') {
            UserDevice::query()->where('user_id', $user->id)->where('token_hash', hash('sha256', $legacy))
                ->whereNull('personal_access_token_id')->whereNull('revoked_at')->update(['revoked_at' => now()]);
        }
        User::acrossSchools()->whereKey($user->id)->update(['fcm_id' => '']);
        $user->forceFill(['fcm_id' => ''])->syncOriginalAttributes(['fcm_id']);
    }

    /** FCM says the token no longer exists: forget it for this account. */
    public function forget(int $userId, string $token): void
    {
        if ($token === '') {
            return;
        }
        $device = UserDevice::query()->where('user_id', $userId)->where('token_hash', hash('sha256', $token))->first();
        if ($device !== null) {
            $this->revoke($device);
        }
        // Do not clear a newer token installed while the message waited.
        User::acrossSchools()->whereKey($userId)->where('fcm_id', $token)->update(['fcm_id' => null]);
    }

    private function revoke(UserDevice $device): void
    {
        $token = (string) $device->token;
        $device->forceFill(['revoked_at' => now()])->save();
        User::acrossSchools()->whereKey($device->user_id)->where('fcm_id', $token)->update(['fcm_id' => null]);
    }

    private function prune(int $userId): void
    {
        $keep = $this->activeQuery($userId)->orderByDesc('last_seen_at')->orderByDesc('id')->limit(self::MAX_ACTIVE)->pluck('id');
        $this->activeQuery($userId)->whereNotIn('id', $keep)->update(['revoked_at' => now()]);
    }

    /** @return Builder<UserDevice> */
    private function activeQuery(int $userId): Builder
    {
        return UserDevice::query()->where('user_id', $userId)->whereNull('revoked_at')->whereIn('platform', self::PLATFORMS)
            ->where(function ($query) {
                $query->whereNull('personal_access_token_id')
                    ->orWhereExists(fn ($tokens) => $tokens->selectRaw('1')->from('personal_access_tokens')
                        ->whereColumn('personal_access_tokens.id', 'user_devices.personal_access_token_id'));
            });
    }
}
