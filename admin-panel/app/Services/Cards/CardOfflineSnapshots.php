<?php

declare(strict_types=1);

namespace App\Services\Cards;

use App\Models\Cards\Card;
use App\Models\Cards\CardKey;
use App\Models\Cards\CardQrUse;
use App\Models\Cards\CardScanLog;
use App\Models\Platform\School;
use App\Services\Cards\Crypto\CardOfflineCipher;
use App\Services\Cards\Crypto\SignedCardQr;
use App\Services\Cards\Crypto\SignedOfflineManifest;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use UnexpectedValueException;

/** Bounded identity-only offline data; no payment, collection or login authority. */
class CardOfflineSnapshots
{
    public const LIFETIME = 60;

    /** Each encrypted page is independently device-bound and expires in one minute. */
    public function page(string $credential, ?string $after = null, ?string $challenge = null): array
    {
        $device = app(CardReaders::class)->authenticate($credential, 'gate', true);
        Validator::make(['after_card_id' => $after, 'request_id' => $challenge], ['after_card_id' => ['nullable', 'uuid'], 'request_id' => ['nullable', 'uuid']])->validate();

        return DB::transaction(function () use ($credential, $device, $after, $challenge) {
            $school = app(CurrentSchool::class)->id();
            School::whereKey($school)->lockForUpdate()->firstOrFail();
            app(CardReaders::class)->authenticate($credential, 'gate', true);
            $start = now()->getTimestamp();
            $context = ['school_id' => $school, 'device_id' => $device->id, 'snapshot_id' => (string) Str::uuid(), 'issued_at' => $start, 'expires_at' => $start + self::LIFETIME];
            $cards = [];
            $portraits = [];
            $bytes = 0;
            $cursor = $after;
            $rows = Card::where('status', 'active')->when($after !== null, fn ($q) => $q->where('id', '>', $after))->orderBy('id')->limit(101)->get();
            foreach ($rows->take(100) as $card) {
                try {
                    app(CardAccess::class)->active($card);
                    $portrait = app(CardPortraits::class)->offline($credential, $card);
                } catch (ModelNotFoundException) {
                    $cursor = $card->id;

                    continue;
                } catch (HttpExceptionInterface $error) {
                    // Recheck delegation first: one revoked reader must not look
                    // like an empty school roster.
                    app(CardReaders::class)->authenticate($credential, 'gate', true);
                    if (! in_array($error->getStatusCode(), [403, 404, 409], true)) {
                        throw $error;
                    }
                    $cursor = $card->id;

                    continue;
                }
                $encoded = base64_encode($portrait['bytes']);
                if ($bytes + strlen($encoded) > 2 * 1024 * 1024) {
                    break;
                }
                $cards[] = ['id' => $card->id, 'r' => (int) $card->revision, 'u' => (int) $card->user_id, 't' => $card->type, 'p' => hash('sha256', $portrait['bytes'])];
                $portraits[$card->id] = $encoded;
                $bytes += strlen($encoded);
                $cursor = $card->id;
            }
            $signing = app(CardSigningKeys::class)->current();
            $keys = CardKey::whereNull('revoked_at')->where('starts_at', '<=', now()->utc())->where('verify_until', '>', now()->utc())->get()
                ->map(fn (CardKey $key) => ['id' => $key->id, 'public_key' => $key->public_key, 'starts_at' => $key->starts_at->getTimestamp(), 'sign_until' => $key->sign_until->getTimestamp(), 'verify_until' => $key->verify_until->getTimestamp()])->all();
            $metadata = ['v' => 1, 's' => $school, 'd' => $device->id, 'id' => $context['snapshot_id'], 'i' => $start, 'e' => $context['expires_at'], 'cards' => $cards, 'keys' => $keys, 'q' => $challenge];
            $secret = base64_decode($signing->secret_key, true);
            abort_unless(is_string($secret), 503);
            try {
                $manifest = app(SignedOfflineManifest::class)->sign($metadata, $signing->id, $secret);
            } finally {
                sodium_memzero($secret);
            }
            $envelope = app(CardOfflineCipher::class)->seal(['manifest' => $manifest, 'portraits' => (object) $portraits], base64_decode($device->public_key, true), $context);
            app(CardReaders::class)->authenticate($credential, 'gate', true);
            abort_unless(now()->getTimestamp() < $context['expires_at'], 503, 'The offline roster took too long to prepare. Reconnect and refresh it.');
            $more = $cursor !== null && Card::where('status', 'active')->where('id', '>', $cursor)->exists();

            return ['request_id' => $challenge, 'envelope' => $envelope, 'signing_key_id' => $signing->id, 'signing_public_key' => $signing->public_key, 'next_after' => $more ? $cursor : null];
        });
    }

