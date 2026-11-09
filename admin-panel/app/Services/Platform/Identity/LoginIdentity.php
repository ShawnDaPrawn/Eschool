<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

/** Stable rate-limit identity across email, handle, learner number and user-id sign-in. */
final class LoginIdentity
{
    public static function key(mixed $login): string
    {
        $login = is_string($login) ? strtolower(trim($login)) : '';
        $user = $login === '' ? null : app(StudentNumbers::class)->resolveLogin($login);

        return $user === null ? 'unknown:'.hash('sha256', $login) : 'user:'.$user->getKey();
    }

    public static function credentialsKey(array $credentials): string
    {
        $id = $credentials['id'] ?? null;
        if ((is_int($id) || (is_string($id) && ctype_digit($id))) && (int) $id > 0) {
            return 'user:'.(int) $id;
        }

        return self::key($credentials['email'] ?? null);
    }

    public static function failureKey(array $credentials): string
    {
        return 'login-failures:'.self::credentialsKey($credentials);
    }
}
