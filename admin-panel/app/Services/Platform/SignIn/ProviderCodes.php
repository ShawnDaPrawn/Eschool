<?php

declare(strict_types=1);

namespace App\Services\Platform\SignIn;

/**
 * Trades the panel's authorization code (with its PKCE verifier) for the
 * provider's ID token (Action Plan 5.9). Bound in the container.
 */
interface ProviderCodes
{
    /** The ID token, or ProviderSignInRefused ('code_rejected' or 'unavailable'). */
    public function idToken(string $provider, string $code, string $verifier, string $redirectUri, string $tenant): string;
}
