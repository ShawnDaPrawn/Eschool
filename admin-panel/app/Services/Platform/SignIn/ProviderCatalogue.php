<?php

declare(strict_types=1);

namespace App\Services\Platform\SignIn;

/**
 * The sign-in providers this server knows (config/sign_in_providers.php)
 * and whether each is set up here: without a client id a provider is never
 * offered, whatever a school chooses (Action Plan 5.9).
 */
final class ProviderCatalogue
{
    public const GOOGLE = 'google';

    public const MICROSOFT = 'microsoft';

    public const ALL = [self::GOOGLE, self::MICROSOFT];

    public function exists(string $provider): bool
    {
        return in_array($provider, self::ALL, true);
    }

    /** Whether this server has the provider's web client (id and secret). */
    public function configured(string $provider): bool
    {
        return $this->exists($provider) && $this->clientId($provider) !== '' && (string) config("sign_in_providers.providers.$provider.client_secret") !== '';
    }

    public function label(string $provider): string
    {
        return (string) config("sign_in_providers.providers.$provider.label", ucfirst($provider));
    }

    public function clientId(string $provider): string
    {
        return trim((string) config("sign_in_providers.providers.$provider.client_id"));
    }

    public function clientSecret(string $provider): string
    {
        return (string) config("sign_in_providers.providers.$provider.client_secret");
    }

    /**
     * The client ids an ID token may be addressed to: the web client, and for
     * the apps also their own Android and iOS clients.
     *
     * @return list<string>
     */
    public function audiences(string $provider, bool $apps): array
    {
        $ids = [$this->clientId($provider)];
        if ($apps) {
            $ids = [...$ids, ...array_map('strval', (array) config("sign_in_providers.providers.$provider.app_client_ids", []))];
        }

        return array_values(array_unique(array_filter(array_map('trim', $ids), fn (string $id) => $id !== '')));
    }

    public function setting(string $provider, string $key): mixed
    {
        return config("sign_in_providers.providers.$provider.$key");
    }
}
