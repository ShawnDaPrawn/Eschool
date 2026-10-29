<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorOrder;
use App\Models\Commerce\VendorOrderAction;
use App\Models\Commerce\VendorOrderLine;
use App\Models\Commerce\VendorProduct;
use App\Models\Commerce\VendorStockBalance;
use App\Models\Commerce\VendorStockEvent;
use App\Models\Money\WalletAccount;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\Students;
use App\Models\User;
use App\Services\Commerce\VendorCatalogueReconciliation;
use App\Services\Commerce\VendorCatalogues;
use App\Services\Commerce\VendorOrderReconciliation;
use App\Services\Commerce\VendorOrders;
use App\Services\Commerce\VendorStorefronts;
use App\Services\Money\WalletDeposits;
use App\Services\Money\WalletOperations;
use App\Services\Money\Wallets;
use App\Services\Money\WalletSettings;
use App\Services\Money\WalletTransfers;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class VendorOrdersTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private User $admin;

    private User $parent;

    private User $child;

    private WalletAccount $wallet;

    private WalletAccount $childWallet;

    private Vendor $vendor;

    private VendorProduct $product;

    private VendorOrders $orders;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->admin = $this->superAdmin();
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->child = User::findOrFail($this->schoolA->ids['asha_user']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'can_approve' => true, 'can_see_fees' => false, 'relationship' => 'guardian']);
        LearnerApproval::firstOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->parent->id], ['consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        app(ModuleGate::class)->change('wallet', true, $this->admin);
        app(ModuleGate::class)->change('fees', false, $this->admin);
        app(ModuleGate::class)->change('vendors', true, $this->admin);
        app(WalletSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'currency' => 'ZAR', 'daily_spend_cents' => 5000, 'child_limits_required' => true]);
        app(WalletDeposits::class)->policy($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true]);
        $claim = app(WalletDeposits::class)->claim($this->parent, ['operation_id' => (string) Str::uuid(), 'amount' => '500.00', 'method' => 'eft', 'source_reference' => 'SYNTHETIC-ORDER-BACKING', 'paid_on' => now()->toDateString(), 'evidence' => 'Synthetic actual original parent deposit', 'confirmed' => true]);
        app(WalletDeposits::class)->verify($this->admin, $claim->id, ['operation_id' => (string) Str::uuid(), 'amount' => '500.00', 'method' => 'eft', 'source_reference' => 'SYNTHETIC-ACTUAL-ORDER-BACKING', 'received_on' => now()->toDateString(), 'evidence' => 'Independently verified actual synthetic parent backing', 'verified_original_holder' => true, 'verified_actual_receipt' => true]);
        $this->wallet = WalletAccount::sole();
        $this->childWallet = app(Wallets::class)->open($this->parent, ['operation_id' => (string) Str::uuid(), 'owner_user_id' => $this->child->id]);
        app(WalletTransfers::class)->transfer($this->parent, ['operation_id' => (string) Str::uuid(), 'from_wallet_id' => $this->wallet->id, 'to_wallet_id' => $this->childWallet->id, 'amount' => '50.00', 'confirmed' => true]);
        $catalogues = app(VendorCatalogues::class);
        $category = $catalogues->category($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'key' => 'food', 'name' => 'Food', 'status' => 'active']);
        $this->vendor = $catalogues->vendor($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'category_id' => $category->id, 'name' => 'Synthetic school tuckshop', 'kind' => 'school', 'status' => 'active', 'legal_name' => 'Synthetic school vendor', 'vat_registered' => false, 'refund_bearer' => 'school', 'chargeback_bearer' => 'school', 'settlement_method' => 'school_internal', 'settlement_cycle' => 'manual', 'settlement_delay_days' => 0, 'settlement_terms' => 'Retain school proceeds pending reviewed internal settlement.']);
        $this->product = $catalogues->product($this->admin, $this->productInput());
        $catalogues->stock($this->admin, ['operation_id' => (string) Str::uuid(), 'product_id' => $this->product->id, 'source_reference' => 'SYNTHETIC-COUNTED-ORDER-STOCK', 'kind' => 'receipt', 'units_delta' => 10, 'private_reason' => 'Synthetic actually counted goods for orders']);
        $this->orders = app(VendorOrders::class);
    }

    private function productInput(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'vendor_id' => $this->vendor->id, 'category_id' => $this->vendor->category_id, 'sku' => 'SYNTHETIC-SANDWICH', 'name' => 'Synthetic sandwich', 'unit_label' => 'each', 'price' => '12.50', 'vat_kind' => 'none', 'vat_rate_bps' => 0, 'tracks_stock' => true, 'status' => 'active'], $changes);
    }

    private function cart(int $quantity = 2, ?int $wallet = null): array
    {
        return ['vendor_id' => $this->vendor->id, 'wallet_id' => $wallet ?? $this->wallet->id, 'lines' => [['product_id' => $this->product->id, 'quantity' => $quantity]]];
    }

    private function buy(?User $actor = null, ?array $cart = null): VendorOrder
    {
        $actor ??= $this->parent;
        $quote = $this->orders->quote($actor, $cart ?? $this->cart());

        return $this->orders->purchase($actor, ['operation_id' => (string) Str::uuid(), 'quote' => $quote['quote'], 'confirmed' => true]);
    }

    private function refuses(callable $work, int $status): void
    {
        try {
            $work();
            $this->fail('Expected checkout refusal');
        } catch (ValidationException) {
            $this->assertSame(422, $status);
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($status, $e->getStatusCode(), $e->getMessage());
        }
    }

    public function test_reviewed_purchase_moves_original_wallet_to_vendor_and_consumes_stock_once(): void
    {
        $quote = $this->orders->quote($this->parent, $this->cart() + ['amount_cents' => 1]);
        $this->assertSame(2500, $quote['amount_cents']);
        $input = ['operation_id' => (string) Str::uuid(), 'quote' => $quote['quote'], 'confirmed' => true];
        $order = $this->orders->purchase($this->parent, $input);
        $this->assertSame($order->id, $this->orders->purchase($this->parent, $input)->id);
        $this->assertSame('VO-000001', $order->order_number);
        $this->assertSame('ready', $this->orders->state($order));
        $this->assertSame(42500, app(LedgerService::class)->balance($this->wallet->ledger_account_id)->cents);
        $this->assertSame(2500, app(LedgerService::class)->balance($this->vendor->ledger_account_id)->cents);
        $this->assertSame(8, VendorStockBalance::sole()->on_hand);
        $this->assertSame(1, VendorOrder::count());
        $this->assertSame(1, VendorOrderLine::count());
        $this->assertSame(2, VendorStockEvent::count());
        $this->assertTrue(VendorCatalogueReconciliation::healthy(app(VendorCatalogueReconciliation::class)->inspect()));
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        $this->assertTrue(VendorOrderReconciliation::healthy(app(VendorOrderReconciliation::class)->inspect()));
        $this->assertSame('committed', app(WalletOperations::class)->resolve($this->parent, $input['operation_id'])['status']);
        $this->assertStringNotContainsString('private_qr_token', $order->toJson());
    }

    public function test_cancellation_restores_only_original_wallet_counted_stock_and_allowance_once(): void
    {
        $order = $this->buy();
        $input = ['operation_id' => (string) Str::uuid(), 'private_reason' => 'Synthetic change before collection', 'confirmed' => true];
        $this->orders->cancel($this->parent, $order->id, $input);
        $this->orders->cancel($this->parent, $order->id, $input);
        $this->assertSame('cancelled', $this->orders->state($order));
        $this->assertSame(45000, app(LedgerService::class)->balance($this->wallet->ledger_account_id)->cents);
        $this->assertSame(0, app(LedgerService::class)->balance($this->vendor->ledger_account_id)->cents);
        $this->assertSame(10, VendorStockBalance::sole()->on_hand);
        $this->assertSame(1, VendorOrderAction::count());
        $this->assertTrue(VendorOrderReconciliation::healthy(app(VendorOrderReconciliation::class)->inspect()));
        $this->assertSame(2500, $this->orders->quote($this->parent, $this->cart())['amount_cents']);
        $this->assertTrue(VendorCatalogueReconciliation::healthy(app(VendorCatalogueReconciliation::class)->inspect()));
    }

    public function test_scan_or_typed_collection_is_single_use_and_prevents_buyer_cancellation(): void
    {
        $order = $this->buy();
        $input = ['operation_id' => (string) Str::uuid(), 'qr_token' => $order->private_qr_token, 'confirmed' => true];
        $this->orders->collect($this->admin, $this->vendor->id, $input);
        $this->assertSame($order->id, $this->orders->collect($this->admin, $this->vendor->id, $input)->id);
        $this->assertSame('collected', $this->orders->state($order));
        $this->refuses(fn () => $this->orders->collect($this->admin, $this->vendor->id, ['operation_id' => (string) Str::uuid(), 'order_number' => $order->order_number, 'confirmed' => true]), 409);
        $this->refuses(fn () => $this->orders->cancel($this->parent, $order->id, ['operation_id' => (string) Str::uuid(), 'private_reason' => 'Already collected', 'confirmed' => true]), 409);
        $this->refuses(fn () => $this->orders->collect(User::findOrFail($this->schoolA->ids['tara_user']), $this->vendor->id, $input), 403);
        $this->assertSame(8, VendorStockBalance::sole()->on_hand);
    }

    public function test_learner_purchase_needs_guardian_limits_categories_and_daily_headroom(): void
    {
        $this->refuses(fn () => $this->orders->quote($this->child, $this->cart(1, $this->childWallet->id)), 422);
        app(Wallets::class)->controls($this->parent, $this->childWallet->id, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'daily_spend_cents' => 2500, 'all_categories' => false, 'categories' => ['books']]);
        $this->refuses(fn () => $this->orders->quote($this->child, $this->cart(1, $this->childWallet->id)), 422);
        app(Wallets::class)->controls($this->parent, $this->childWallet->id, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'daily_spend_cents' => 2500, 'all_categories' => false, 'categories' => ['food']]);
        $order = $this->buy($this->child, $this->cart(2, $this->childWallet->id));
        $this->assertSame($this->schoolA->ids['asha'], $order->learner_id);
        $this->refuses(fn () => $this->orders->quote($this->child, $this->cart(1, $this->childWallet->id)), 422);
        $this->assertSame(2500, app(LedgerService::class)->balance($this->childWallet->ledger_account_id)->cents);
    }

    public function test_price_change_and_expired_quote_require_new_review_but_committed_uuid_replays(): void
    {
        $quote = $this->orders->quote($this->parent, $this->cart());
        app(VendorCatalogues::class)->product($this->admin, $this->productInput(['id' => $this->product->id, 'revision' => 1, 'price' => '13.00']));
        $input = ['operation_id' => (string) Str::uuid(), 'quote' => $quote['quote'], 'confirmed' => true];
        $this->refuses(fn () => $this->orders->purchase($this->parent, $input), 409);
        $fresh = $this->orders->quote($this->parent, $this->cart());
        $input['quote'] = $fresh['quote'];
        $order = $this->orders->purchase($this->parent, $input);
        $this->travel(16)->minutes();
        $this->assertSame($order->id, $this->orders->purchase($this->parent, $input)->id);
        $input['operation_id'] = (string) Str::uuid();
        $this->refuses(fn () => $this->orders->purchase($this->parent, $input), 409);
        $this->assertSame(1, VendorOrder::count());
    }

    public function test_wrong_owner_school_and_revoked_guardian_cannot_spend_or_replay(): void
    {
        $quote = $this->orders->quote($this->parent, $this->cart());
        $this->refuses(fn () => $this->orders->purchase(User::findOrFail($this->schoolA->ids['tara_user']), ['operation_id' => (string) Str::uuid(), 'quote' => $quote['quote'], 'confirmed' => true]), 403);
        app(CurrentSchool::class)->run(2, function (): void {
            foreach (['vendors', 'wallet'] as $module) {
                SchoolModule::updateOrCreate(['module' => $module], ['in_package' => true, 'enabled' => true, 'read_only' => false]);
            }
            app(ModuleGate::class)->forget();
            $this->refuses(fn () => $this->orders->quote(User::findOrFail($this->schoolB->ids['farid_user']), $this->cart()), 404);
        });
        app(Wallets::class)->controls($this->parent, $this->childWallet->id, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'daily_spend_cents' => 2500, 'all_categories' => true, 'categories' => []]);
        $quote = $this->orders->quote($this->parent, $this->cart(1, $this->childWallet->id));
        GuardianLink::where('guardian_user_id', $this->parent->id)->update(['status' => 'revoked']);
        $this->refuses(fn () => $this->orders->purchase($this->parent, ['operation_id' => (string) Str::uuid(), 'quote' => $quote['quote'], 'confirmed' => true]), 404);
        $this->assertSame(0, VendorOrder::count());
        $this->assertSame(10, VendorStockBalance::sole()->on_hand);
    }

    public function test_insufficient_stock_and_balance_never_leave_partial_postings_or_orders(): void
    {
        $this->refuses(fn () => $this->orders->quote($this->parent, $this->cart(11)), 409);
        $quote = $this->orders->quote($this->parent, $this->cart());
        app(VendorCatalogues::class)->stock($this->admin, ['operation_id' => (string) Str::uuid(), 'product_id' => $this->product->id, 'source_reference' => 'SYNTHETIC-WASTE', 'kind' => 'waste', 'units_delta' => -9, 'private_reason' => 'Actual synthetic damaged stock removed']);
        $entries = DB::table('ledger_entries')->count();
        $this->refuses(fn () => $this->orders->purchase($this->parent, ['operation_id' => (string) Str::uuid(), 'quote' => $quote['quote'], 'confirmed' => true]), 409);
        $this->assertSame($entries, DB::table('ledger_entries')->count());
        $this->assertSame(0, VendorOrder::count());
        $this->assertSame(45000, app(LedgerService::class)->balance($this->wallet->ledger_account_id)->cents);
        $this->assertSame(1, VendorStockBalance::sole()->on_hand);
    }

    public function test_order_roots_lines_actions_and_stock_events_are_sql_immutable(): void
    {
        $order = $this->buy();
        foreach ([fn () => DB::table('vendor_orders')->where('id', $order->id)->update(['amount_cents' => 1]), fn () => DB::table('vendor_order_lines')->update(['quantity' => 99]), fn () => DB::table('vendor_stock_events')->delete()] as $work) {
            try {
                $work();
                $this->fail('Original order and stock roots must be immutable');
            } catch (QueryException $e) {
                $this->assertSame('45000', $e->errorInfo[0]);
            }
        }
        $this->assertSame(2500, $order->fresh()->amount_cents);
    }

    public function test_current_adult_with_a_historical_student_row_retains_own_controls_and_private_wallet(): void
    {
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $old = Students::findOrFail($this->schoolA->ids['asha'])->replicate();
        $old->forceFill(['user_id' => $teacher->id, 'admission_no' => 'HISTORICAL-TEACHER'])->save();
        GuardianLink::updateOrCreate(['learner_id' => $old->id, 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'can_approve' => true, 'can_see_fees' => false, 'relationship' => 'guardian']);
        $wallet = app(Wallets::class)->open($teacher, ['operation_id' => (string) Str::uuid(), 'owner_user_id' => $teacher->id]);
        $summary = app(Wallets::class)->statement($teacher, $wallet->id);
        $this->assertFalse($summary['is_child']);
        $this->assertTrue($summary['can_manage_limits']);
        app(Wallets::class)->controls($teacher, $wallet->id, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'daily_spend_cents' => 1000, 'all_categories' => true, 'categories' => []]);
        $this->refuses(fn () => app(Wallets::class)->statement($this->parent, $wallet->id), 404);
    }

    public function test_vat_rounding_and_public_receipt_retain_original_seller_without_settlement_terms(): void
    {
        $this->vendor = app(VendorCatalogues::class)->vendor($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'category_id' => $this->vendor->category_id, 'name' => 'Synthetic VAT seller', 'kind' => 'school', 'status' => 'active', 'legal_name' => 'Original synthetic VAT merchant', 'legal_address' => '1 Synthetic Trading Road', 'vat_registered' => true, 'vat_number' => 'SYNTHETIC-VAT', 'refund_bearer' => 'school', 'chargeback_bearer' => 'school', 'settlement_method' => 'school_internal', 'settlement_cycle' => 'manual', 'settlement_delay_days' => 0, 'settlement_terms' => 'Private synthetic commercial settlement agreement']);
        $this->product = app(VendorCatalogues::class)->product($this->admin, $this->productInput(['price' => '10.01', 'vat_kind' => 'standard', 'vat_rate_bps' => 1500, 'tracks_stock' => false]));
        $quote = $this->orders->quote($this->parent, $this->cart(1));
        $this->assertSame(1001, $quote['amount_cents']);
        $this->assertSame(131, $quote['vat_cents']);
        $this->assertArrayNotHasKey('settlement_terms', $quote['seller']);
        $order = $this->buy($this->parent, $this->cart(1));
        $receipt = app(VendorStorefronts::class)->receipt($this->parent, $order->id);
        $this->assertSame(870, $receipt['net_cents']);
        $this->assertSame(131, $receipt['lines'][0]['vat_cents']);
        $this->assertSame('Original synthetic VAT merchant', $receipt['seller']['legal_name']);
        $this->assertArrayNotHasKey('settlement_terms', $receipt['seller']);
        $this->assertStringNotContainsString('Private synthetic commercial', json_encode($receipt, JSON_THROW_ON_ERROR));
    }

    public function test_explicit_free_products_use_no_zero_money_postings_and_restore_stock_on_cancellation(): void
    {
        app(VendorCatalogues::class)->product($this->admin, $this->productInput(['id' => $this->product->id, 'revision' => 1, 'price' => '0.00']));
        $entries = DB::table('ledger_entries')->count();
        $order = $this->buy();
        $this->assertSame(0, $order->amount_cents);
        $this->assertNull($order->ledger_entry_id);
        $this->assertSame(8, VendorStockBalance::sole()->on_hand);
        $this->orders->cancel($this->parent, $order->id, ['operation_id' => (string) Str::uuid(), 'private_reason' => 'Free order cancelled before collection', 'confirmed' => true]);
        $this->assertSame(10, VendorStockBalance::sole()->on_hand);
        $this->assertSame($entries, DB::table('ledger_entries')->count());
    }

    public function test_storefront_api_purchase_receipt_and_replay_recheck_current_guardian_authority(): void
    {
        app(Wallets::class)->controls($this->parent, $this->childWallet->id, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'daily_spend_cents' => 2500, 'all_categories' => true, 'categories' => []]);
        Sanctum::actingAs($this->parent);
        $headers = ['X-School-Id' => '1'];
        $this->getJson('/api/v2/commerce/vendors', $headers)->assertOk()->assertJsonPath('data.items.0.name', 'Synthetic school tuckshop');
        $this->getJson('/api/v2/commerce/vendors/'.$this->vendor->id, $headers)->assertOk()->assertJsonPath('data.items.0.price_cents', 1250);
        $quote = $this->postJson('/api/v2/commerce/quotes', $this->cart(1, $this->childWallet->id), $headers)->assertOk()->json('data.quote');
        $input = ['operation_id' => (string) Str::uuid(), 'quote' => $quote, 'confirmed' => true];
        $headers['Idempotency-Key'] = $input['operation_id'];
        $response = $this->postJson('/api/v2/commerce/orders', $input, $headers)->assertOk()->assertJsonPath('data.amount_cents', 1250)->assertJsonPath('data.status', 'ready');
        $id = $response->json('data.id');
        $this->postJson('/api/v2/commerce/orders', $input, $headers)->assertOk()->assertJsonPath('data.id', $id);
        $this->getJson('/api/v2/commerce/orders/'.$id, ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.wallet_id', $this->childWallet->id);
        GuardianLink::where('guardian_user_id', $this->parent->id)->update(['status' => 'revoked']);
        $this->postJson('/api/v2/commerce/orders', $input, $headers)->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson('/api/v2/commerce/orders/'.$id, ['X-School-Id' => '1'])->assertNotFound();
        $this->assertSame(1, VendorOrder::count());
        $this->assertSame(9, VendorStockBalance::sole()->on_hand);
    }

    public function test_finance_counter_forms_validate_once_and_can_return_a_departed_holders_uncollected_order(): void
    {
        $order = $this->buy();
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $this->actingAs($this->admin, 'web')->get('/vendor-orders?vendor_id='.$this->vendor->id)->assertOk()->assertSee('Validate order collection');
        $this->get('/vendor-orders/'.$order->id)->assertOk()->assertSee('Synthetic sandwich')->assertDontSee($order->private_qr_token);
        SchoolMembership::where('user_id', $this->parent->id)->where('role', 'Parent')->update(['status' => 'inactive']);
        $this->post('/vendor-orders/'.$order->id.'/cancel', ['operation_id' => (string) Str::uuid(), 'private_reason' => 'Verified original holder departed before collection', 'confirmed' => true, 'verified_uncollected' => true] + $bind)->assertRedirect('/vendor-orders/'.$order->id);
        $this->assertSame('cancelled', $this->orders->state($order));
        $this->assertSame(45000, app(LedgerService::class)->balance($this->wallet->ledger_account_id)->cents);
        $this->assertSame(10, VendorStockBalance::sole()->on_hand);
        $this->assertTrue(VendorOrderReconciliation::healthy(app(VendorOrderReconciliation::class)->inspect()));
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']), 'web')->get('/vendor-orders')->assertForbidden();
    }

    public function test_counter_accepts_the_private_wallet_qr_protocol_and_records_collection_before_handover(): void
    {
        $order = $this->buy();
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $collect = ['operation_id' => (string) Str::uuid(), 'qr_token' => 'navigateed:order:'.$order->private_qr_token, 'confirmed' => true] + $bind;
        $this->actingAs($this->admin, 'web')->post('/vendor-orders/vendors/'.$this->vendor->id.'/collect', $collect)->assertRedirect('/vendor-orders/'.$order->id);
        $this->get('/vendor-orders/'.$order->id)->assertOk()->assertSee('Hand over the listed items once.')->assertDontSee('Validate this order collection');
        $this->post('/vendor-orders/vendors/'.$this->vendor->id.'/collect', $collect)->assertRedirect('/vendor-orders/'.$order->id);
        $this->get('/vendor-orders/'.$order->id)->assertOk()->assertSee('do not hand over goods again.')->assertDontSee('Hand over the listed items once.');
        $this->assertSame('collected', $this->orders->state($order));
        $this->assertSame(8, VendorStockBalance::sole()->on_hand);
    }
}
