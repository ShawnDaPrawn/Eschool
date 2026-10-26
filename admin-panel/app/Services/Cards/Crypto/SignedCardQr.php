<?php

declare(strict_types=1);

namespace App\Services\Cards\Crypto;

use InvalidArgumentException;
use UnexpectedValueException;

/** Versioned, bounded Ed25519 proof; decoding a reference is never authorization. */
class SignedCardQr
{
    public function encode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private function decode(string $text): string
    {
        if ($text === '' || preg_match('/^[A-Za-z0-9_-]+$/D', $text) !== 1 || strlen($text) % 4 === 1) {
            throw new UnexpectedValueException('The card proof could not be verified.');
        }
        $bytes = base64_decode(strtr($text, '-_', '+/').str_repeat('=', (4 - strlen($text) % 4) % 4), true);
        if ($bytes === false || ! hash_equals($this->encode($bytes), $text)) {
            throw new UnexpectedValueException('The card proof could not be verified.');
        }

        return $bytes;
    }

    private function uuid(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D', $value) === 1;
    }

    private function claims(array $value): void
    {
        if (array_keys($value) !== ['v', 's', 'c', 'r', 'k', 'i', 'e', 'n'] || $value['v'] !== 1
            || ! is_int($value['s']) || $value['s'] < 1 || $value['s'] > 9007199254740991
            || ! $this->uuid($value['c']) || ! $this->uuid($value['k'])
            || ! is_int($value['r']) || $value['r'] < 1 || $value['r'] > 9007199254740991
            || ! is_int($value['i']) || ! is_int($value['e']) || $value['i'] < 1
            || $value['e'] - $value['i'] < 10 || $value['e'] - $value['i'] > 30
            || ! is_string($value['n']) || preg_match('/^[a-f0-9]{32}$/D', $value['n']) !== 1) {
            throw new UnexpectedValueException('The card proof could not be verified.');
        }
    }

    public function issue(int $school, string $card, int $revision, string $keyId, string $secretKey, int $issuedAt, int $lifetime = 25): string
    {
        if (strlen($secretKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new InvalidArgumentException('Invalid configured card signing key.');
        }
        $claims = ['v' => 1, 's' => $school, 'c' => $card, 'r' => $revision, 'k' => $keyId, 'i' => $issuedAt, 'e' => $issuedAt + $lifetime, 'n' => bin2hex(random_bytes(16))];
        $this->claims($claims);
        $body = $this->encode(json_encode($claims, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        $signed = 'NEQ1.'.$body;

        return $signed.'.'.$this->encode(sodium_crypto_sign_detached($signed, $secretKey));
    }

    private function parse(string $credential): array
    {
        if (strlen($credential) > 1024) {
            throw new UnexpectedValueException('The card proof could not be verified.');
        }
        $parts = explode('.', $credential);
        if (count($parts) !== 3 || $parts[0] !== 'NEQ1') {
            throw new UnexpectedValueException('The card proof could not be verified.');
        }
        try {
            $claims = json_decode($this->decode($parts[1]), true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new UnexpectedValueException('The card proof could not be verified.');
        }
        if (! is_array($claims)) {
            throw new UnexpectedValueException('The card proof could not be verified.');
        }
        $this->claims($claims);
        $signature = $this->decode($parts[2]);
        if (strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            throw new UnexpectedValueException('The card proof could not be verified.');
        }

        return [$parts, $claims, $signature];
    }

    /** Unverified references are solely for an already school-scoped key/card lookup. */
    public function unverifiedReferences(string $credential): array
    {
        [, $claims] = $this->parse($credential);

        return ['school_id' => $claims['s'], 'card_id' => $claims['c'], 'key_id' => $claims['k']];
    }

    /** Caller must still enforce current key, card revision/status, subject and nonce policy. */
    public function verify(string $credential, string $publicKey, int $school, string $card, string $keyId, int $now): array
    {
        [$parts, $claims, $signature] = $this->parse($credential);
        if (strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES || $claims['s'] !== $school
            || $claims['c'] !== $card || $claims['k'] !== $keyId || $claims['i'] > $now || $claims['e'] <= $now
            || ! sodium_crypto_sign_verify_detached($signature, $parts[0].'.'.$parts[1], $publicKey)) {
            throw new UnexpectedValueException('The card proof could not be verified.');
        }

        return $claims;
    }
}
