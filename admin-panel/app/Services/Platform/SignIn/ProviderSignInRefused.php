<?php

declare(strict_types=1);

namespace App\Services\Platform\SignIn;

use RuntimeException;

/**
 * A Google or Microsoft sign-in or link that is refused (Action Plan 5.9).
 * The reason code goes to the audit log; the person sees a plain message
 * that never says more than they need (never another account's state).
 */
final class ProviderSignInRefused extends RuntimeException
{
    /** The reasons a token itself is refused: the message never says which check failed. */
    private const TOKEN = ['invalid_token', 'bad_signature', 'wrong_audience', 'wrong_issuer', 'expired', 'not_yet_valid', 'too_old',
        'nonce_mismatch', 'nonce_invalid', 'state_invalid', 'code_rejected', 'provider_error', 'missing_claim'];

    private const MESSAGES = [
        'unavailable' => 'Google and Microsoft sign-in is not available right now. Sign in with your password, or try again in a few minutes.',
        'provider_off' => 'Your school has not switched on this way of signing in.',
        'email_unverified' => 'Your school does not allow this sign-in for this account.',
        'domain_not_allowed' => 'Your school does not allow this sign-in for this account.',
        'tenant_not_allowed' => 'Your school does not allow this sign-in for this account.',
        'guest_account' => 'Your school does not allow this sign-in for this account.',
        'not_linked' => 'This account is not linked to NavigateEd at this school yet. Sign in with your password, then link it on your linked accounts page.',
        'password_change_required' => 'Sign in with your password first and choose a new one. Then you can use this sign-in.',
        'already_signed_in' => 'You are already signed in. Sign out first to sign in with another account.',
        'email_mismatch' => 'This account\'s email address is not the one on your NavigateEd account, so it cannot be linked.',
        'subject_taken' => 'This account is already linked to someone else at this school.',
        'already_linked' => 'You have already linked an account of this kind. Remove that link first.',
        'password_wrong' => 'Your password is not right.',
        'locked' => 'Too many wrong passwords for this account. Try again later.',
        'wrong_account' => 'Start linking again from your linked accounts page.',
    ];

    public function __construct(public readonly string $reason)
    {
        parent::__construct('Provider sign-in refused: '.$reason);
    }

    /** What the person is told (an English source string; translate with __()). */
    public function userMessage(): string
    {
        if (in_array($this->reason, self::TOKEN, true)) {
            return 'We could not confirm that sign-in. Please try again.';
        }

        // Inactive accounts, HQ staff, roles and memberships all get the same answer.
        return self::MESSAGES[$this->reason] ?? 'This account cannot sign in this way. Sign in with your password, or ask your school.';
    }

    /** The HTTP status for the API. */
    public function status(): int
    {
        return match (true) {
            $this->reason === 'unavailable' => 503,
            $this->reason === 'password_wrong' => 422,
            in_array($this->reason, ['subject_taken', 'already_linked'], true) => 409,
            in_array($this->reason, self::TOKEN, true) => 401,
            default => 403,
        };
    }
}
