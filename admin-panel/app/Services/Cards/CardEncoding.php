<?php

declare(strict_types=1);

namespace App\Services\Cards;

use App\Models\Cards\Card;
use App\Models\Cards\CardChipProfile;
use App\Models\Cards\CardEvent;
use App\Models\Cards\CardReaderDevice;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Cards\Crypto\CardEncodingCipher;
use App\Services\Cards\Crypto\SunVerifier;
use App\Services\Cards\Encoding\NtagEncodingProfile;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use UnexpectedValueException;

/** Explicitly authorized personalization, followed by actual SUN confirmation. */
class CardEncoding
{
    private function device(string $id): CardReaderDevice
    {
        $device = CardReaderDevice::where('kind', 'encoder')->findOrFail($id);
        abort_unless($device->status === 'active' && $device->revoked_at === null && $device->expires_at->isFuture(), 403);
        app(CardAccess::class)->encode(User::findOrFail($device->issued_by));
        app(CardReaders::class)->validateEncoderPublicKey($device->public_key);

        return $device;
    }

    public function prepare(User $actor, string $cardId, array $input): array
    {
        app(CardAccess::class)->encode($actor);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'device_id' => ['required', 'uuid'], 'uid' => ['required', 'regex:/^04[a-fA-F0-9]{12}$/D']])->validate();
        $data['uid'] = strtoupper($data['uid']);
        $data['card_id'] = $cardId;
        $authority = function () use ($actor, $data): void {
            app(CardAccess::class)->encode($actor);
            $this->device($data['device_id']);
            app(CardAccess::class)->active(Card::whereKey($data['card_id'])->lockForUpdate()->firstOrFail());
        };

        return app(CardOperations::class)->once('user', (string) $actor->id, 'encoding.prepare', $data, $authority, function () use ($actor, $data, $authority) {
            $card = Card::findOrFail($data['card_id']);
            abort_if(CardChipProfile::where('card_id', $card->id)->exists(), 409, 'This card already has a personalization plan. Resume that plan instead of creating another one.');
            abort_if(CardChipProfile::where('uid_hash', hash('sha256', $data['uid']))->exists(), 409, 'This chip already belongs to another school card.');
            $keys = [];
            for ($i = 0; $i < 5; $i++) {
                $value = bin2hex(random_bytes(16));
                abort_if($value === str_repeat('0', 32) || in_array($value, $keys, true), 503);
                $keys[] = $value;
            }
            $profile = new CardChipProfile(['card_id' => $card->id, 'encoder_device_id' => $data['device_id'], 'uid' => $data['uid'],
                'uid_hash' => hash('sha256', $data['uid']), 'metadata_key' => $keys[2], 'file_read_key' => $keys[1], 'key_version' => 1, 'last_counter' => -1]);
            $profile->setUniqueIds();
            $plan = ['v' => 1, 'school_id' => app(CurrentSchool::class)->id(), 'device_id' => $data['device_id'], 'plan_id' => $profile->id,
                'card_id' => $card->id, 'card_revision' => (int) $card->revision, 'uid' => $data['uid'], 'keys' => $keys, 'key_version' => 1,
                'prepared_by' => (int) $actor->id] + app(NtagEncodingProfile::class)->create((string) config('app.url'), app(CurrentSchool::class)->id(), $card->id);
            $profile->encoding_package = Crypt::encryptString(json_encode($plan, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
            $profile->save();
            CardEvent::create(['card_id' => $card->id, 'actor_id' => $actor->id, 'action' => 'encoding_prepared', 'reason' => 'Prepared encrypted chip personalization.',
                'revision' => (int) $card->revision, 'occurred_at' => now()->utc()]);
            Audit::record('card.encoding_prepared', $card, [], ['card_id' => $card->id, 'plan_id' => $profile->id, 'encoder_device_id' => $data['device_id']], app(CurrentSchool::class)->id());

            $authority();

            return ['prepared' => true, 'plan_id' => $profile->id, 'card_id' => $card->id, 'revision' => (int) $card->revision];
        });
    }

    /** All authority is checked before returning any decrypted job or chip keys. */
    private function authorized(string $device, string $id): array
    {
        $this->device($device);
        $profile = CardChipProfile::where('encoder_device_id', $device)->whereKey($id)->lockForUpdate()->firstOrFail();
        $card = Card::whereKey($profile->card_id)->lockForUpdate()->firstOrFail();
        app(CardAccess::class)->active($card);
        $plan = json_decode(Crypt::decryptString($profile->encoding_package), true, 12, JSON_THROW_ON_ERROR);
        abort_unless(is_array($plan) && ($plan['v'] ?? null) === 1 && ($plan['school_id'] ?? null) === app(CurrentSchool::class)->id()
            && ($plan['device_id'] ?? null) === $device && ($plan['plan_id'] ?? null) === $profile->id && ($plan['card_id'] ?? null) === $card->id
            && ($plan['card_revision'] ?? null) === (int) $card->revision, 403);
        app(CardAccess::class)->encode(User::findOrFail($plan['prepared_by']));

        return [$profile, $card, $plan];
    }

    public function download(string $credential, string $id): array
    {
        $device = app(CardReaders::class)->authenticate($credential, 'encoder', true);

        return DB::transaction(function () use ($credential, $device, $id) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            [$profile, $card, $plan] = $this->authorized($device->id, $id);
            unset($plan['prepared_by']);
            $plan['confirmed'] = $profile->encoded_at !== null;
            $start = now()->getTimestamp();
            $context = ['school_id' => app(CurrentSchool::class)->id(), 'device_id' => $device->id, 'plan_id' => $profile->id,
                'card_id' => $card->id, 'revision' => (int) $card->revision, 'issued_at' => $start, 'expires_at' => $start + 600];
            $result = app(CardEncodingCipher::class)->seal($plan, base64_decode($device->public_key, true), $context);
            app(CardReaders::class)->authenticate($credential, 'encoder', true);
            $this->authorized($device->id, $id);
            abort_unless(now()->getTimestamp() < $context['expires_at'], 503);

            return $result;
        });
    }

    public function confirm(string $credential, string $id, array $input): array
    {
        $device = app(CardReaders::class)->authenticate($credential, 'encoder', true);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'picc' => ['required', 'regex:/^[a-fA-F0-9]{32}$/D'], 'mac' => ['required', 'regex:/^[a-fA-F0-9]{16}$/D']])->validate();
        $data['picc'] = strtoupper($data['picc']);
        $data['mac'] = strtoupper($data['mac']);
        $data['plan_id'] = $id;
        $authorize = function () use ($credential, $device, $id): void {
            app(CardReaders::class)->authenticate($credential, 'encoder', true);
            $this->authorized($device->id, $id);
        };

        return app(CardOperations::class)->once('device', $device->id, 'encoding.confirm', $data, $authorize, function () use ($device, $id, $data, $authorize) {
            [$profile, $card, $plan] = $this->authorized($device->id, $id);
            try {
                $proof = app(SunVerifier::class)->verify($data['picc'], $data['mac'], hex2bin($profile->metadata_key), hex2bin($profile->file_read_key), $profile->uid);
            } catch (UnexpectedValueException) {
                abort(403, 'The programmed chip could not be verified.');
            }
            abort_unless($proof['counter'] > $profile->last_counter, 409, 'This chip confirmation was already used. Retry its original operation.');
            $first = $profile->encoded_at === null;
            $profile->forceFill(['encoded_at' => $profile->encoded_at ?? now()->utc(), 'last_counter' => $proof['counter']])->save();
            if ($first) {
                CardEvent::create(['card_id' => $card->id, 'actor_id' => $plan['prepared_by'], 'action' => 'encoded', 'reason' => 'SUN-confirmed chip personalization.',
                    'revision' => (int) $card->revision, 'occurred_at' => now()->utc()]);
                Audit::record('card.encoded', $card, [], ['card_id' => $card->id, 'plan_id' => $profile->id, 'encoder_device_id' => $device->id], app(CurrentSchool::class)->id());
            }

            $authorize();

            return ['encoded' => true, 'plan_id' => $profile->id, 'card_id' => $card->id, 'revision' => (int) $card->revision,
                'verified_counter' => $proof['counter'], 'encoded_at' => $profile->encoded_at->toISOString(), 'collection_authorized' => false, 'sign_in_authorized' => false];
        });
    }
}
