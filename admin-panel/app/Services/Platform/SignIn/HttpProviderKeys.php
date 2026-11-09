<?php

declare(strict_types=1);

namespace App\Services\Platform\SignIn;

use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The providers' JWKS over HTTPS, cached (Action Plan 5.9).
 *
 * - Bounded: a copy is kept between jwks.minimum_seconds and
 *   jwks.maximum_seconds (Cache-Control max-age within those), a set larger
 *   than jwks.maximum_bytes or with more than jwks.maximum_keys keys is
 *   refused, and only RSA signing keys with a key id are kept.
 * - An unknown key id (the provider rotated) fetches the set again, at most
 *   once per jwks.refetch_cooldown_seconds, so a stream of forged key ids
 *   cannot make this server hammer the provider.
 * - Fail closed: when the set cannot be fetched and no unexpired copy is
 *   held, sign-in is refused ('unavailable'); an expired copy is never used.
 */
final class HttpProviderKeys implements ProviderKeys
{
    public function __construct(private readonly ProviderCatalogue $catalogue) {}

    public function keys(string $provider, ?string $kid): array
    {
        $cacheKey = 'sign-in-providers:jwks:'.$provider;
        $now = now()->getTimestamp();
        $held = Cache::get($cacheKey);
        $fresh = is_array($held) && ($held['expires_at'] ?? 0) > $now && is_array($held['jwks'] ?? null);
        if ($fresh) {
            $keys = $this->parse($held['jwks']);
            if ($kid === null || isset($keys[$kid]) || ! Cache::add($cacheKey.':refetch', 1, (int) config('sign_in_providers.jwks.refetch_cooldown_seconds', 300))) {
                return $keys;
            }
        }

        $fetched = $this->fetch($provider);
        if ($fetched === null) {
            if ($fresh) {
                return $this->parse($held['jwks']);
            }
            throw new ProviderSignInRefused('unavailable');
        }
        Cache::put($cacheKey, ['jwks' => $fetched['jwks'], 'expires_at' => $now + $fetched['ttl']], $fetched['ttl']);
        Cache::add($cacheKey.':refetch', 1, (int) config('sign_in_providers.jwks.refetch_cooldown_seconds', 300));

        return $this->parse($fetched['jwks']);
    }

    /** @return array{jwks: array{keys: list<array<string, mixed>>}, ttl: int}|null */
    private function fetch(string $provider): ?array
    {
        $url = (string) $this->catalogue->setting($provider, 'jwks_url');
        if (! str_starts_with($url, 'https://')) {
            return null;
        }
        try {
            $response = Http::acceptJson()
                ->timeout((int) config('sign_in_providers.jwks.timeout_seconds', 5))
                ->connectTimeout(3)
                ->withOptions(['allow_redirects' => false])
                ->get($url);
        } catch (Throwable $e) {
            Log::warning('sign_in_provider_jwks_unreachable', ['provider' => $provider, 'exception' => $e::class]);

            return null;
        }
        $body = $response->body();
        if (! $response->successful() || strlen($body) > (int) config('sign_in_providers.jwks.maximum_bytes', 65536)) {
            Log::warning('sign_in_provider_jwks_refused', ['provider' => $provider, 'status' => $response->status(), 'bytes' => strlen($body)]);

            return null;
        }
        $decoded = json_decode($body, true);
        $keys = is_array($decoded) && is_array($decoded['keys'] ?? null) ? $decoded['keys'] : null;
        if ($keys === null || $keys === [] || count($keys) > (int) config('sign_in_providers.jwks.maximum_keys', 32)) {
            Log::warning('sign_in_provider_jwks_refused', ['provider' => $provider, 'status' => $response->status(), 'bytes' => strlen($body)]);

            return null;
        }
        $kept = array_values(array_filter($keys, fn ($key) => is_array($key) && ($key['kty'] ?? null) === 'RSA'
            && in_array($key['use'] ?? 'sig', ['sig'], true) && in_array($key['alg'] ?? 'RS256', ['RS256'], true)
            && is_string($key['kid'] ?? null) && $key['kid'] !== '' && is_string($key['n'] ?? null) && is_string($key['e'] ?? null)));
        try {
            if ($kept === [] || JWK::parseKeySet(['keys' => $kept], 'RS256') === []) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }

        return ['jwks' => ['keys' => $kept], 'ttl' => $this->ttl((string) $response->header('Cache-Control'))];
    }

    private function ttl(string $cacheControl): int
    {
        $minimum = (int) config('sign_in_providers.jwks.minimum_seconds', 300);
        $maximum = (int) config('sign_in_providers.jwks.maximum_seconds', 21600);
        $age = preg_match('/(?:^|,)\s*max-age=(\d{1,9})/i', $cacheControl, $match) === 1 ? (int) $match[1] : $minimum;

        return max($minimum, min($maximum, $age));
    }

    /**
     * @param  array{keys: list<array<string, mixed>>}  $jwks
     * @return array<string, Key>
     */
    private function parse(array $jwks): array
    {
        try {
            return JWK::parseKeySet($jwks, 'RS256');
        } catch (Throwable) {
            throw new ProviderSignInRefused('unavailable');
        }
    }
}
