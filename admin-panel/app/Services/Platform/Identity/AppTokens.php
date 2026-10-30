<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Models\User;
use App\Services\Academics\Transitions\LearnerAcademicAccess;
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

    public const FACILITATOR = 'facilitator-app';

    public const VENDOR = 'vendor-app';

    /** The role each app is for. */
    public const APPS = [
        self::STUDENT => 'Student',
        self::PARENT => 'Parent',
        self::TEACHER => 'Teacher',
        self::FACILITATOR => 'Facilitator',
        self::VENDOR => 'Vendor',
    ];

    /** Signed-in devices kept per app and account; the oldest beyond this are signed out. */
    public const MAX_DEVICES_PER_APP = 5;

    /**
     * Issues a token for one more device of this app. The account stays signed
     * in on its other devices (a parent's phone and tablet), up to
     * MAX_DEVICES_PER_APP; the oldest beyond that are revoked. Unnamed tokens
     * from before app names are still removed when they can only be this app's.
     * The facilitator and vendor profiles keep one session that each sign-in
     * replaces.
     */
    public static function issue(User $user, string $app): string
    {
        app(ApprovalService::class)->assertAccess($user);
        if ($app === self::STUDENT) {
            app(LearnerAcademicAccess::class)->assertUser($user);
        }
        if (in_array($app, [self::FACILITATOR, self::VENDOR], true)) {
            // Dedicated profiles (counselling, counter devices) keep one session:
            // a new sign-in rotates it, as before.
            self::revokeApp($user, $app);

            return $user->createToken($app)->plainTextToken;
        }
        self::revokeLegacy($user, $app);
        $keep = $user->tokens()->where('name', $app)->orderByDesc('id')->limit(self::MAX_DEVICES_PER_APP - 1)->pluck('id');
        $user->tokens()->where('name', $app)->whereNotIn('id', $keep)->delete();

        return $user->createToken($app)->plainTextToken;
    }

    /** Revokes the account's tokens for one app; returns how many. */
    public static function revokeApp(User $user, string $app): int
    {
        // This new profile has no legacy tokens; never revoke another app's
        // pre-profile tokens, and allow its logout after school-role revocation.
        if (in_array($app, [self::FACILITATOR, self::VENDOR], true)) {
            return $user->tokens()->where('name', $app)->delete();
        }
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

    /** Signs this device out: the account's other devices stay signed in. */
    public static function signOut(User $user, ?PersonalAccessToken $token): void
    {
        if ($token === null) {
            return;
        }

        if (! in_array($token->name, [self::FACILITATOR, self::VENDOR], true) && array_key_exists($token->name, self::APPS)) {
            self::revokeLegacy($user, $token->name);
        }
        $token->delete();
    }

    /** Removes unnamed tokens from before app names when they can only be this app's. */
    private static function revokeLegacy(User $user, string $app): void
    {
        if (in_array($app, [self::FACILITATOR, self::VENDOR], true) || self::usesAnotherApp($user, $app)) {
            return;
        }
        $user->tokens()->whereNotIn('name', array_keys(self::APPS))->delete();
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
