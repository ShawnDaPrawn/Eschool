<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Throwable;

/**
 * The seed gave learners, parents, teachers and staff their date of birth as
 * password. Every format it has used: date('dmY') and
 * str_replace('-', '', date('d-m-Y')) (both ddmmyyyy, the common case), and
 * str_replace('/', '', <the form's date>) on older edit paths, which left
 * dd-mm-yyyy or, with a dd/mm/yyyy picker, ddmmyyyy again. yyyy-mm-dd and
 * yyyymmdd cover dates stored or typed the other way round.
 */
final class DateOfBirthPasswords
{
    /** Most common first, so a match usually costs one hash check. */
    public const FORMATS = ['dmY', 'd-m-Y', 'd/m/Y', 'Y-m-d', 'Ymd'];

    /**
     * @return list<string>
     */
    public static function candidates(mixed $dob): array
    {
        if ($dob === null || $dob === '') {
            return [];
        }

        try {
            $date = $dob instanceof \DateTimeInterface ? $dob : new \DateTimeImmutable((string) $dob);
        } catch (Throwable) {
            return [];
        }

        return array_values(array_unique(array_map(fn (string $format) => $date->format($format), self::FORMATS)));
    }

    /** Whether the account's password is still its date of birth in any format the seed used. */
    public static function stillInUse(User $user): bool
    {
        $hash = (string) $user->getAuthPassword();
        if ($hash === '') {
            return false;
        }

        foreach (self::candidates($user->dob) as $candidate) {
            if (Hash::check($candidate, $hash)) {
                return true;
            }
        }

        return false;
    }

    /** Whether a typed password is the account's date of birth in any of those formats. */
    public static function isDateOfBirth(User $user, string $password): bool
    {
        return in_array($password, self::candidates($user->dob), true);
    }

    /**
     * At sign-in: whether the account must choose a new password. True when it
     * is flagged, or when the password just typed is the date of birth, which
     * flags it (so learners:flag-dob-passwords is only a backstop).
     */
    public static function changeRequiredAtSignIn(User $user, string $typedPassword): bool
    {
        if ((bool) $user->password_change_required) {
            return true;
        }
        if (! self::isDateOfBirth($user, $typedPassword)) {
            return false;
        }

        User::whereKey($user->getKey())->update(['password_change_required' => true]);
        $user->setAttribute('password_change_required', true)->syncOriginalAttribute('password_change_required');

        return true;
    }
}
