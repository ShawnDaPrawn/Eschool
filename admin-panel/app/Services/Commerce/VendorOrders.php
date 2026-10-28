<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorCategory;
use App\Models\Commerce\VendorOrder;
use App\Models\Commerce\VendorOrderAction;
use App\Models\Commerce\VendorOrderLine;
use App\Models\Commerce\VendorProduct;
use App\Models\Commerce\VendorStockBalance;
use App\Models\Money\WalletAccount;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\User;
use App\Policies\Commerce\VendorPolicy;
use App\Policies\Money\WalletPolicy;
use App\Services\Money\WalletOperations;
use App\Services\Money\Wallets;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** One spending boundary for storefront orders and later online counter payments. */
final class VendorOrders
{
    public static function cartRules(): array
    {
        return ['vendor_id' => ['required', 'integer', 'min:1'], 'wallet_id' => ['required', 'integer', 'min:1'], 'learner_id' => ['nullable', 'integer', 'min:1'], 'lines' => ['required', 'array', 'min:1', 'max:20'], 'lines.*.product_id' => ['required', 'integer', 'min:1', 'distinct'], 'lines.*.quantity' => ['required', 'integer', 'between:1,999']];
    }

    private function cart(array $input): array
    {
        $data = Validator::make($input, self::cartRules())->validate();
        $lines = array_map(fn ($l) => ['product_id' => (int) $l['product_id'], 'quantity' => (int) $l['quantity']], $data['lines']);
        usort($lines, fn ($a, $b) => $a['product_id'] <=> $b['product_id']);

        return ['vendor_id' => (int) $data['vendor_id'], 'wallet_id' => (int) $data['wallet_id'], 'learner_id' => isset($data['learner_id']) ? (int) $data['learner_id'] : null, 'lines' => $lines];
    }

    private function buyer(User $actor, int $wallet, bool $write): WalletAccount
    {
        app(VendorPolicy::class)->actor($actor, $write);
        $row = WalletAccount::lockForUpdate()->findOrFail($wallet);
        app(WalletPolicy::class)->owner($actor, $row->user_id, $write);

        return $row;
    }

    private function recipient(User $actor, WalletAccount $wallet, ?int $learner): ?int
    {
        if ($learner === null) {
            $owner = User::findOrFail($wallet->user_id);
            if (in_array('Student', app(WalletPolicy::class)->roles($owner), true)) {
                return (int) Students::where('user_id', $owner->id)->sole()->id;
            }

            return null;
        }
        $subject = Students::lockForUpdate()->findOrFail($learner);
        app(WalletPolicy::class)->owner($actor, (int) $subject->user_id, true);
        abort_if($wallet->user_id !== (int) $actor->id && $wallet->user_id !== (int) $subject->user_id, 403, 'A child wallet order belongs to that original child.');

        return $learner;
    }

