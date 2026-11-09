<?php

declare(strict_types=1);

namespace App\Services\Platform\SignIn;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The providers' token endpoints (Action Plan 5.9). The client secret is sent
 * only here, from configuration, and no response body is ever logged.
 */
final class HttpProviderCodes implements ProviderCodes
{
    public function __construct(private readonly ProviderCatalogue $catalogue) {}

    public function idToken(string $provider, string $code, string $verifier, string $redirectUri, string $tenant): string
    {
        $url = str_replace('{tenant}', rawurlencode($tenant), (string) $this->catalogue->setting($provider, 'token_url'));
        try {
            $response = Http::asForm()->acceptJson()->timeout(10)->connectTimeout(3)
                ->withOptions(['allow_redirects' => false])
                ->post($url, [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                    'client_id' => $this->catalogue->clientId($provider),
                    'client_secret' => $this->catalogue->clientSecret($provider),
                    'code_verifier' => $verifier,
                ]);
        } catch (ConnectionException) {
            Log::warning('sign_in_provider_token_endpoint_unreachable', ['provider' => $provider]);
            throw new ProviderSignInRefused('unavailable');
        }
        if ($response->serverError()) {
            Log::warning('sign_in_provider_token_endpoint_failed', ['provider' => $provider, 'status' => $response->status()]);
            throw new ProviderSignInRefused('unavailable');
        }
        $token = $response->successful() ? $response->json('id_token') : null;
        if (! is_string($token) || $token === '' || strlen($token) > 16384) {
            throw new ProviderSignInRefused('code_rejected');
        }

        return $token;
    }
}
