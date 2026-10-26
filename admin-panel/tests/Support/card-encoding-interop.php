<?php

declare(strict_types=1);

// Synthetic cross-runtime packet: random chip keys; no database or hardware.
require __DIR__.'/../../vendor/autoload.php';

use App\Services\Cards\Crypto\CardEncodingCipher;
use App\Services\Cards\Encoding\NtagEncodingProfile;

$input = json_decode(stream_get_contents(STDIN), true, 12, JSON_THROW_ON_ERROR);
$public = base64_decode($input['public_key'], true);
$issued = time();
$context = ['school_id' => 1, 'device_id' => 'ce351ba8-ef98-445d-9712-19671e289f16', 'plan_id' => '33477421-580b-4585-9e2d-bdf9acec109f',
    'card_id' => '03329821-2574-4cf8-b730-7df97e465f47', 'revision' => 3, 'issued_at' => $issued, 'expires_at' => $issued + 600];
$payload = ['v' => 1, 'school_id' => 1, 'device_id' => $context['device_id'], 'plan_id' => $context['plan_id'], 'card_id' => $context['card_id'], 'card_revision' => 3,
    'uid' => '04'.strtoupper(bin2hex(random_bytes(6))), 'keys' => array_map(fn () => bin2hex(random_bytes(16)), range(0, 4)), 'key_version' => 1, 'confirmed' => false]
    + (new NtagEncodingProfile)->create('https://school.example', 1, $context['card_id']);
echo json_encode(['envelope' => (new CardEncodingCipher)->seal($payload, $public, $context), 'payload' => $payload], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
