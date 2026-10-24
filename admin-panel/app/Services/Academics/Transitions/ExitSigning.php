<?php

declare(strict_types=1);

namespace App\Services\Academics\Transitions;

use App\Services\Platform\Tenancy\CurrentSchool;
use RuntimeException;

class ExitSigning
{
    public function sign(string $manifest): array
    {
        $configured = (string) config('app.key');
        $key = str_starts_with($configured, 'base64:') ? base64_decode(substr($configured, 7), true) : $configured;
        if (! is_string($key) || strlen($key) < 16) {
            throw new RuntimeException('Export signing is unavailable.');
        }
        $seed = hash_hkdf('sha256', $key, SODIUM_CRYPTO_SIGN_SEEDBYTES, 'navigateed-academic-exit-signature-v1:school/'.app(CurrentSchool::class)->id());
        $pair = sodium_crypto_sign_seed_keypair($seed);
        $secret = sodium_crypto_sign_secretkey($pair);
        $public = sodium_crypto_sign_publickey($pair);
        $signature = sodium_crypto_sign_detached($manifest, $secret);
        sodium_memzero($secret);
        sodium_memzero($seed);
        sodium_memzero($pair);

        return ['algorithm' => 'Ed25519', 'public_key' => base64_encode($public), 'key_id' => hash('sha256', $public), 'signature' => base64_encode($signature)];
    }
}
