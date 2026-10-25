<?php

declare(strict_types=1);

namespace App\Services\Cards;

use App\Models\Cards\Card;
use App\Models\Cards\CardChipProfile;
use App\Models\Cards\CardKey;
use App\Models\Cards\CardQrUse;
use App\Models\Cards\CardScanLog;
use App\Services\Cards\Crypto\SignedCardQr;
use App\Services\Cards\Crypto\SunVerifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use UnexpectedValueException;

class CardScans
{
    /** Replay of a transport operation returns its original scan, never a new entry. */
    public function qr(string $deviceCredential, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'credential' => ['required', 'string', 'max:1024']])->validate();
        $device = app(CardReaders::class)->authenticate($deviceCredential, 'gate', true);
        try {
            $refs = app(SignedCardQr::class)->unverifiedReferences($data['credential']);
        } catch (UnexpectedValueException) {
            abort(403, 'The card proof could not be verified.');
        }
        abort_unless($refs['school_id'] === app(CurrentSchool::class)->id(), 403);

        return app(CardOperations::class)->once('device', $device->id, 'scan.qr', $data, function () use ($deviceCredential, $refs) {
            app(CardReaders::class)->authenticate($deviceCredential, 'gate', true);
            app(CardAccess::class)->active(Card::whereKey($refs['card_id'])->lockForUpdate()->firstOrFail());
        }, function () use ($data, $refs, $device) {
            $card = Card::whereKey($refs['card_id'])->lockForUpdate()->firstOrFail();
            $key = CardKey::findOrFail($refs['key_id']);
            try {
                $claims = app(SignedCardQr::class)->verify($data['credential'], base64_decode($key->public_key), app(CurrentSchool::class)->id(), $card->id, $key->id, now()->getTimestamp());
            } catch (UnexpectedValueException) {
                abort(403, 'The card proof could not be verified.');
            }
            app(CardSigningKeys::class)->forVerification($key->id, $claims['i']);
            abort_unless($claims['r'] === $card->revision, 403);
            $nonce = hash('sha256', $claims['n']);
            abort_if(CardQrUse::where('nonce_hash', $nonce)->exists(), 409, 'This card proof was already used.');
            CardQrUse::create(['card_id' => $card->id, 'device_id' => $device->id, 'key_id' => $key->id, 'nonce_hash' => $nonce,
                'expires_at' => CarbonImmutable::createFromTimestampUTC($claims['e']), 'used_at' => now()->utc()]);

            return $this->accepted($device->id, $card, 'qr');
        });
    }

    public function sun(string $deviceCredential, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'card_id' => ['required', 'uuid'],
            'picc' => ['required', 'regex:/^[a-fA-F0-9]{32}$/D'], 'mac' => ['required', 'regex:/^[a-fA-F0-9]{16}$/D']])->validate();
        $device = app(CardReaders::class)->authenticate($deviceCredential, 'gate', true);

        return app(CardOperations::class)->once('device', $device->id, 'scan.sun', $data, function () use ($deviceCredential, $data) {
            app(CardReaders::class)->authenticate($deviceCredential, 'gate', true);
            app(CardAccess::class)->active(Card::whereKey($data['card_id'])->lockForUpdate()->firstOrFail());
        }, function () use ($data, $device) {
            $card = Card::findOrFail($data['card_id']);
            $profile = CardChipProfile::where('card_id', $card->id)->whereNotNull('encoded_at')->lockForUpdate()->firstOrFail();
            try {
                $proof = app(SunVerifier::class)->verify($data['picc'], $data['mac'], hex2bin($profile->metadata_key), hex2bin($profile->file_read_key), $profile->uid);
            } catch (UnexpectedValueException) {
                abort(403, 'The card proof could not be verified.');
            }
            abort_unless($proof['counter'] > $profile->last_counter, 409, 'This card proof was already used.');
            $profile->forceFill(['last_counter' => $proof['counter']])->save();

            return $this->accepted($device->id, $card, 'sun');
        });
    }

    private function accepted(string $device, Card $card, string $method): array
    {
        $scan = CardScanLog::create(['device_id' => $device, 'card_id' => $card->id, 'user_id' => $card->user_id,
            'method' => $method, 'outcome' => 'accepted', 'scanned_at' => now()->utc()]);

        return ['scan_id' => $scan->id, 'card_id' => $card->id, 'person_id' => (int) $card->user_id, 'type' => $card->type, 'outcome' => 'accepted', 'scanned_at' => $scan->scanned_at->toIso8601String()];
    }
}
