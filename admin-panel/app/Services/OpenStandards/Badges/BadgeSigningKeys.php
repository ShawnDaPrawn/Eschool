<?php

declare(strict_types=1);

namespace App\Services\OpenStandards\Badges;

use RuntimeException;

/**
 * The Ed25519 keys that sign Open Badges credentials (Action Plan 5.9). The
 * key material is a 32-byte seed from configuration (OPEN_BADGES_SIGNING_KEY),
 * never stored in the database or the repository. With the 'school' scope each
 * school has its own key, derived from the seed with HKDF; with 'platform'
 * every school's credentials carry one platform key. Retired seeds are kept
 * for verification only, so credentials issued before a rotation still verify.
 */
class BadgeSigningKeys
{
    public const UNAVAILABLE = 'Badge credentials are not available yet. Ask the school to contact NavigateEd.';

    public function configured(): bool
    {
        return $this->seed((string) config('open_standards.open_badges.signing_key')) !== null;
    }

    /**
     * The key a new credential for this school is signed with.
     *
     * @return array{public: string, secret: string, key_id: string, verification_method: string}
     */
    public function signing(int $schoolId): array
    {
        $seed = $this->seed((string) config('open_standards.open_badges.signing_key'));
        if ($seed === null) {
            throw new RuntimeException(self::UNAVAILABLE);
        }

        return $this->pair($seed, $schoolId);
    }

    /**
     * Every public key that may have signed this school's credentials: the
     * current one first, then the retired ones, by verification method.
     *
     * @return array<string, string> verification method => public key
     */
    public function trusted(int $schoolId): array
    {
        $keys = [];
        $seeds = [(string) config('open_standards.open_badges.signing_key'), ...(array) config('open_standards.open_badges.retired_signing_keys', [])];
        foreach ($seeds as $configured) {
            $seed = $this->seed((string) $configured);
            if ($seed === null) {
                continue;
            }
            $pair = $this->pair($seed, $schoolId);
            sodium_memzero($pair['secret']);
            $keys[$pair['verification_method']] = $pair['public'];
        }

        return $keys;
    }

    public function keyId(string $publicKey): string
    {
        return hash('sha256', $publicKey);
    }

    /** @return array{public: string, secret: string, key_id: string, verification_method: string} */
    private function pair(string $seed, int $schoolId): array
    {
        $info = config('open_standards.open_badges.key_scope') === 'platform' ? 'navigateed-open-badges-v1:platform' : 'navigateed-open-badges-v1:school/'.$schoolId;
        $derived = hash_hkdf('sha256', $seed, SODIUM_CRYPTO_SIGN_SEEDBYTES, $info);
        $pair = sodium_crypto_sign_seed_keypair($derived);
        $public = sodium_crypto_sign_publickey($pair);
        $secret = sodium_crypto_sign_secretkey($pair);
        sodium_memzero($derived);
        sodium_memzero($pair);

        return ['public' => $public, 'secret' => $secret, 'key_id' => $this->keyId($public), 'verification_method' => Multibase::didKey($public)];
    }

    private function seed(string $configured): ?string
    {
        $configured = trim($configured);
        if ($configured === '') {
            return null;
        }
        $seed = base64_decode(str_starts_with($configured, 'base64:') ? substr($configured, 7) : $configured, true);

        return is_string($seed) && strlen($seed) === SODIUM_CRYPTO_SIGN_SEEDBYTES ? $seed : null;
    }
}
