<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Sanctum tokens for the apps, named after the app that holds them.
 *
 * The rule:
 * - Signing in to an app revokes the account's earlier tokens for that same
 *   app, so each account is signed in on one device per app. Tokens of the
 *   account's other apps are left alone: a teacher who is also a parent stays
 *   signed in to the teacher app when they sign in to the parent app.
 * - Tokens issued before tokens carried an app name are treated as this app's
 *   when the account can only use this one app; otherwise they are left to
 *   expire (SANCTUM_EXPIRATION, 30 days).
 * - Signing out revokes the account's tokens for the app it signs out of.
 * - Changing or resetting the password revokes every token of the account,
 *   except the one the app used to make the change (that device proved it
 *   knows the new password and stays signed in).
 */
final class AppTokens
{
    public const STUDENT = 'student-app';

    public const PARENT = 'parent-app';

    public const TEACHER = 'teacher-app';

    /** The role each app is for. */
    public const APPS = [
        self::STUDENT => 'Student',
        self::PARENT => 'Parent',
        self::TEACHER => 'Teacher',
    ];

    /** Revokes the account's earlier tokens for this app and issues a new one. */
    public static function issue(User $user, string $app): string
    {
        app(ApprovalService::class)->assertAccess($user);
        self::revokeApp($user, $app);

        return $user->createToken($app)->plainTextToken;
    }

    /** Revokes the account's tokens for one app; returns how many. */
    public static function revokeApp(User $user, string $app): int
    {
        $legacyAreThisApps = ! self::usesAnotherApp($user, $app);

        return $user->tokens()
            ->where(function ($query) use ($app, $legacyAreThisApps) {
                $query->where('name', $app);
                if ($legacyAreThisApps) {
                    $query->orWhereNotIn('name', array_keys(self::APPS));
                }
            })
            ->delete();
    }

    /** Signs the token's holder out of the app the token belongs to. */
    public static function signOut(User $user, ?PersonalAccessToken $token): void
    {
        if ($token === null) {
            return;
        }

        if (array_key_exists($token->name, self::APPS)) {
            self::revokeApp($user, $token->name);
        }
        $token->delete();
    }

    /** Revokes every token of the account, optionally keeping one. */
    public static function revokeAll(User $user, ?PersonalAccessToken $except = null): int
    {
        return $user->tokens()
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->delete();
    }

    private static function usesAnotherApp(User $user, string $app): bool
    {
        $otherRoles = array_values(array_diff_key(self::APPS, [$app => true]));

        return $user->hasAnyRole($otherRoles);
    }
}
