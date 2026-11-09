<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Builder;

/** Separate business sign-in eligibility and session proof from school sign-in. */
final class PlatformSignIn
{
    public const SESSION_KEY = 'platform_sign_in_user';

    public static function credentials(array $credentials, bool $business): array
    {
        return $credentials + [fn (Builder $query) => self::constrain($query, $business)];
    }

    public static function constrain(Builder $query, bool $business): void
    {
        $identity = PlatformStaffIdentity::query()->select('user_id');
        if (! $business) {
            // Revoked operators must not fall back to school sign-in either.
            $query->whereNotIn('users.id', $identity);

            return;
        }
        $query->where('users.status', 1)
            ->whereIn('users.id', $identity->where('active', true))
            ->whereIn('users.id', PlatformAdminGrant::query()->select('user_id')
                ->where('active', true)->where('expires_at', '>', now()->utc())
                ->whereIn('role', array_keys((array) config('platform_roles'))));
    }

    public static function isBusinessAccount(User $user): bool
    {
        return PlatformStaffIdentity::query()->where('user_id', $user->id)->exists();
    }

    public static function mark(Session $session, User $user): void
    {
        $session->put(self::SESSION_KEY, (int) $user->id);
    }

    public static function passed(?Session $session, ?User $user): bool
    {
        return $user !== null && $session?->get(self::SESSION_KEY) === (int) $user->id;
    }
}
