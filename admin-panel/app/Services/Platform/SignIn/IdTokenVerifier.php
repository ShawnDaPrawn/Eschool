<?php

declare(strict_types=1);

namespace App\Services\Platform\SignIn;

use App\Models\SignIn\SignInProviderSetting;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use stdClass;
use Throwable;

/**
 * Checks a Google or Microsoft ID token (Action Plan 5.9) before anything
 * else reads it:
 *
 * - RS256 only, signed by a key in the provider's JWKS (ProviderKeys);
 * - iss: Google's issuer, or for Microsoft exactly the tenant-specific
 *   issuer of the token's own tid, and that tid one of the school's tenants;
 * - aud: one of the configured client ids (a single audience);
 * - exp, iat (and nbf) with config leeway, and iat no older than
 *   maximum_token_age_seconds;
 * - nonce: its SHA-256 equals the one-use challenge's;
 * - a verified email: Google email_verified, Microsoft email_verified or
 *   xms_edov (the domain owner verified it), in an allowed domain; for Google
 *   with require_hosted_domain the hd claim too; Microsoft guest accounts
 *   (an idp other than the issuer) are refused.
 *
 * Any failure throws ProviderSignInRefused with the reason for the audit log.
 */
final class IdTokenVerifier
{
    private const GUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/D';

    public function __construct(
        private readonly ProviderKeys $keys,
        private readonly ProviderSettings $settings,
    ) {}

    /**
     * @param  list<string>  $audiences
     */
    public function verify(string $provider, string $jwt, string $nonceHash, array $audiences, SignInProviderSetting $setting): ProviderIdentity
    {
        $claims = $this->decode($provider, $jwt);
        $now = now()->getTimestamp();
        $leeway = max(0, (int) config('sign_in_providers.leeway_seconds', 60));

        $aud = $claims->aud ?? null;
        if (is_array($aud) && count($aud) === 1) {
            $aud = $aud[0];
        }
        if (! is_string($aud) || ! in_array($aud, $audiences, true)) {
            throw new ProviderSignInRefused('wrong_audience');
        }
        if (! is_numeric($claims->exp ?? null) || ! is_numeric($claims->iat ?? null)) {
            throw new ProviderSignInRefused('missing_claim');
        }
        if ((int) $claims->iat < $now - (int) config('sign_in_providers.maximum_token_age_seconds', 600) - $leeway) {
            throw new ProviderSignInRefused('too_old');
        }
        $nonce = $claims->nonce ?? null;
        if (! is_string($nonce) || $nonce === '' || ! hash_equals($nonceHash, hash('sha256', $nonce))) {
            throw new ProviderSignInRefused('nonce_mismatch');
        }

        return $provider === ProviderCatalogue::MICROSOFT
            ? $this->microsoft($claims, $setting)
            : $this->google($claims, $setting);
    }

    private function google(stdClass $claims, SignInProviderSetting $setting): ProviderIdentity
    {
        if (! in_array($claims->iss ?? null, (array) config('sign_in_providers.providers.google.issuers', []), true)) {
            throw new ProviderSignInRefused('wrong_issuer');
        }
        $subject = $claims->sub ?? null;
        $email = $claims->email ?? null;
        if (! is_string($subject) || $subject === '' || strlen($subject) > 255 || ! is_string($email) || ! $this->isEmail($email)) {
            throw new ProviderSignInRefused('missing_claim');
        }
        if (! $this->isTrue($claims->email_verified ?? null)) {
            throw new ProviderSignInRefused('email_unverified');
        }
        $identity = new ProviderIdentity(ProviderCatalogue::GOOGLE, '', $subject, strtolower($email));
        if (! $this->settings->allowsDomain($setting, $identity->emailDomain())) {
            throw new ProviderSignInRefused('domain_not_allowed');
        }
        if ($setting->require_hosted_domain) {
            $hd = $claims->hd ?? null;
            if (! is_string($hd) || strtolower($hd) !== $identity->emailDomain() || ! $this->settings->allowsDomain($setting, strtolower($hd))) {
                throw new ProviderSignInRefused('domain_not_allowed');
            }
        }

        return $identity;
    }

