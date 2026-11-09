<?php

declare(strict_types=1);

namespace App\Services\Platform\SignIn;

use Firebase\JWT\Key;

/**
 * A provider's ID token signing keys (its JWKS), by key id (Action Plan 5.9).
 * Bound in the container, so tests and other environments can swap it.
 */
interface ProviderKeys
{
    /**
     * The provider's current RS256 keys. When $kid is not among the held keys
     * the set may be fetched again (bounded). Throws ProviderSignInRefused
     * ('unavailable') when no fresh set can be had: never an old one.
     *
     * @return array<string, Key>
     */
    public function keys(string $provider, ?string $kid): array;
}
