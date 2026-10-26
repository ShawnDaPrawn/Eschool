<?php

declare(strict_types=1);

namespace App\Services\Cards\Crypto;

use InvalidArgumentException;
use SodiumException;
use UnexpectedValueException;

/** Purpose-separated device encryption. No plaintext chip key leaves the server. */
class CardEncodingCipher
{
    public const SUITE = 'NEX1-X25519-HKDF-SHA256-A256GCM';

    public function seal(array $payload, string $public, array $context): array
    {
        if (strlen($public) !== 32 || array_keys($context) !== ['school_id', 'device_id', 'plan_id', 'card_id', 'revision', 'issued_at', 'expires_at']) {
            throw new InvalidArgumentException('Invalid encoding envelope context.');
        }
        $secret = random_bytes(32);
        $ephemeral = sodium_crypto_scalarmult_base($secret);
        try {
            $shared = sodium_crypto_scalarmult($secret, $public);
        } catch (SodiumException) {
            throw new InvalidArgumentException('Invalid registered encoder public key.');
        } finally {
            sodium_memzero($secret);
        }
        $aad = json_encode(['suite' => self::SUITE] + $context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $salt = random_bytes(32);
        $nonce = random_bytes(12);
        $key = hash_hkdf('sha256', $shared, 32, $aad, $salt);
        sodium_memzero($shared);
        $plain = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        try {
            $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, $aad, 16);
        } finally {
            sodium_memzero($plain);
            sodium_memzero($key);
        }
        if (! is_string($cipher)) {
            throw new UnexpectedValueException('Encoding encryption is unavailable.');
        }

        return ['suite' => self::SUITE, 'context' => $context, 'ephemeral_public_key' => base64_encode($ephemeral),
            'salt' => base64_encode($salt), 'nonce' => base64_encode($nonce), 'tag' => base64_encode($tag), 'ciphertext' => base64_encode($cipher)];
    }
}