    private function microsoft(stdClass $claims, SignInProviderSetting $setting): ProviderIdentity
    {
        $tenant = $claims->tid ?? null;
        if (! is_string($tenant) || preg_match(self::GUID, strtolower($tenant)) !== 1) {
            throw new ProviderSignInRefused('missing_claim');
        }
        $tenant = strtolower($tenant);
        $issuer = str_replace('{tenant}', $tenant, (string) config('sign_in_providers.providers.microsoft.issuer_template'));
        if (($claims->iss ?? null) !== $issuer) {
            throw new ProviderSignInRefused('wrong_issuer');
        }
        if (! $this->settings->allowsTenant($setting, $tenant)) {
            throw new ProviderSignInRefused('tenant_not_allowed');
        }
        if (isset($claims->idp) && $claims->idp !== $issuer) {
            // A guest from another directory: the school's tenant does not own this identity.
            throw new ProviderSignInRefused('guest_account');
        }
        $subject = $claims->oid ?? null;
        $email = $claims->email ?? null;
        if (! is_string($subject) || preg_match(self::GUID, strtolower($subject)) !== 1 || ! is_string($email) || ! $this->isEmail($email)) {
            throw new ProviderSignInRefused('missing_claim');
        }
        if (! $this->isTrue($claims->email_verified ?? null) && ! $this->isTrue($claims->xms_edov ?? null)) {
            throw new ProviderSignInRefused('email_unverified');
        }
        $identity = new ProviderIdentity(ProviderCatalogue::MICROSOFT, $tenant, strtolower($subject), strtolower($email));
        if ($this->settings->domains($setting) !== [] && ! $this->settings->allowsDomain($setting, $identity->emailDomain())) {
            throw new ProviderSignInRefused('domain_not_allowed');
        }

        return $identity;
    }

    private function decode(string $provider, string $jwt): stdClass
    {
        if (strlen($jwt) > 16384 || substr_count($jwt, '.') !== 2) {
            throw new ProviderSignInRefused('invalid_token');
        }
        try {
            $header = JWT::jsonDecode(JWT::urlsafeB64Decode(explode('.', $jwt)[0]));
        } catch (Throwable) {
            throw new ProviderSignInRefused('invalid_token');
        }
        if (! $header instanceof stdClass || ($header->alg ?? null) !== 'RS256' || ! is_string($header->kid ?? null) || $header->kid === '') {
            throw new ProviderSignInRefused('invalid_token');
        }
        $keys = $this->keys->keys($provider, $header->kid);
        if (! isset($keys[$header->kid])) {
            throw new ProviderSignInRefused('bad_signature');
        }

        // php-jwt reads the clock and leeway from statics: set them from this
        // request's clock (Carbon, so tests can move it) and put them back.
        $timestamp = JWT::$timestamp;
        $leeway = JWT::$leeway;
        JWT::$timestamp = now()->getTimestamp();
        JWT::$leeway = max(0, (int) config('sign_in_providers.leeway_seconds', 60));
        try {
            return JWT::decode($jwt, [$header->kid => $keys[$header->kid]]);
        } catch (SignatureInvalidException) {
            throw new ProviderSignInRefused('bad_signature');
        } catch (ExpiredException) {
            throw new ProviderSignInRefused('expired');
        } catch (BeforeValidException) {
            throw new ProviderSignInRefused('not_yet_valid');
        } catch (Throwable) {
            throw new ProviderSignInRefused('invalid_token');
        } finally {
            JWT::$timestamp = $timestamp;
            JWT::$leeway = $leeway;
        }
    }

    private function isTrue(mixed $value): bool
    {
        return $value === true || $value === 'true' || $value === 1 || $value === '1';
    }

    private function isEmail(string $email): bool
    {
        return strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