    /** Reconciliation records history only. It cannot authorize collection or sign-in. */
    public function reconcile(string $credential, array $input): array
    {
        $device = app(CardReaders::class)->authenticate($credential, 'gate');
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'manifest' => ['required', 'string', 'max:131072'],
            'credential' => ['required', 'string', 'max:1024'], 'scanned_at' => ['required', 'integer', 'min:1']])->validate();
        $data['scanned_at'] = (int) $data['scanned_at'];
        $authorize = fn () => app(CardReaders::class)->authenticate($credential, 'gate');
        $hashOnly = ['operation_id' => $data['operation_id'], 'manifest_hash' => hash('sha256', $data['manifest']), 'proof_hash' => hash('sha256', $data['credential']), 'scanned_at' => $data['scanned_at']];

        return app(CardOperations::class)->once('device', $device->id, 'offline.reconcile', $hashOnly, $authorize, function () use ($data, $device, $authorize) {
            $school = app(CurrentSchool::class)->id();
            $signer = app(SignedOfflineManifest::class);
            try {
                $key = CardKey::whereNull('revoked_at')->findOrFail($signer->keyId($data['manifest']));
                $manifest = $signer->verify($data['manifest'], base64_decode($key->public_key, true));
            } catch (UnexpectedValueException) {
                abort(403, 'The offline roster proof could not be verified.');
            }
            abort_unless(($manifest['v'] ?? null) === 1 && ($manifest['s'] ?? null) === $school && ($manifest['d'] ?? null) === $device->id
                && is_int($manifest['i'] ?? null) && is_int($manifest['e'] ?? null) && $manifest['e'] - $manifest['i'] === self::LIFETIME
                && $manifest['i'] <= $data['scanned_at'] && $data['scanned_at'] < $manifest['e'] && $data['scanned_at'] <= now()->getTimestamp()
                && now()->getTimestamp() <= $manifest['e'] + 600 && $key->starts_at->getTimestamp() <= $manifest['i'] && $manifest['i'] < $key->sign_until->getTimestamp()
                && $data['scanned_at'] < $key->verify_until->getTimestamp()
                && is_array($manifest['cards'] ?? null) && count($manifest['cards']) <= 100, 403, 'The offline roster has expired or belongs to another reader.');
            $qr = app(SignedCardQr::class);
            try {
                $refs = $qr->unverifiedReferences($data['credential']);
                abort_unless($refs['school_id'] === $school, 403);
                $proofKey = CardKey::whereNull('revoked_at')->findOrFail($refs['key_id']);
                $claims = $qr->verify($data['credential'], base64_decode($proofKey->public_key, true), $school, $refs['card_id'], $proofKey->id, $data['scanned_at']);
            } catch (UnexpectedValueException) {
                abort(403, 'The offline card proof could not be verified.');
            }
            abort_unless($proofKey->starts_at->getTimestamp() <= $claims['i'] && $claims['i'] < $proofKey->sign_until->getTimestamp()
                && $data['scanned_at'] < $proofKey->verify_until->getTimestamp(), 403);
            $entry = collect($manifest['cards'])->firstWhere('id', $refs['card_id']);
            abort_unless(is_array($entry) && ($entry['r'] ?? null) === $claims['r'], 403);
            $card = Card::whereKey($refs['card_id'])->lockForUpdate()->firstOrFail();
            $outcome = 'offline_accepted';
            if ($card->status !== 'active' || (int) $card->revision !== $claims['r']) {
                $outcome = 'status_changed';
            } else {
                try {
                    app(CardAccess::class)->active($card);
                } catch (ModelNotFoundException|HttpExceptionInterface) {
                    $outcome = 'access_changed';
                }
            }
            $nonce = hash('sha256', $claims['n']);
            if (CardQrUse::where('nonce_hash', $nonce)->exists()) {
                $outcome = 'duplicate';
            } elseif ($outcome === 'offline_accepted') {
                CardQrUse::create(['card_id' => $card->id, 'device_id' => $device->id, 'key_id' => $proofKey->id, 'nonce_hash' => $nonce,
                    'expires_at' => CarbonImmutable::createFromTimestampUTC($claims['e']), 'used_at' => CarbonImmutable::createFromTimestampUTC($data['scanned_at'])]);
            }
            $authorize();
            $log = CardScanLog::create(['device_id' => $device->id, 'card_id' => $card->id, 'user_id' => $card->user_id, 'method' => 'offline_qr',
                'outcome' => $outcome, 'scanned_at' => CarbonImmutable::createFromTimestampUTC($data['scanned_at'])]);

            return ['scan_id' => $log->id, 'card_id' => $card->id, 'outcome' => $outcome, 'collection_authorized' => false, 'sign_in_authorized' => false];
        });
    }
}
