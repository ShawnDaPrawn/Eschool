<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Cards\Card;
use App\Models\Cards\CardChipProfile;
use App\Models\Cards\CardPortrait;
use App\Models\Commerce\WalletCounterPayment;
use App\Models\Commerce\WalletPaymentClosure;
use App\Models\Commerce\WalletPaymentIntent;
use App\Models\Money\WalletAccount;
use App\Models\Students;
use App\Models\User;
use App\Policies\Money\WalletPolicy;
use App\Services\Cards\CardAccess;
use App\Services\Cards\Crypto\SunVerifier;
use Illuminate\Support\Facades\DB;
use UnexpectedValueException;

/** A Vendor cannot turn a caller-supplied holder ID into financial authority. */
final class CounterSpendAuthority
{
    private function __construct(public readonly WalletPaymentIntent $intent, public readonly User $operator, private readonly ?string $readerCredential = null) {}

    public static function fromIntent(User $operator, WalletPaymentIntent $intent, ?string $readerCredential = null): self
    {
        abort_unless(DB::transactionLevel() > 0 && $intent->exists, 500);
        $authority = new self($intent, $operator, $readerCredential);
        $authority->assertCurrent();

        return $authority;
    }

    /** The only unsaved card authority is created by online SUN verification under the school lock. */
    public static function fromTap(User $operator, int $vendor, string $credential, array $data): self
    {
        abort_unless(DB::transactionLevel() > 0, 500);
        $device = app(VendorPaymentReaders::class)->authenticate($operator, $vendor, $credential);
        $card = Card::lockForUpdate()->findOrFail($data['card_id']);
        $subject = app(CardAccess::class)->active($card);
        app(WalletPolicy::class)->activePerson($subject);
        $profile = CardChipProfile::where('card_id', $card->id)->whereNotNull('encoded_at')->lockForUpdate()->firstOrFail();
        try {
            $proof = app(SunVerifier::class)->verify($data['picc'], $data['mac'], hex2bin($profile->metadata_key), hex2bin($profile->file_read_key), $profile->uid);
        } catch (UnexpectedValueException) {
            abort(403, 'The card proof could not be verified.');
        }
        abort_unless($proof['counter'] > $profile->last_counter, 409, 'This card proof was already used.');
        $portrait = CardPortrait::where('user_id', $subject->id)->lockForUpdate()->firstOrFail();
        $currency = app(VendorOperatorAccess::class)->counter($operator, $vendor, true)->currency;
        $wallet = WalletAccount::where('user_id', $subject->id)->where('currency', $currency)->lockForUpdate()->firstOrFail();
        $learner = in_array('Student', app(WalletPolicy::class)->roles($subject), true) ? (int) Students::where('user_id', $subject->id)->sole()->id : null;
        $intent = new WalletPaymentIntent(['operation_id' => $data['operation_id'], 'vendor_id' => $vendor, 'wallet_account_id' => $wallet->id, 'actor_id' => $subject->id, 'kind' => 'sun',
            'card_id' => $card->id, 'device_id' => $device->id, 'card_revision' => $card->revision, 'portrait_revision' => $portrait->revision, 'portrait_digest' => $portrait->digest, 'sun_counter' => $proof['counter'],
            'private_quote' => ['cart' => ['vendor_id' => $vendor, 'wallet_id' => (int) $wallet->id, 'learner_id' => $learner, 'lines' => $data['lines']]], 'expires_at' => now('UTC')->addMinutes(2), 'created_at' => now('UTC')]);
        $authority = new self($intent, $operator, $credential);
        $authority->assertCurrent();
        $profile->forceFill(['last_counter' => $proof['counter']])->save();

        return $authority;
    }

    public function assertCurrent(): void
    {
        WalletCounterPayments::available(true);
        app(VendorOperatorAccess::class)->counter($this->operator, $this->intent->vendor_id, true);
        abort_unless(in_array($this->intent->kind, ['qr', 'sun'], true), 422);
        abort_unless($this->intent->expires_at->isFuture(), 409, 'This payment instruction expired. Review a new one.');
        abort_if(WalletPaymentClosure::where('intent_id', $this->intent->id)->exists(), 409, 'This payment instruction was cancelled.');
        if ($this->intent->exists) {
            abort_if(WalletCounterPayment::where('intent_id', $this->intent->id)->exists(), 409, 'This instruction was already paid.');
        }
        $wallet = WalletAccount::lockForUpdate()->findOrFail($this->intent->wallet_account_id);
        if ($this->intent->kind === 'sun') {
            $device = app(VendorPaymentReaders::class)->authenticate($this->operator, $this->intent->vendor_id, $this->readerCredential ?? '');
            abort_unless($device->id === $this->intent->device_id, 403);
            $card = Card::lockForUpdate()->findOrFail($this->intent->card_id);
            $subject = app(CardAccess::class)->active($card);
            app(WalletPolicy::class)->activePerson($subject);
            abort_unless((int) $card->revision === $this->intent->card_revision && (int) $subject->id === $wallet->user_id && (int) $subject->id === $this->intent->actor_id, 409, 'The original card changed. Tap again.');
            $portrait = CardPortrait::where('user_id', $subject->id)->lockForUpdate()->firstOrFail();
            abort_unless($portrait->revision === $this->intent->portrait_revision && hash_equals($portrait->digest, $this->intent->portrait_digest), 409, 'The card portrait changed. Tap again.');
        } else {
            app(WalletPolicy::class)->owner(User::findOrFail($this->intent->actor_id), $wallet->user_id, true);
        }
    }

    public function wallet(array $cart): WalletAccount
    {
        $this->assertCurrent();
        abort_unless($cart['wallet_id'] === $this->intent->wallet_account_id && $cart['vendor_id'] === $this->intent->vendor_id, 403);

        return WalletAccount::lockForUpdate()->findOrFail($this->intent->wallet_account_id);
    }

    public function recipient(array $cart, WalletAccount $wallet): ?int
    {
        $actor = User::findOrFail($this->intent->actor_id);
        $learner = $cart['learner_id'];
        if ($this->intent->kind === 'sun') {
            $ownLearner = in_array('Student', app(WalletPolicy::class)->roles($actor), true) ? (int) Students::where('user_id', $actor->id)->sole()->id : null;
            abort_unless($learner === $ownLearner, 403);

            return $ownLearner;
        }
        if ($learner === null) {
            $subject = User::findOrFail($wallet->user_id);

            return in_array('Student', app(WalletPolicy::class)->roles($subject), true)
                ? (int) Students::where('user_id', $subject->id)->sole()->id : null;
        }
        $subject = Students::lockForUpdate()->findOrFail($learner);
        app(WalletPolicy::class)->owner($actor, (int) $subject->user_id, true);
        abort_if($wallet->user_id !== (int) $actor->id && $wallet->user_id !== (int) $subject->user_id, 403);

        return $learner;
    }
}
