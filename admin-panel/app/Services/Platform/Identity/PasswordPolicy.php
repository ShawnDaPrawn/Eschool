<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Validation\NotPwnedVerifier;
use Illuminate\Validation\Rules\Password;

/**
 * The rule for every password a person chooses: the panel profile and reset
 * pages, the apps' change-password call and the learner set-up page. It is
 * registered once as Password::defaults(), so each endpoint asks for
 * Password::defaults() and never spells the rule out itself.
 *
 * uncompromised() asks the Have I Been Pwned range API whether the password is
 * in a known breach. Only the first five characters of its SHA-1 hash leave the
 * server (k-anonymity). If the API cannot be reached the check passes, so an
 * outage never stops anyone changing a password.
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 10;

    /** Seconds to wait for the breach check; Laravel's default of 30 would hold a worker. */
    public const BREACH_CHECK_TIMEOUT = 5;

    public static function rule(): Password
    {
        return Password::min(self::MIN_LENGTH)->letters()->numbers()->uncompromised();
    }

    public static function register(Application $app): void
    {
        Password::defaults(static fn () => self::rule());

        // The validation provider is deferred, so its binding is extended rather
        // than replaced (a replacement would be overwritten when it loads).
        $app->extend(UncompromisedVerifier::class, static fn ($verifier, $app) => new NotPwnedVerifier(
            $app->make(HttpFactory::class),
            self::BREACH_CHECK_TIMEOUT,
        ));
    }
}
