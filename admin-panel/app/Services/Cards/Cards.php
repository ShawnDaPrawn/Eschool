<?php

declare(strict_types=1);

namespace App\Services\Cards;

use App\Models\Cards\Card;
use App\Models\Cards\CardChipProfile;
use App\Models\Cards\CardEvent;
use App\Models\Cards\CardPortrait;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Cards\Crypto\SignedCardQr;
use App\Services\Cards\Crypto\SunVerifier;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Cards
{
    public function metadata(Card $card): array
    {
        return ['id' => $card->id, 'school_id' => (int) $card->school_id, 'person_id' => (int) $card->user_id,
            'type' => $card->type, 'status' => $card->status, 'revision' => (int) $card->revision,
            'issued_at' => $card->issued_at->toISOString(), 'frozen_at' => $card->frozen_at?->toISOString(), 'replaced_by' => $card->replaced_by,
            'has_chip' => CardChipProfile::where('card_id', $card->id)->whereNotNull('encoded_at')->exists()];
    }

    private function event(Card $card, User $actor, string $action, string $reason): void
    {
        CardEvent::create(['card_id' => $card->id, 'actor_id' => $actor->id, 'action' => $action, 'reason' => $reason,
            'revision' => $card->revision, 'occurred_at' => now()->utc()]);
        Audit::record('card.'.$action, $card, [], ['card_id' => $card->id, 'person_id' => (int) $card->user_id, 'revision' => (int) $card->revision], app(CurrentSchool::class)->id());
    }

    private function create(User $actor, int $person, string $type): Card
    {
        app(CardAccess::class)->person($person, $type);
        abort_unless(CardPortrait::where('user_id', $person)->exists(), 422, 'Add a private card portrait before issuing the card.');
        $identity = $person.':'.$type;
        abort_if(Card::where('active_identity', $identity)->exists(), 409, 'This person already has a current card of that type. Replace the existing card instead.');
        $card = Card::create(['user_id' => $person, 'type' => $type, 'status' => 'active', 'revision' => 1, 'active_identity' => $identity, 'issued_at' => now()->utc()]);
        $this->event($card, $actor, 'issued', 'Issued school identity card.');

        return $card;
    }

    public function issue(User $actor, array $input): array
    {
        app(CardAccess::class)->manage($actor);
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'person_id' => ['required', 'integer', 'min:1'], 'type' => ['required', 'in:learner,staff,guardian']])->validate();
        $authority = function () use ($actor, $d): void {
            app(CardAccess::class)->manage($actor);
            app(CardAccess::class)->person((int) $d['person_id'], $d['type']);
        };

        return app(CardOperations::class)->once('user', (string) $actor->id, 'card.issue', $d, $authority,
            fn () => $this->metadata($this->create($actor, (int) $d['person_id'], $d['type'])));
    }

    public function credential(User $actor, string $id): array
    {
        app(CardAccess::class)->actor($actor);

        return DB::transaction(function () use ($actor, $id) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $card = Card::whereKey($id)->lockForUpdate()->firstOrFail();
            app(CardAccess::class)->owner($actor, $card);
            app(CardAccess::class)->active($card);
            $key = app(CardSigningKeys::class)->current();
            $now = now()->getTimestamp();
            $secret = base64_decode($key->secret_key, true);
            abort_unless(is_string($secret), 503);
            $qr = app(SignedCardQr::class)->issue((int) $card->school_id, $card->id, (int) $card->revision, $key->id, $secret, $now);
            sodium_memzero($secret);

            return ['card' => $this->metadata($card), 'credential' => $qr, 'expires_at' => gmdate('Y-m-d\TH:i:s\Z', $now + 25), 'key_id' => $key->id, 'public_key' => $key->public_key];
        }, 3);
    }

    public function freeze(User $actor, string $id, array $input): array
    {
        app(CardAccess::class)->actor($actor, revocation: true);
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'reason' => ['required', 'string', 'max:2000']])->validate();
        $d['card_id'] = $id;
        $authority = fn () => app(CardAccess::class)->owner($actor, Card::findOrFail($id), true);

        return app(CardOperations::class)->once('user', (string) $actor->id, 'card.freeze', $d, $authority, function () use ($actor, $id, $d) {
            $card = Card::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($card->status === 'active') {
                $card->update(['status' => 'frozen', 'frozen_at' => now()->utc(), 'revision' => (int) $card->revision + 1]);
                $this->event($card, $actor, 'frozen', $d['reason']);
            }

            return $this->metadata($card);
        });
    }

    public function replace(User $actor, string $id, array $input): array
    {
        app(CardAccess::class)->manage($actor);
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'reason' => ['required', 'string', 'max:2000']])->validate();
        $d['card_id'] = $id;
        $authority = function () use ($actor, $id): void {
            app(CardAccess::class)->manage($actor);
            $card = Card::findOrFail($id);
            app(CardAccess::class)->person((int) $card->user_id, $card->type);
        };

        return app(CardOperations::class)->once('user', (string) $actor->id, 'card.replace', $d, $authority, function () use ($actor, $id, $d) {
            $old = Card::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_if($old->replaced_by !== null, 409, 'This card has already been replaced.');
            $old->update(['active_identity' => null, 'status' => 'replaced', 'frozen_at' => now()->utc(), 'revision' => (int) $old->revision + 1]);
            $new = $this->create($actor, (int) $old->user_id, $old->type);
            $old->update(['replaced_by' => $new->id]);
            $this->event($old, $actor, 'replaced', $d['reason']);

            return ['previous' => $this->metadata($old), 'current' => $this->metadata($new)];
        });
    }

    public function restore(User $actor, string $id, array $input): array
    {
        app(CardAccess::class)->manage($actor);
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'reason' => ['required', 'string', 'max:2000'],
            'picc' => ['sometimes', 'string', 'size:32'], 'mac' => ['sometimes', 'string', 'size:16']])->validate();
        $d['card_id'] = $id;
        $authority = function () use ($actor, $id): void {
            app(CardAccess::class)->manage($actor);
            $card = Card::findOrFail($id);
            app(CardAccess::class)->person((int) $card->user_id, $card->type);
        };

        return app(CardOperations::class)->once('user', (string) $actor->id, 'card.restore', $d, $authority, function () use ($actor, $id, $d) {
            $card = Card::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($card->status === 'frozen' && $card->replaced_by === null, 409);
            $chip = CardChipProfile::where('card_id', $id)->lockForUpdate()->first();
            if ($chip !== null) {
                abort_unless(isset($d['picc'], $d['mac']) && $chip->encoded_at !== null, 422, 'Tap the recovered secure card before restoring it.');
                $proof = app(SunVerifier::class)->verify($d['picc'], $d['mac'], hex2bin($chip->metadata_key), hex2bin($chip->file_read_key), $chip->uid);
                abort_unless($proof['counter'] > $chip->last_counter, 409, 'A fresh secure-card tap is required.');
                $chip->update(['last_counter' => $proof['counter']]);
            }
            $card->update(['status' => 'active', 'frozen_at' => null, 'revision' => (int) $card->revision + 1]);
            $this->event($card, $actor, 'restored', $d['reason']);

            return $this->metadata($card);
        });
    }
}
