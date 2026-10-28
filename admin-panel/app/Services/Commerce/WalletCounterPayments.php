<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Commerce\VendorCategory;
use App\Models\Commerce\VendorOperation;
use App\Models\Commerce\VendorProduct;
use App\Models\Commerce\VendorStockBalance;
use App\Models\Commerce\WalletCounterPayment;
use App\Models\Commerce\WalletPaymentClosure;
use App\Models\Commerce\WalletPaymentIntent;
use App\Models\Money\WalletAccount;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Money\WalletPolicy;
use App\Services\Money\WalletOperations;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Short-lived instructions, with original money/stock and actual counter attribution. */
final class WalletCounterPayments
{
    public static function available(bool $write = false): void
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        foreach (['wallet_payments', 'wallet', 'vendors'] as $module) {
            abort_unless($write ? $gate->writable($module, locked: true) : $gate->allows($module), 403);
        }
    }

    private function holder(User $actor, WalletPaymentIntent $intent, bool $write = false): void
    {
        self::available($write);
        abort_unless($intent->kind === 'qr' && $intent->actor_id === (int) $actor->id, 404);
        $wallet = WalletAccount::lockForUpdate()->findOrFail($intent->wallet_account_id);
        app(WalletPolicy::class)->owner($actor, $wallet->user_id, $write);
    }

    public function listing(User $actor, int $page = 1): array
    {
        self::available();
        app(WalletPolicy::class)->actor($actor);
        $rows = WalletPaymentIntent::where('actor_id', $actor->id)->where('kind', 'qr')->orderByDesc('created_at')->paginate(20, ['*'], 'page', $page);
        $items = [];
        foreach ($rows as $row) {
            // Revoked child authority removes private instructions immediately, without hiding other authorised rows.
            try {
                $items[] = $this->personal($actor, $row->id);
            } catch (HttpExceptionInterface|ModelNotFoundException $error) {
                if (! ($error instanceof ModelNotFoundException) && ! in_array($error->getStatusCode(), [403, 404], true)) {
                    throw $error;
                }
            }
        }

        return $this->scope($actor) + ['items' => $items, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()];
    }

    public function personalOutcome(User $actor, string $operation): array
    {
        self::available();
        app(WalletPolicy::class)->actor($actor);
        $intent = WalletPaymentIntent::where('actor_id', $actor->id)->where('operation_id', $operation)->where('kind', 'qr')->firstOrFail();

        return $this->personal($actor, $intent->id);
    }

    public function catalogue(User $actor, int $vendor, int $page = 1): array
    {
        self::available(true);
        $counter = app(VendorOperatorAccess::class)->counter($actor, $vendor, true);
        $rows = VendorProduct::where('vendor_id', $vendor)->where('status', 'active')
            ->whereIn('category_id', VendorCategory::where('status', 'active')->select('id'))->orderBy('name')->orderBy('id')->paginate(30, ['*'], 'page', $page);

        return $this->scope($actor) + ['vendor_id' => $vendor, 'currency' => $counter->currency, 'items' => $rows->map(fn ($p) => ['id' => (int) $p->id, 'name' => $p->name, 'unit_label' => $p->unit_label, 'revision' => $p->revision, 'unit_price_cents' => $p->price_cents, 'vat_kind' => $p->vat_kind, 'vat_rate_bps' => $p->vat_rate_bps, 'available_quantity' => $p->tracks_stock ? VendorStockBalance::where('product_id', $p->id)->value('on_hand') : null])->all(), 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()];
    }

    public function qr(User $actor, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'quote' => ['required', 'string', 'max:32768'], 'confirmed' => ['required', 'accepted']])->validate();
        $orders = app(VendorOrders::class);
        $quoted = $orders->authorizePurchase($actor, $data);
        $receipt = app(WalletOperations::class)->once($actor, 'counter_qr_issue', $data, function () use ($actor, $data, $orders): void {
            self::available(true);
            $orders->authorizePurchase($actor, $data);
        }, function () use ($actor, $quoted, $orders, $data): array {
            abort_unless($quoted['expires_at'] >= now('UTC')->getTimestamp(), 409, 'Review a fresh quote before creating a payment QR.');
            $fresh = $orders->quote($actor, $quoted['cart']);
            // The encrypted original quote retains the full original seller revision.
            $current = $orders->authorizePurchase($actor, ['quote' => $fresh['quote']]);
            abort_unless(hash_equals($quoted['fingerprint'], $current['fingerprint']), 409, 'The quote changed. Review the exact items and total again.');
            $token = bin2hex(random_bytes(32));
            $intent = WalletPaymentIntent::create([
                'vendor_id' => $quoted['cart']['vendor_id'], 'wallet_account_id' => $quoted['cart']['wallet_id'],
                'actor_id' => $actor->id, 'operation_id' => $data['operation_id'], 'kind' => 'qr', 'currency' => $fresh['currency'],
                'amount_cents' => $fresh['amount_cents'], 'net_cents' => $fresh['net_cents'], 'vat_cents' => $fresh['vat_cents'],
                'private_quote' => ['cart' => $quoted['cart'], 'review' => array_intersect_key($fresh, array_flip(['seller', 'lines']))],
                'fingerprint' => $quoted['fingerprint'], 'private_qr_token' => $token, 'qr_hash' => hash('sha256', $token),
                'expires_at' => now('UTC')->addMinutes(2), 'created_at' => now('UTC'),
            ]);
            Audit::record('wallet.counter_qr_issued', $intent, [], ['vendor_id' => $intent->vendor_id]);

            return ['id' => $intent->id];
        });

        return $this->personal($actor, $receipt['id']);
    }

    public function personal(User $actor, string $id): array
    {
        $intent = WalletPaymentIntent::findOrFail($id);
        $this->holder($actor, $intent);
        $payment = WalletCounterPayment::where('intent_id', $intent->id)->first();
        $status = $payment !== null ? 'paid' : (WalletPaymentClosure::where('intent_id', $intent->id)->exists() ? 'cancelled' : ($intent->expires_at->isPast() ? 'expired' : 'awaiting_counter'));

        $wallet = WalletAccount::findOrFail($intent->wallet_account_id);
        $owner = User::withTrashed()->findOrFail($wallet->user_id);

        return $this->scope($actor) + $this->instruction($intent) + [
            'wallet_id' => (int) $wallet->id, 'wallet_owner_id' => $wallet->user_id, 'wallet_owner_name' => $owner->full_name,
            'status' => $status, 'qr' => $status === 'awaiting_counter' && app(ModuleGate::class)->writable('wallet_payments') ? 'navigateed:wallet-pay:'.$intent->private_qr_token : null,
            'order_id' => $payment?->order_id,
        ];
    }

    public function cancel(User $actor, string $id): array
    {
        return DB::transaction(function () use ($actor, $id): array {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $intent = WalletPaymentIntent::lockForUpdate()->findOrFail($id);
            $this->holder($actor, $intent, true);
            abort_if(WalletCounterPayment::where('intent_id', $id)->exists(), 409, 'This instruction is already paid. Open its original receipt.');
            if (! WalletPaymentClosure::where('intent_id', $id)->exists()) {
                WalletPaymentClosure::create(['intent_id' => $id, 'actor_id' => $actor->id, 'created_at' => now('UTC')]);
            }

            return $this->personal($actor, $id);
        }, 3);
    }

    public function lookup(User $actor, int $vendor, array $input): array
    {
        self::available(true);
        app(VendorOperatorAccess::class)->counter($actor, $vendor, true);
        $data = Validator::make($input, ['qr_token' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/D']])->validate();

        return DB::transaction(function () use ($actor, $vendor, $data): array {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $intent = WalletPaymentIntent::where('vendor_id', $vendor)->where('kind', 'qr')->where('qr_hash', hash('sha256', $data['qr_token']))->lockForUpdate()->firstOrFail();
            CounterSpendAuthority::fromIntent($actor, $intent);
            abort_if(WalletCounterPayment::where('intent_id', $intent->id)->exists(), 409, 'This instruction was already paid.');

            return $this->scope($actor) + $this->instruction($intent);
        }, 3);
    }

    public function pay(User $actor, int $vendor, array $input, ?string $readerCredential = null): array
    {
        return DB::transaction(function () use ($actor, $vendor, $input, $readerCredential): array {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $replayed = WalletCounterPayment::where('operation_id', $input['operation_id'] ?? '')->where('actor_id', $actor->id)->exists();
            $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'intent_id' => ['required', 'uuid'], 'qr_token' => ['nullable', 'string', 'regex:/\A[a-f0-9]{64}\z/D'], 'portrait_revision' => ['nullable', 'integer', 'min:1'], 'portrait_confirmed' => ['nullable', 'boolean'], 'confirmed' => ['required', 'accepted']])->validate() + ['vendor_id' => $vendor];
            $receipt = app(VendorOperations::class)->once($actor, 'wallet_counter_payment', $data, function () use ($actor, $vendor, $data, $readerCredential): array {
                $intent = WalletPaymentIntent::where('vendor_id', $vendor)->lockForUpdate()->findOrFail($data['intent_id']);
                $authority = CounterSpendAuthority::fromIntent($actor, $intent, $readerCredential);
                abort_unless($intent->kind !== 'qr' || (isset($data['qr_token']) && hash_equals($intent->qr_hash, hash('sha256', $data['qr_token']))), 403);
                abort_unless($intent->kind !== 'sun' || (($data['portrait_confirmed'] ?? false) && (int) ($data['portrait_revision'] ?? 0) === $intent->portrait_revision), 422, 'Check the current card portrait and exact amount before confirming.');
                $orderOperation = Uuid::uuid5(Uuid::NAMESPACE_URL, 'navigateed:wallet-counter:'.app(CurrentSchool::class)->id().':'.$data['operation_id'])->toString();
                $order = app(VendorOrders::class)->counterPurchase($authority, $orderOperation);
                $payment = WalletCounterPayment::create(['intent_id' => $intent->id, 'vendor_id' => $vendor, 'actor_id' => $actor->id, 'operation_id' => $data['operation_id'], 'order_id' => $order->id, 'created_at' => now('UTC')]);
                Audit::record('wallet.counter_payment_posted', $payment, [], ['vendor_id' => $vendor, 'order_id' => $order->id]);

                return ['kind' => 'wallet_counter_payment', 'vendor_id' => $vendor, 'id' => $payment->id];
            }, function () use ($actor, $vendor, $data, $readerCredential): void {
                self::available(true);
                app(VendorOperatorAccess::class)->counter($actor, $vendor, true);
                $intent = WalletPaymentIntent::where('vendor_id', $vendor)->findOrFail($data['intent_id']);
                if ($intent->kind === 'sun') {
                    $device = app(VendorPaymentReaders::class)->authenticate($actor, $vendor, $readerCredential ?? '');
                    abort_unless($device->id === $intent->device_id, 403);
                }
            });

            return $this->receipt($actor, $vendor, $receipt['id']) + ['replayed' => $replayed];
        }, 3);
    }

    public function receipt(User $actor, int $vendor, string $id): array
    {
        self::available();
        app(VendorOperatorAccess::class)->counter($actor, $vendor);
        $payment = WalletCounterPayment::where('vendor_id', $vendor)->where('actor_id', $actor->id)->findOrFail($id);

        return $this->scope($actor) + ['vendor_id' => $vendor, 'id' => $payment->id, 'intent_id' => $payment->intent_id,
            'operation_id' => $payment->operation_id, 'status' => 'paid', 'paid_at' => $payment->created_at->utc()->format('Y-m-d\TH:i:s.v\Z'),
            'order' => app(VendorCounterOrders::class)->receipt($actor, $vendor, $payment->order_id)];
    }

    public function resolve(User $actor, int $vendor, string $operation): array
    {
        Validator::make(['operation' => $operation], ['operation' => ['required', 'uuid']])->validate();

        return DB::transaction(function () use ($actor, $vendor, $operation): array {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            self::available();
            app(VendorOperatorAccess::class)->counter($actor, $vendor);
            $prior = VendorOperation::where('operation_id', $operation)->lockForUpdate()->first();
            $payment = null;
            if ($prior === null) {
                VendorOperation::create(['actor_id' => $actor->id, 'operation_id' => $operation, 'request_hash' => hash_hmac('sha256', 'abandoned_wallet_payment:'.$vendor.':'.$operation, (string) config('app.key')), 'receipt' => ['kind' => 'wallet_counter_payment', 'vendor_id' => $vendor, 'abandoned' => true], 'created_at' => now('UTC')]);
                $status = 'abandoned';
            } else {
                abort_unless($prior->actor_id === (int) $actor->id && ($prior->receipt['kind'] ?? null) === 'wallet_counter_payment' && ($prior->receipt['vendor_id'] ?? null) === $vendor, 404);
                $status = ($prior->receipt['abandoned'] ?? false) ? 'abandoned' : 'committed';
                if ($status === 'committed') {
                    $payment = $this->receipt($actor, $vendor, $prior->receipt['id']);
                }
            }

            return $this->scope($actor) + ['vendor_id' => $vendor, 'operation_id' => $operation, 'status' => $status, 'payment' => $payment];
        }, 3);
    }

    private function scope(User $actor): array
    {
        return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id];
    }

    public function counterInstruction(User $actor, WalletPaymentIntent $intent): array
    {
        app(VendorOperatorAccess::class)->counter($actor, $intent->vendor_id, true);

        return $this->scope($actor) + $this->instruction($intent);
    }

    private function instruction(WalletPaymentIntent $intent): array
    {
        return ['id' => $intent->id, 'vendor_id' => $intent->vendor_id, 'kind' => $intent->kind, 'currency' => $intent->currency,
            'amount_cents' => $intent->amount_cents, 'net_cents' => $intent->net_cents, 'vat_cents' => $intent->vat_cents,
            'expires_at' => $intent->expires_at->utc()->format('Y-m-d\TH:i:s.v\Z'),
            'seller' => array_intersect_key($intent->private_quote['review']['seller'], array_flip(['id', 'name', 'legal_name', 'legal_address', 'kind', 'vat_registered', 'vat_number'])), 'lines' => $intent->private_quote['review']['lines']];
    }
}
