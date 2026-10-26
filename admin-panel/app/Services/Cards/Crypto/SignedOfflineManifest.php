<?php

declare(strict_types=1);

namespace App\Services\Cards\Crypto;

use InvalidArgumentException;
use UnexpectedValueException;

/** Public metadata signature, distinct from a card proof or a user sign-in. */
class SignedOfflineManifest
{
    private const UUID = '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/D';

    private function encode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private function decode(string $value): string
    {
        if ($value === '' || preg_match('/^[A-Za-z0-9_-]+$/D', $value) !== 1 || strlen($value) % 4 === 1) {
            throw new UnexpectedValueException('Invalid offline manifest.');
        }
        $bytes = base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        if (! is_string($bytes) || $this->encode($bytes) !== $value) {
            throw new UnexpectedValueException('Invalid offline manifest.');
        }

        return $bytes;
    }

    public function sign(array $metadata, string $keyId, string $secret): string
    {
        if (preg_match(self::UUID, $keyId) !== 1 || strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new InvalidArgumentException('Invalid offline manifest signing key.');
        }
        $body = 'NEOS1.'.$keyId.'.'.$this->encode(json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return $body.'.'.$this->encode(sodium_crypto_sign_detached($body, $secret));
    }

    private function parse(string $token): array
    {
        if (strlen($token) > 131072) {
            throw new UnexpectedValueException('Invalid offline manifest.');
        }
        $parts = explode('.', $token);
        if (count($parts) !== 4 || $parts[0] !== 'NEOS1' || preg_match(self::UUID, $parts[1]) !== 1) {
            throw new UnexpectedValueException('Invalid offline manifest.');
        }
        $signature = $this->decode($parts[3]);
        if (strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            throw new UnexpectedValueException('Invalid offline manifest.');
        }

        return [$parts, $signature];
    }

    /** Only a reference for an already-selected school's public-key lookup. */
    public function keyId(string $token): string
    {
        [$parts] = $this->parse($token);

        return $parts[1];
    }

    public function verify(string $token, string $publicKey): array
    {
        [$parts, $signature] = $this->parse($token);
        if (strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES || ! sodium_crypto_sign_verify_detached($signature, implode('.', array_slice($parts, 0, 3)), $publicKey)) {
            throw new UnexpectedValueException('Invalid offline manifest.');
        }
        try {
            $value = json_decode($this->decode($parts[2]), true, 12, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new UnexpectedValueException('Invalid offline manifest.');
        }
        if (! is_array($value) || array_is_list($value)) {
            throw new UnexpectedValueException('Invalid offline manifest.');
        }

        return $value;
    }
}
