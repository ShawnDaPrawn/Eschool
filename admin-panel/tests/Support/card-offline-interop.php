<?php

declare(strict_types=1);

// Pure synthetic interoperability fixture: no database, network or fixed secret.
require __DIR__.'/../../vendor/autoload.php';

use App\Services\Cards\Crypto\CardOfflineCipher;
use App\Services\Cards\Crypto\SignedCardQr;
use App\Services\Cards\Crypto\SignedOfflineManifest;

$input = json_decode(stream_get_contents(STDIN), true, 8, JSON_THROW_ON_ERROR);
$keyId = '8b2fd275-4018-41e5-91a0-96fa35beca26';
$card = '03329821-2574-4cf8-b730-7df97e465f47';
$context = ['school_id' => 1, 'device_id' => 'ce351ba8-ef98-445d-9712-19671e289f16', 'snapshot_id' => '33477421-580b-4585-9e2d-bdf9acec109f', 'issued_at' => 1000, 'expires_at' => 1060];
$pair = sodium_crypto_sign_keypair();
$secret = sodium_crypto_sign_secretkey($pair);
$public = base64_encode(sodium_crypto_sign_publickey($pair));
$portrait = 'Synthetic protocol portrait bytes; not a decoded image.';
$metadata = ['v' => 1, 's' => 1, 'd' => $context['device_id'], 'id' => $context['snapshot_id'], 'i' => 1000, 'e' => 1060,
    'cards' => [['id' => $card, 'r' => 3, 'u' => 12, 't' => 'learner', 'p' => hash('sha256', $portrait)]],
    'keys' => [['id' => $keyId, 'public_key' => $public, 'starts_at' => 900, 'sign_until' => 2000, 'verify_until' => 2060]]];
if ($input['empty'] ?? false) {
    $metadata['cards'] = [];
}
if (isset($input['request_id'])) {
    $metadata['q'] = $input['request_id'];
}
$manifest = (new SignedOfflineManifest)->sign($metadata, $keyId, $secret);
$proof = (new SignedCardQr)->issue(1, $card, 3, $keyId, $secret, 1000 + ($input['proof_offset'] ?? 0));
sodium_memzero($secret);
sodium_memzero($pair);
$packet = ['envelope' => (new CardOfflineCipher)->seal(['manifest' => $manifest, 'portraits' => ($input['empty'] ?? false) ? (object) [] : (object) [$card => base64_encode($portrait)]], base64_decode($input['public_key'], true), $context), 'signing_key_id' => $keyId, 'signing_public_key' => $public];
if (isset($input['request_id'])) {
    $packet['request_id'] = $input['request_id'];
}
echo json_encode(['packet' => $packet, 'proof' => $proof], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
