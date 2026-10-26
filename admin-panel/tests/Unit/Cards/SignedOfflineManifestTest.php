<?php

declare(strict_types=1);

namespace Tests\Unit\Cards;

use App\Services\Cards\Crypto\SignedOfflineManifest;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class SignedOfflineManifestTest extends TestCase
{
    public function test_manifest_signature_and_canonical_envelope_are_distinct_from_card_authentication(): void
    {
        $pair = sodium_crypto_sign_keypair();
        $reference = '98b7d062-622f-4bee-b838-986979a42d25';
        $value = ['v' => 1, 's' => 7, 'cards' => [['id' => 'synthetic-card', 'r' => 2]]];
        $signer = new SignedOfflineManifest;
        $token = $signer->sign($value, $reference, sodium_crypto_sign_secretkey($pair));
        $this->assertSame($reference, $signer->keyId($token));
        $this->assertSame($value, $signer->verify($token, sodium_crypto_sign_publickey($pair)));
        $parts = explode('.', $token);
        foreach ([str_replace('NEOS1.', 'NEQ1.', $token), $token.'.extra', str_replace($reference, str_repeat('-', 36), $token),
            $parts[0].'.'.$parts[1].'.'.$parts[2].'=.'.$parts[3],
            $parts[0].'.'.$parts[1].'.'.$parts[2].'.'.str_repeat('A', strlen($parts[3]))] as $bad) {
            try {
                $signer->verify($bad, sodium_crypto_sign_publickey($pair));
                $this->fail('Malformed or altered offline signature was accepted.');
            } catch (UnexpectedValueException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->expectException(UnexpectedValueException::class);
        $signer->verify($token, sodium_crypto_sign_publickey(sodium_crypto_sign_keypair()));
    }

    public function test_non_uuid_signing_key_reference_is_refused_before_signing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SignedOfflineManifest)->sign([], str_repeat('-', 36), sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair()));
    }
}
