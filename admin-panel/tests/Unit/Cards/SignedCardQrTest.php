<?php

declare(strict_types=1);

namespace Tests\Unit\Cards;

use App\Services\Cards\Crypto\SignedCardQr;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class SignedCardQrTest extends TestCase
{
    private const CARD = '75133b39-0476-4ba0-a627-a152d042687a';

    private const KEY = '0619113d-7aa9-440c-8aa5-b684fbf6dfdc';

    public function test_sodium_ed25519_matches_rfc8032_empty_message_vector(): void
    {
        $pair = sodium_crypto_sign_seed_keypair(hex2bin('9d61b19deffd5a60ba844af492ec2cc44449c5697b326919703bac031cae7f60'));
        $this->assertSame('d75a980182b10ab7d54bfed3c964073a0ee172f3daa62325af021a68f707511a', bin2hex(sodium_crypto_sign_publickey($pair)));
        $this->assertSame('e5564300c360ac729086e2cc806e828a84877f1eb8e5d974d873e065224901555fb8821590a33bacc61e39701cf9b46bd25bf5f0595bbe24655141438e7a100b', bin2hex(sodium_crypto_sign_detached('', sodium_crypto_sign_secretkey($pair))));
    }

    public function test_school_card_key_expiry_and_nonce_are_bound_to_a_short_lived_signature(): void
    {
        $qr = new SignedCardQr;
        $pair = sodium_crypto_sign_keypair();
        $token = $qr->issue(1, self::CARD, 3, self::KEY, sodium_crypto_sign_secretkey($pair), 1000);
        $claims = $qr->verify($token, sodium_crypto_sign_publickey($pair), 1, self::CARD, self::KEY, 1001);
        $this->assertSame(3, $claims['r']);
        $this->assertSame(1025, $claims['e']);
        $this->assertSame(32, strlen($claims['n']));
        $other = $qr->issue(1, self::CARD, 3, self::KEY, sodium_crypto_sign_secretkey($pair), 1000);
        $this->assertNotSame($token, $other);
        foreach ([['school' => 2], ['card' => self::KEY], ['key' => self::CARD], ['now' => 1025], ['now' => 999], ['public' => random_bytes(32)]] as $change) {
            try {
                $qr->verify($token, $change['public'] ?? sodium_crypto_sign_publickey($pair), $change['school'] ?? 1, $change['card'] ?? self::CARD, $change['key'] ?? self::KEY, $change['now'] ?? 1001);
                $this->fail('A substituted or expired card proof was accepted.');
            } catch (UnexpectedValueException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_tampered_payload_and_noncanonical_base64_are_refused(): void
    {
        $qr = new SignedCardQr;
        $pair = sodium_crypto_sign_keypair();
        $token = $qr->issue(1, self::CARD, 1, self::KEY, sodium_crypto_sign_secretkey($pair), 1000);
        $parts = explode('.', $token);
        $claims = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true, 8, JSON_THROW_ON_ERROR);
        $claims['s'] = 2;
        $tampered = 'NEQ1.'.$qr->encode(json_encode($claims, JSON_THROW_ON_ERROR)).'.'.$parts[2];
        try {
            $qr->verify($tampered, sodium_crypto_sign_publickey($pair), 2, self::CARD, self::KEY, 1001);
            $this->fail('The modified signed claims were accepted.');
        } catch (UnexpectedValueException) {
            $this->addToAssertionCount(1);
        }
        $this->expectException(UnexpectedValueException::class);
        $qr->unverifiedReferences($parts[0].'.'.$parts[1].'=.'.$parts[2]);
    }
}
