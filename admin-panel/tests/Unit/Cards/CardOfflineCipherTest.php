<?php

declare(strict_types=1);

namespace Tests\Unit\Cards;

use App\Services\Cards\Crypto\CardOfflineCipher;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CardOfflineCipherTest extends TestCase
{
    private function open(array $envelope, string $readerSecret, ?array $context = null): string|false
    {
        $shared = sodium_crypto_scalarmult($readerSecret, base64_decode($envelope['ephemeral_public_key'], true));
        $aad = json_encode(['suite' => $envelope['suite']] + ($context ?? $envelope['context']), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $key = hash_hkdf('sha256', $shared, 32, $aad, base64_decode($envelope['salt'], true));

        return openssl_decrypt(base64_decode($envelope['ciphertext'], true), 'aes-256-gcm', $key, OPENSSL_RAW_DATA,
            base64_decode($envelope['nonce'], true), base64_decode($envelope['tag'], true), $aad);
    }

    public function test_snapshot_is_private_to_the_registered_reader_and_bound_to_its_school_and_expiry(): void
    {
        $secret = random_bytes(32);
        $context = ['school_id' => 1, 'device_id' => 'synthetic-reader', 'snapshot_id' => 'synthetic-snapshot', 'issued_at' => 100, 'expires_at' => 160];
        $payload = ['cards' => [['id' => 'synthetic-card', 'status' => 'active', 'portrait' => 'synthetic-private-pixels']]];
        $envelope = (new CardOfflineCipher)->seal($payload, sodium_crypto_scalarmult_base($secret), $context);
        $this->assertSame($payload, json_decode($this->open($envelope, $secret), true, 8, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('synthetic-private-pixels', json_encode($envelope, JSON_THROW_ON_ERROR));
        $this->assertFalse($this->open($envelope, random_bytes(32)));
        foreach (['school_id' => 2, 'device_id' => 'other-reader', 'snapshot_id' => 'other-snapshot', 'expires_at' => 161] as $field => $value) {
            $this->assertFalse($this->open($envelope, $secret, array_replace($context, [$field => $value])));
        }
        $tag = base64_decode($envelope['tag']);
        $tag[0] = chr(ord($tag[0]) ^ 1);
        $envelope['tag'] = base64_encode($tag);
        $this->assertFalse($this->open($envelope, $secret));
    }

    public function test_low_order_or_invalid_reader_keys_are_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CardOfflineCipher)->seal([], str_repeat(chr(0), 32), ['school_id' => 1, 'device_id' => 'reader', 'snapshot_id' => 'snapshot', 'issued_at' => 100, 'expires_at' => 160]);
    }
}
