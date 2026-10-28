<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorOrder;
use App\Models\Commerce\VendorProduct;
use App\Models\Commerce\VendorSettlement;
use App\Models\Commerce\VendorSettlementOutcome;
use App\Models\Money\WalletAccount;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\User;
use App\Services\Commerce\VendorCatalogues;
use App\Services\Commerce\VendorOrderReconciliation;
use App\Services\Commerce\VendorOrders;
use App\Services\Commerce\VendorSettlementReconciliation;
use App\Services\Commerce\VendorSettlements;
use App\Services\Money\ExternalDisbursements;
use App\Services\Money\WalletDeposits;
use App\Services\Money\Wallets;
use App\Services\Money\WalletSettings;
use App\Services\Money\WalletTransfers;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Ledger\CentsColumns;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class VendorSettlementsTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private User $operator;

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

    private function reviewer(): User
    {
        $user = User::create(['first_name' => 'Synthetic', 'last_name' => 'Independent reviewer', 'email' => 'reviewer.'.Str::uuid().'@example.test', 'password' => Hash::make('SyntheticReview2026'), 'password_change_required' => false, 'status' => 1]);
        $user->assignRole('Super Admin');
        app(MembershipService::class)->grant($user, 1, 'Super Admin');

        return $user;
    }

    private function collected(): VendorOrder
    {
        $order = $this->buy(null, $this->cart(1));

        return $this->orders->collect($this->admin, $this->vendor->id, ['operation_id' => (string) Str::uuid(), 'order_number' => $order->order_number, 'confirmed' => true]);
    }

    private function batch(array $orders, array $extra = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'vendor_id' => $this->vendor->id, 'vendor_revision' => $this->vendor->revision, 'period_from' => now()->toDateString(), 'period_to' => now()->toDateString(), 'order_ids' => array_map(fn ($o) => $o->id, $orders), 'confirmed_original_terms' => true], $extra);
    }

    private function confirmation(array $extra = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'confirmed' => true], $extra);
    }

    private function payout(array $extra = []): array
    {
        return $this->confirmation(array_replace(['paid_on' => now()->toDateString(), 'private_evidence' => 'Synthetic independently reviewed original settlement evidence', 'verified_original_vendor' => true, 'verified_actual_payout' => true, 'payout_reference' => 'SYNTHETIC-ORIGINAL-VENDOR-PAYOUT'], $extra));
    }

    private function merchant(string $method = 'eft', string $price = '12.50', bool $vat = false, string $kind = 'independent'): void
    {
        $this->vendor = app(VendorCatalogues::class)->vendor($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'category_id' => $this->vendor->category_id, 'name' => 'Synthetic independent merchant', 'legal_name' => 'Synthetic original merchant', 'kind' => $kind, 'status' => 'active', 'vat_registered' => $vat, 'vat_number' => $vat ? 'SYNTHETIC-VAT-2026' : null, 'legal_address' => $vat ? 'Synthetic merchant tax address' : null, 'refund_bearer' => $kind === 'school' ? 'school' : 'vendor', 'chargeback_bearer' => $kind === 'school' ? 'school' : 'vendor', 'settlement_method' => $method, 'settlement_cycle' => 'manual', 'settlement_delay_days' => 0, 'settlement_terms' => 'Original reviewed independent merchant agreement', 'private_bank_instructions' => $method === 'eft' ? 'Synthetic verified original merchant bank account' : null]);
        $this->product = app(VendorCatalogues::class)->product($this->admin, $this->productInput(['vendor_id' => $this->vendor->id, 'sku' => 'INDEPENDENT-ORDER', 'tracks_stock' => false, 'price' => $price, 'vat_kind' => $vat ? 'standard' : 'none', 'vat_rate_bps' => $vat ? 1500 : 0]));
    }

    private function changeVendor(array $changes): void
    {
        $input = array_intersect_key($this->vendor->getAttributes(), VendorCatalogues::rules('vendor'));
        $this->vendor = app(VendorCatalogues::class)->vendor($this->admin, array_replace($input, ['operation_id' => (string) Str::uuid(), 'revision' => $this->vendor->revision, 'private_bank_instructions' => $this->vendor->private_bank_instructions], $changes));
    }

    private function healthy(): void
    {
        $this->assertTrue(VendorSettlementReconciliation::healthy(app(VendorSettlementReconciliation::class)->inspect()), json_encode(app(VendorSettlementReconciliation::class)->inspect()));
        $this->assertTrue(VendorOrderReconciliation::healthy(app(VendorOrderReconciliation::class)->inspect()), json_encode(app(VendorOrderReconciliation::class)->inspect()));
    }

    public function test_internal_settlement_preserves_orders_and_recognises_sales_once_after_independent_approval(): void
    {
        $order = $this->collected();
        $s = app(VendorSettlements::class);
        $before = DB::table('ledger_entries')->count();
        $input = $this->batch([$order]);
        $row = $s->prepare($this->admin, $input);
        $this->assertSame($row->id, $s->prepare($this->admin, $input)->id);
        $this->assertSame($before, DB::table('ledger_entries')->count());
        $this->assertSame(1250, $row->payout_cents);
        $this->assertSame(0, $row->collector_fee_cents);
        $this->assertSame('school', $row->collected_by);
        $this->refuses(fn () => $s->approve($this->admin, $row->id, $this->confirmation()), 403);
        $reviewer = $this->reviewer();
        $approval = $this->confirmation();
        $s->approve($reviewer, $row->id, $approval);
        $s->approve($reviewer, $row->id, $approval);
        $payout = $this->payout();
        $s->finish($this->admin, $row->id, $payout);
        $s->finish($this->admin, $row->id, $payout);
        $this->assertSame('settled', $s->state($row));
        $this->assertSame(1, VendorSettlementOutcome::count());
        $this->assertSame(0, DB::table('financial_disbursements')->count());
        $this->assertSame(0, app(LedgerService::class)->balance($this->vendor->ledger_account_id)->cents);
        $this->assertSame(1250, (int) DB::table('ledger_accounts')->where('reference', 'vendor:sales:ZAR')->value('balance_cents'));
        $this->refuses(fn () => $s->prepare($this->admin, $this->batch([$order])), 409);
        $this->refuses(fn () => $s->finish($this->admin, $row->id, $this->payout()), 409);
        $this->healthy();
    }

    public function test_independent_payout_uses_actual_backing_and_shared_outgoing_identity(): void
    {
        $this->merchant();
        $order = $this->collected();
        $s = app(VendorSettlements::class);
        $row = $s->prepare($this->admin, $this->batch([$order]));
        $s->approve($this->reviewer(), $row->id, $this->confirmation());
        $this->refuses(fn () => $s->finish($this->admin, $row->id, $this->payout(['verified_actual_payout' => false])), 422);
        $s->finish($this->admin, $row->id, $this->payout());
        $this->assertSame(48750, (int) DB::table('ledger_accounts')->where('reference', 'wallet:eft:ZAR')->value('balance_cents'));
        $this->assertSame(1, DB::table('financial_disbursements')->where('domain', 'vendors')->count());
        $this->refuses(fn () => app(ExternalDisbursements::class)->available('eft', 'ZAR', 'synthetic-original-vendor-payout'), 409);
        $second = $s->prepare($this->admin, $this->batch([$this->collected()]));
        $s->approve($this->reviewer(), $second->id, $this->confirmation());
        $this->refuses(fn () => $s->finish($this->admin, $second->id, $this->payout()), 409);
        $this->assertSame(1, VendorSettlementOutcome::count());
        $this->healthy();
    }

    public function test_only_collected_due_same_vendor_orders_can_be_batched_and_terms_revision_is_bound(): void
    {
        $s = app(VendorSettlements::class);
        $ready = $this->buy(null, $this->cart(1));
        $this->refuses(fn () => $s->prepare($this->admin, $this->batch([$ready])), 409);
        $order = $this->collected();
        $this->refuses(fn () => $s->prepare($this->parent, $this->batch([$order])), 403);
        $this->refuses(fn () => app(CurrentSchool::class)->run(2, fn () => $s->prepare($this->admin, $this->batch([$order]))), 403);
        $this->refuses(fn () => $s->prepare($this->admin, $this->batch([$order], ['vendor_revision' => $this->vendor->revision + 1])), 409);
        $this->changeVendor(['settlement_delay_days' => 1]);
        $this->assertSame([], $s->eligible($this->admin, $this->vendor->id, ['period_from' => now()->toDateString(), 'period_to' => now()->toDateString()]));
        $this->refuses(fn () => $s->prepare($this->admin, $this->batch([$order])), 409);
        $this->assertSame(0, VendorSettlement::count());
    }

    public function test_zero_value_batch_never_posts_zero_money_or_creates_outside_payment(): void
    {
        $this->merchant('eft', '0.00');
        $s = app(VendorSettlements::class);
        $row = $s->prepare($this->admin, $this->batch([$this->collected()]));
        $before = DB::table('ledger_entries')->count();
        $s->approve($this->reviewer(), $row->id, $this->confirmation());
        $s->finish($this->admin, $row->id, $this->payout(['payout_reference' => null]));
        $this->assertSame($before, DB::table('ledger_entries')->count());
        $this->assertNull(VendorSettlementOutcome::sole()->ledger_entry_id);
        $this->assertSame(0, DB::table('financial_disbursements')->count());
        $this->healthy();
    }

    public function test_prepared_terms_and_sql_history_are_retained_and_payout_requires_approval(): void
    {
        $s = app(VendorSettlements::class);
        $row = $s->prepare($this->admin, $this->batch([$this->collected()]));
        $name = $row->private_vendor_snapshot['name'];
        $this->changeVendor(['name' => 'Changed merchant display']);
        $this->assertSame($name, $row->fresh()->private_vendor_snapshot['name']);
        $this->refuses(fn () => $s->finish($this->admin, $row->id, $this->payout()), 404);
        foreach (['vendor_settlements', 'vendor_settlement_lines'] as $table) {
            try {
                DB::table($table)->delete();
                $this->fail('Retained rows were deleted');
            } catch (QueryException) {
                $this->assertTrue(true);
            }
        }
        try {
            DB::table('vendor_settlements')->where('id', $row->id)->update(['gross_cents' => 1]);
            $this->fail('Sealed totals were changed');
        } catch (QueryException) {
            $this->assertSame(1250, $row->fresh()->gross_cents);
        }
        $this->healthy();
    }

    public function test_original_vat_is_recognised_separately_from_the_gross_vendor_payout_budget(): void
    {
        $this->merchant('school_internal', '12.50', true, 'school');
        $order = $this->collected();
        $s = app(VendorSettlements::class);
        $row = $s->prepare($this->admin, $this->batch([$order]));
        $this->assertSame(1250, $row->gross_cents);
        $this->assertSame(1087, $row->sales_net_cents);
        $this->assertSame(163, $row->vat_cents);
        $this->assertSame(1250, $row->payout_cents);
        $s->approve($this->reviewer(), $row->id, $this->confirmation());
        $s->finish($this->admin, $row->id, $this->payout());
        $this->assertSame(1087, (int) DB::table('ledger_accounts')->where('reference', 'vendor:sales:ZAR')->value('balance_cents'));
        $this->assertSame(163, (int) DB::table('ledger_accounts')->where('reference', 'vendor:vat:ZAR')->value('balance_cents'));
        $this->healthy();
        // The demo builder writes legacy fixtures outside the cents-aware models.
        CentsColumns::backfill();
        $exit = Artisan::call('ledger:reconcile', ['--school' => 1, '--json' => true]);
        $this->assertSame(0, $exit, Artisan::output());
    }

    public function test_cash_vendor_payout_requires_actual_cash_and_records_its_custodian(): void
    {
        $this->merchant('cash');
        $s = app(VendorSettlements::class);
        $row = $s->prepare($this->admin, $this->batch([$this->collected()]));
        $s->approve($this->reviewer(), $row->id, $this->confirmation());
        $before = DB::table('ledger_entries')->count();
        $this->refuses(fn () => $s->finish($this->admin, $row->id, $this->payout()), 422);
        $this->assertSame($before, DB::table('ledger_entries')->count());
        $this->assertSame(0, VendorSettlementOutcome::count());
        $claim = app(WalletDeposits::class)->claim($this->parent, ['operation_id' => (string) Str::uuid(), 'amount' => '20.00', 'method' => 'cash', 'source_reference' => 'SYNTHETIC-CASH-CLAIM', 'paid_on' => now()->toDateString(), 'evidence' => 'Original synthetic counted cash parent receipt', 'confirmed' => true]);
        app(WalletDeposits::class)->verify($this->admin, $claim->id, ['operation_id' => (string) Str::uuid(), 'amount' => '20.00', 'method' => 'cash', 'source_reference' => 'SYNTHETIC-ACTUAL-CASH', 'received_on' => now()->toDateString(), 'evidence' => 'Actual synthetic counted cash held by this operator', 'verified_original_holder' => true, 'verified_actual_receipt' => true]);
        $s->finish($this->admin, $row->id, $this->payout());
        $this->assertSame((int) $this->admin->id, VendorSettlementOutcome::sole()->cash_operator_id);
        $this->assertSame(750, (int) DB::table('ledger_accounts')->where('reference', 'wallet:cash:ZAR')->value('balance_cents'));
        $this->healthy();
    }
}
