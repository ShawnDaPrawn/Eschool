<?php

declare(strict_types=1);

namespace App\Services\Cards\Crypto;

use InvalidArgumentException;
use UnexpectedValueException;

/** X25519/HKDF-SHA256/AES-256-GCM envelope; the reader private key never leaves it. */
class CardOfflineCipher
{
    public const SUITE = 'X25519-HKDF-SHA256-A256GCM';

    public function seal(array $payload, string $readerPublicKey, array $context): array
    {
        if (strlen($readerPublicKey) !== SODIUM_CRYPTO_SCALARMULT_BYTES || array_keys($context) !== ['school_id', 'device_id', 'snapshot_id', 'issued_at', 'expires_at']) {
            throw new InvalidArgumentException('Invalid offline reader envelope context.');
        }
        $secret = random_bytes(SODIUM_CRYPTO_SCALARMULT_SCALARBYTES);
        $ephemeral = sodium_crypto_scalarmult_base($secret);
        try {
            $shared = sodium_crypto_scalarmult($secret, $readerPublicKey);
        } catch (\SodiumException) {
            throw new InvalidArgumentException('Invalid offline reader public key.');
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
            sodium_memzero($key);
            sodium_memzero($plain);
        }
        if (! is_string($cipher)) {
            throw new UnexpectedValueException('Offline reader encryption is unavailable.');
        }

        return ['suite' => self::SUITE, 'context' => $context, 'ephemeral_public_key' => base64_encode($ephemeral),
            'salt' => base64_encode($salt), 'nonce' => base64_encode($nonce), 'tag' => base64_encode($tag), 'ciphertext' => base64_encode($cipher)];
    }
}