    /** Resolves current authoritative prices; caller totals, tax and stock are never accepted. */
    private function priced(User $actor, array $cart): array
    {
        $wallet = $this->buyer($actor, $cart['wallet_id'], true);
        $learner = $this->recipient($actor, $wallet, $cart['learner_id']);
        $vendor = Vendor::where('status', 'active')->lockForUpdate()->findOrFail($cart['vendor_id']);
        abort_unless(VendorCategory::whereKey($vendor->category_id)->where('status', 'active')->lockForUpdate()->exists(), 409, 'This vendor category is unavailable.');
        abort_unless($vendor->currency === $wallet->currency, 409, 'The buyer and vendor must use the configured school currency.');
        abort_if($vendor->vat_registered && trim((string) $vendor->legal_address) === '', 409, 'The vendor must configure its legal VAT address before checkout.');
        $summary = app(Wallets::class)->summary($wallet);
        abort_unless($summary['limits']['effective_daily_spend_cents'] > 0, 422, 'This wallet is not enabled for purchases under its current spending controls.');
        $lines = [];
        $gross = 0;
        $vat = 0;
        foreach ($cart['lines'] as $requested) {
            $p = VendorProduct::where('vendor_id', $vendor->id)->where('status', 'active')->lockForUpdate()->findOrFail($requested['product_id']);
            $category = VendorCategory::whereKey($p->category_id)->where('status', 'active')->lockForUpdate()->firstOrFail();
            abort_unless($summary['limits']['all_categories'] || in_array($category->key, $summary['limits']['categories'], true), 422, 'This wallet does not permit that product category.');
            abort_unless($vendor->vat_registered ? $p->vat_kind !== 'none' : $p->vat_kind === 'none', 409, 'Review this product against its vendor VAT registration.');
            $quantity = $requested['quantity'];
            if ($p->tracks_stock) {
                $stock = VendorStockBalance::where('product_id', $p->id)->lockForUpdate()->sole();
                abort_unless($stock->on_hand === app(VendorInventory::class)->actual((int) $p->id), 409, 'Reconcile counted stock before checkout.');
                abort_unless($stock->on_hand >= $quantity, 409, 'The requested counted quantity is unavailable.');
            }
            $total = $p->price_cents * $quantity;
            abort_unless($total <= 100000000000 && $gross <= 100000000000 - $total, 422, 'This order exceeds the supported amount.');
            $tax = $p->vat_kind === 'standard' ? Money::ofFraction($total * $p->vat_rate_bps, 10000 + $p->vat_rate_bps, $p->currency)->cents : 0;
            $lines[] = ['product_id' => (int) $p->id, 'product_revision' => $p->revision, 'product_name' => $p->name, 'sku' => $p->sku, 'unit_label' => $p->unit_label, 'category_key' => $category->key, 'quantity' => $quantity, 'unit_price_cents' => $p->price_cents, 'gross_cents' => $total, 'net_cents' => $total - $tax, 'vat_cents' => $tax, 'vat_kind' => $p->vat_kind, 'vat_rate_bps' => $p->vat_rate_bps, 'tracks_stock' => $p->tracks_stock];
            $admission = app(TicketSales::class)->pricedLine($p, $quantity, $learner);
            if ($admission !== null) {
                $lines[array_key_last($lines)]['ticket'] = $admission;
            }
            $gross += $total;
            $vat += $tax;
        }
        $admissions = count(array_filter($lines, fn ($line) => isset($line['ticket'])));
        abort_if($admissions > 0 && $admissions !== count($lines), 422, 'Buy digital admissions and physical goods in separate checkouts.');
        $this->capacity($wallet, $gross, $summary);

        return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id, 'wallet_id' => (int) $wallet->id, 'wallet_owner_id' => $wallet->user_id, 'learner_id' => $learner, 'vendor_id' => (int) $vendor->id, 'currency' => $vendor->currency, 'amount_cents' => $gross, 'vat_cents' => $vat, 'net_cents' => $gross - $vat, 'seller' => ['id' => (int) $vendor->id, 'revision' => $vendor->revision, 'name' => $vendor->name, 'legal_name' => $vendor->legal_name, 'legal_address' => $vendor->legal_address, 'kind' => $vendor->kind, 'vat_registered' => $vendor->vat_registered, 'vat_number' => $vendor->vat_number, 'refund_bearer' => $vendor->refund_bearer, 'chargeback_bearer' => $vendor->chargeback_bearer, 'settlement_method' => $vendor->settlement_method, 'settlement_cycle' => $vendor->settlement_cycle, 'settlement_delay_days' => $vendor->settlement_delay_days, 'settlement_terms' => $vendor->settlement_terms], 'lines' => $lines];
    }

    private function capacity(WalletAccount $wallet, int $amount, array $summary): void
    {
        abort_unless($summary['balance_cents'] >= $amount, 422, 'There is not enough available money in this original wallet.');
        $day = CarbonImmutable::now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->startOfDay();
        // Uncollected cancellations restore funds and the daily allowance atomically.
        $spent = (int) VendorOrder::where('wallet_account_id', $wallet->id)->whereNotIn('id', VendorOrderAction::where('action', 'cancelled')->select('order_id'))->whereNotNull('sealed_at')->where('created_at', '>=', $day->utc()->format('Y-m-d H:i:s.u'))->where('created_at', '<', $day->addDay()->utc()->format('Y-m-d H:i:s.u'))->sum('amount_cents');
        abort_unless($spent <= $summary['limits']['effective_daily_spend_cents'] - $amount, 422, 'This purchase would exceed the current school or guardian daily cap.');
    }

    public function quote(User $actor, array $input): array
    {
        $cart = $this->cart($input);

        return DB::transaction(function () use ($actor, $cart): array {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $priced = $this->priced($actor, $cart);
            $fingerprint = hash('sha256', json_encode($priced, JSON_THROW_ON_ERROR));
            $expires = now('UTC')->addMinutes(15);
            $token = Crypt::encryptString(json_encode(['school_id' => $priced['school_id'], 'actor_id' => (int) $actor->id, 'cart' => $cart, 'fingerprint' => $fingerprint, 'expires_at' => $expires->getTimestamp()], JSON_THROW_ON_ERROR));

            $priced['seller'] = array_intersect_key($priced['seller'], array_flip(['id', 'name', 'legal_name', 'legal_address', 'kind', 'vat_registered', 'vat_number']));

            return $priced + ['quote' => $token, 'expires_at' => $expires->toIso8601String()];
        }, 3);
    }

    /** Runs before the HTTP replay cache as well as the durable money operation. */
    public function authorizePurchase(User $actor, array $input): array
    {
        $data = Validator::make($input, ['quote' => ['required', 'string', 'max:32768']])->validate();
        try {
            $quoted = json_decode(Crypt::decryptString($data['quote']), true, 64, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            abort(422, 'This quote is invalid. Review a fresh checkout.');
        }
        abort_unless(is_array($quoted) && ($quoted['school_id'] ?? null) === app(CurrentSchool::class)->id() && ($quoted['actor_id'] ?? null) === (int) $actor->id && is_array($quoted['cart'] ?? null) && is_int($quoted['expires_at'] ?? null) && is_string($quoted['fingerprint'] ?? null), 403);
        $this->buyer($actor, (int) ($quoted['cart']['wallet_id'] ?? 0), true);

        return $quoted;
    }

    public function authorizeOrder(User $actor, int $id, bool $write = false): VendorOrder
    {
        $order = VendorOrder::lockForUpdate()->findOrFail($id);
        $this->buyer($actor, $order->wallet_account_id, $write);

        return $order;
    }

    public function purchase(User $actor, array $input): VendorOrder
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'quote' => ['required', 'string', 'max:32768'], 'confirmed' => ['required', 'accepted']])->validate();
        $quoted = $this->authorizePurchase($actor, $data);
        $cart = $this->cart($quoted['cart']);
        $receipt = app(WalletOperations::class)->once($actor, 'vendor_order', $data, fn () => $this->buyer($actor, $cart['wallet_id'], true), function () use ($actor, $data, $quoted, $cart): array {
            abort_unless($quoted['expires_at'] >= now('UTC')->getTimestamp(), 409, 'This quote expired. Review a fresh checkout.');
            $priced = $this->priced($actor, $cart);
            abort_unless(hash_equals($quoted['fingerprint'], hash('sha256', json_encode($priced, JSON_THROW_ON_ERROR))), 409, 'The vendor, price or tax changed. Review a fresh checkout.');
            $wallet = WalletAccount::findOrFail($cart['wallet_id']);
            $vendor = Vendor::findOrFail($cart['vendor_id']);
            $entry = null;
            if ($priced['amount_cents'] > 0) {
                $money = Money::ofCents($priced['amount_cents'], $priced['currency']);
                $entry = app(LedgerService::class)->post('vendor:order:'.$data['operation_id'], 'vendor:order:'.$data['operation_id'], 'Original wallet vendor order', [Posting::debit($wallet->ledger_account_id, $money), Posting::credit($vendor->ledger_account_id, $money)]);
            }
            $school = app(CurrentSchool::class)->id();
            DB::table('vendor_order_counters')->insertOrIgnore(['school_id' => $school, 'next_number' => 1]);
            $number = DB::table('vendor_order_counters')->where('school_id', $school)->lockForUpdate()->firstOrFail()->next_number;
            DB::table('vendor_order_counters')->where('school_id', $school)->update(['next_number' => (int) $number + 1]);
            $token = bin2hex(random_bytes(32));
            $order = VendorOrder::create(['operation_id' => $data['operation_id'], 'order_number' => 'VO-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT), 'vendor_id' => $vendor->id, 'wallet_account_id' => $wallet->id, 'wallet_owner_id' => $wallet->user_id, 'actor_id' => $actor->id, 'learner_id' => $priced['learner_id'], 'ledger_entry_id' => $entry, 'currency' => $priced['currency'], 'amount_cents' => $priced['amount_cents'], 'net_cents' => $priced['net_cents'], 'vat_cents' => $priced['vat_cents'], 'seller_snapshot' => $priced['seller'], 'quote_fingerprint' => $quoted['fingerprint'], 'private_qr_token' => $token, 'qr_hash' => hash('sha256', $token), 'created_at' => now('UTC')]);
            foreach ($priced['lines'] as $line) {
                $root = VendorOrderLine::create(array_diff_key($line, ['ticket' => true]) + ['order_id' => $order->id]);
                app(VendorInventory::class)->order($root);
            }
            $order->sealed_at = CarbonImmutable::now('UTC');
            $order->save();
            app(TicketSales::class)->issue($actor, $order, $priced['lines']);
            Audit::record('vendors.order_purchased', $order, [], ['vendor_id' => $vendor->id, 'amount_cents' => $order->amount_cents]);

            return ['id' => (int) $order->id];
        });

        return VendorOrder::findOrFail($receipt['id']);
    }

    public function state(VendorOrder $order): string
    {
        abort_unless($order->sealed_at !== null, 409, 'Order completion needs reconciliation.');

        return VendorOrderAction::where('order_id', $order->id)->value('action') ?? 'ready';
    }

    public function cancel(User $actor, int $id, array $input): VendorOrder
    {
        return $this->cancellation($actor, $id, $input, false);
    }

    public function cancelByFinance(User $actor, int $id, array $input): VendorOrder
    {
        Validator::make($input, ['verified_uncollected' => ['required', 'accepted']])->validate();

        return $this->cancellation($actor, $id, $input, true);
    }

    private function cancellation(User $actor, int $id, array $input, bool $finance): VendorOrder
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'private_reason' => ['required', 'string', 'min:3', 'max:2000'], 'confirmed' => ['required', 'accepted']])->validate() + ['order_id' => $id];
        $authorize = function () use ($actor, $id, $finance): void {
            $order = VendorOrder::lockForUpdate()->findOrFail($id);
            if ($finance) {
                app(VendorPolicy::class)->finance($actor, true);
                app(WalletPolicy::class)->finance($actor, true);
            } else {
                $this->buyer($actor, $order->wallet_account_id, true);
            }
        };
        $receipt = app(WalletOperations::class)->once($actor, $finance ? 'vendor_order_cancel_finance' : 'vendor_order_cancel', $data, $authorize, function () use ($actor, $id, $data): array {
            $order = VendorOrder::lockForUpdate()->findOrFail($id);
            abort_unless($this->state($order) === 'ready', 409, 'Only an uncollected order can be cancelled.');
            $vendor = Vendor::findOrFail($order->vendor_id);
            $wallet = WalletAccount::findOrFail($order->wallet_account_id);
            $entry = null;
            if ($order->amount_cents > 0) {
                abort_unless(app(LedgerService::class)->balance($vendor->ledger_account_id)->cents >= $order->amount_cents, 409, 'Reconcile the original vendor payable before cancelling.');
                $money = Money::ofCents($order->amount_cents, $order->currency);
                $entry = app(LedgerService::class)->post('vendor:cancel:'.$order->id, 'vendor:cancel:'.$order->id, 'Cancelled uncollected original wallet order', [Posting::debit($vendor->ledger_account_id, $money), Posting::credit($wallet->ledger_account_id, $money)]);
            }
            $action = VendorOrderAction::create(['order_id' => $order->id, 'operation_id' => $data['operation_id'], 'actor_id' => $actor->id, 'action' => 'cancelled', 'ledger_entry_id' => $entry, 'private_reason' => $data['private_reason'], 'created_at' => now('UTC')]);
            foreach (VendorOrderLine::where('order_id', $order->id)->orderBy('id')->get() as $line) {
                app(VendorInventory::class)->order($line, $action);
            }
            Audit::record('vendors.order_cancelled', $order);

            return ['id' => (int) $order->id];
        });

        return VendorOrder::findOrFail($receipt['id']);
    }

    /** Finance counter boundary; dedicated vendor operator ownership is added in increment 12. */
    public function collect(User $actor, int $vendor, array $input, bool $operator = false): VendorOrder
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'order_number' => ['nullable', 'string', 'regex:/\AVO-\d{6,20}\z/D', 'required_without:qr_token'], 'qr_token' => ['nullable', 'string', 'regex:/\A[a-f0-9]{64}\z/D', 'required_without:order_number'], 'confirmed' => ['required', 'accepted']])->validate() + ['vendor_id' => $vendor];
        abort_if(! empty($data['order_number']) && ! empty($data['qr_token']), 422, 'Scan the QR or enter the order number.');
        $authorize = $operator ? fn () => app(VendorOperatorAccess::class)->counter($actor, $vendor, true) : fn () => app(VendorPolicy::class)->finance($actor, true);
        $receipt = app(VendorOperations::class)->once($actor, 'order_collect', $data, function () use ($actor, $vendor, $data): array {
            Vendor::findOrFail($vendor);
            $order = VendorOrder::where('vendor_id', $vendor)->when(! empty($data['order_number']), fn ($q) => $q->where('order_number', $data['order_number']), fn ($q) => $q->where('qr_hash', hash('sha256', $data['qr_token'])))->lockForUpdate()->firstOrFail();
            abort_unless($this->state($order) === 'ready', 409, 'This order was already collected or cancelled.');
            VendorOrderAction::create(['order_id' => $order->id, 'operation_id' => $data['operation_id'], 'actor_id' => $actor->id, 'action' => 'collected', 'created_at' => now('UTC')]);
            Audit::record('vendors.order_collected', $order);

            return ['id' => (int) $order->id];
        }, $authorize);

        return VendorOrder::findOrFail($receipt['id']);
    }
}
