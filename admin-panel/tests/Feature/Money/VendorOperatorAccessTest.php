<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorOperator;
use App\Models\Commerce\VendorOrder;
use App\Models\Commerce\VendorOrderAction;
use App\Models\Commerce\VendorProduct;
use App\Models\Money\WalletAccount;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\PolicyAcceptance;
use App\Models\User;
use App\Policies\Money\WalletPolicy;
use App\Services\Commerce\VendorCatalogues;
use App\Services\Commerce\VendorOperators;
use App\Services\Commerce\VendorOrders;
use App\Services\Money\WalletDeposits;
use App\Services\Money\Wallets;
use App\Services\Money\WalletSettings;
use App\Services\Money\WalletTransfers;
use App\Services\ParentPolicyService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class VendorOperatorAccessTest extends TestCase
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
        $this->operator = app(VendorOperators::class)->provision($this->admin, ['operation_id' => (string) Str::uuid(), 'first_name' => 'Synthetic', 'last_name' => 'Vendor', 'email' => 'vendor.synthetic@example.test', 'initial_password' => 'SyntheticVendor2026', 'confirmed_operator_identity' => true]);
        $this->operator->forceFill(['password_change_required' => false])->save();
        ParentPolicyService::acceptCurrent($this->operator);
        app(VendorOperators::class)->bind($this->admin, $this->binding());
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

    private function binding(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'vendor_id' => $this->vendor->id, 'user_id' => $this->operator->id, 'revision' => 0, 'status' => 'active', 'private_reason' => 'Synthetic operator verified for this counter', 'confirmed_operator_identity' => true], $changes);
    }

    private function login(array $changes = [])
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->postJson('/api/v2/vendor/sign-in', array_replace(['school' => School::findOrFail(1)->slug, 'email' => $this->operator->email, 'password' => 'SyntheticVendor2026'], $changes));
    }

    private function authenticateVendor(): string
    {
        $token = $this->login()->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data.token');
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $this->operator->id]);

        return $token;
    }

    public function test_vendor_gets_only_explicit_counter_and_redacted_original_order_receipts(): void
    {
        $order = $this->buy();
        $this->authenticateVendor();
        $base = '/api/v2/vendor/counters/'.$this->vendor->id;
        $this->getJson('/api/v2/vendor/session')->assertOk()->assertJsonPath('data.counters.0.id', $this->vendor->id)->assertDontSee('wallet');
        $response = $this->getJson($base.'/orders')->assertOk()->assertJsonPath('data.items.0.order_number', $order->order_number)->assertJsonPath('data.items.0.amount_cents', 2500);
        foreach (['wallet_account_id', 'wallet_owner_id', 'actor_id', 'private_qr_token', 'qr_hash', 'seller_snapshot', 'bank_instructions'] as $field) {
            $this->assertStringNotContainsString('"'.$field.'"', $response->getContent());
        }
        $this->postJson($base.'/lookup', ['qr_token' => 'navigateed:order:'.$order->private_qr_token])->assertOk()->assertJsonPath('data.id', $order->id)->assertJsonPath('data.can_collect', true);
    }

    public function test_vendor_cannot_borrow_staff_family_wallet_or_finance_routes_even_after_a_staff_role_is_granted(): void
    {
        $this->operator->assignRole('Teacher');
        app(MembershipService::class)->grant($this->operator, 1, 'Teacher');
        $this->authenticateVendor();
        foreach (['/api/v2/me/context', '/api/v2/wallets', '/api/teacher/get-user-list', '/api/v2/academics/reference'] as $url) {
            $response = $this->getJson($url);
            $this->assertSame(403, $response->status(), $url.' '.$response->getContent());
        }
        $this->flushHeaders();
        $this->app['auth']->shouldUse('web');
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->operator, 'web')->withSession(['portal_app' => AppTokens::VENDOR]);
        foreach (['/vendor-orders', '/vendor-catalogues', '/home'] as $url) {
            $this->getJson($url)->assertForbidden();
        }
    }

    public function test_other_profiles_and_other_app_tokens_cannot_borrow_counter_access(): void
    {
        $token = $this->operator->createToken(AppTokens::TEACHER)->plainTextToken;
        $this->withToken($token)->withHeader('X-School-Id', '1')->getJson('/api/v2/vendor/counters/'.$this->vendor->id.'/orders')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->withToken($this->parent->createToken('parent-app')->plainTextToken)->getJson('/api/v2/vendor/session')->assertForbidden();
        $this->parent->forceFill(['password' => Hash::make('SyntheticVendor2026')])->save();
        $this->login(['email' => $this->parent->email])->assertForbidden();
    }

    public function test_wrong_counter_school_owner_and_revoked_binding_refuse_reads_and_cached_collection(): void
    {
        $order = $this->buy();
        $this->authenticateVendor();
        $base = '/api/v2/vendor/counters/'.$this->vendor->id;
        $key = (string) Str::uuid();
        $body = ['operation_id' => $key, 'order_number' => $order->order_number, 'confirmed' => true];
        $this->withHeader('Idempotency-Key', $key)->postJson($base.'/collect', $body)->assertOk()->assertJsonPath('data.status', 'collected');
        $this->getJson('/api/v2/vendor/counters/999999/orders')->assertNotFound();
        $this->withHeader('X-School-Id', '2')->getJson($base.'/orders')->assertForbidden();
        $this->withHeader('X-School-Id', '1');
        app(VendorOperators::class)->bind($this->admin, $this->binding(['revision' => 1, 'status' => 'revoked']));
        $this->getJson($base.'/orders')->assertForbidden();
        $this->withHeader('Idempotency-Key', $key)->postJson($base.'/collect', $body)->assertForbidden();
        $this->assertSame(1, VendorOrderAction::count());
    }

    public function test_operator_collection_has_one_terminal_action_and_original_operation_recovery(): void
    {
        $order = $this->buy();
        $this->authenticateVendor();
        $base = '/api/v2/vendor/counters/'.$this->vendor->id;
        $key = (string) Str::uuid();
        $body = ['operation_id' => $key, 'order_number' => $order->order_number, 'confirmed' => true];
        $this->withHeader('Idempotency-Key', $key)->postJson($base.'/collect', $body)->assertOk();
        $this->postJson($base.'/collect', $body)->assertOk()->assertHeader('Idempotent-Replayed', 'true')->assertJsonPath('data.newly_collected', false);
        $this->postJson($base.'/operations/'.$key.'/resolve')->assertOk()->assertJsonPath('data.status', 'committed')->assertJsonPath('data.order.id', $order->id);
        $new = (string) Str::uuid();
        $this->withHeader('Idempotency-Key', $new)->postJson($base.'/collect', array_replace($body, ['operation_id' => $new]))->assertConflict();
        $this->assertSame(1, VendorOrderAction::count());
        $this->assertSame(42500, app(LedgerService::class)->balance($this->wallet->ledger_account_id)->cents);
    }

    public function test_resolving_an_unknown_collection_abandons_it_and_refuses_a_late_handover(): void
    {
        $order = $this->buy();
        $this->authenticateVendor();
        $base = '/api/v2/vendor/counters/'.$this->vendor->id;
        $key = (string) Str::uuid();
        $this->postJson($base.'/operations/'.$key.'/resolve')->assertOk()->assertJsonPath('data.status', 'abandoned');
        $this->withHeader('Idempotency-Key', $key)->postJson($base.'/collect', ['operation_id' => $key, 'order_number' => $order->order_number, 'confirmed' => true])->assertConflict();
        $this->assertSame(0, VendorOrderAction::count());
    }

    public function test_vendor_session_rotation_and_logout_preserve_other_app_tokens_after_membership_ends(): void
    {
        $teacher = $this->operator->createToken('teacher-app');
        $parent = $this->operator->createToken('parent-app');
        $first = $this->login()->assertOk()->json('data.token');
        $second = $this->authenticateVendor();
        $this->assertNotSame($first, $second);
        $this->assertSame(3, $this->operator->tokens()->count());
        SchoolMembership::where('user_id', $this->operator->id)->update(['status' => 'ended']);
        $this->getJson('/api/v2/vendor/session')->assertForbidden();
        $this->postJson('/api/v2/vendor/sign-out')->assertOk();
        $this->assertSame(2, $this->operator->tokens()->count());
        $this->assertNotNull($teacher->accessToken->fresh());
        $this->assertNotNull($parent->accessToken->fresh());
    }

    public function test_first_sign_in_requires_policy_and_password_setup_before_any_token_is_issued(): void
    {
        $this->operator->forceFill(['password_change_required' => true])->save();
        PolicyAcceptance::where('user_id', $this->operator->id)->delete();
        $reply = $this->login()->assertStatus(409)->assertJsonPath('data.authenticated', false)->assertJsonPath('data.token', null)->assertJsonPath('data.password_change_required', true);
        $accept = [];
        foreach ($reply->json('data.documents') as $document) {
            $accept[$document['document']] = $document['version'];
        }
        $this->login(['new_password' => 'NewSyntheticVendor2026', 'new_password_confirmation' => 'NewSyntheticVendor2026', 'acceptances' => $accept])->assertOk()->assertJsonPath('data.profile.counters.0.id', $this->vendor->id);
        $this->assertFalse($this->operator->fresh()->password_change_required);
    }

    public function test_read_only_and_revoked_identity_retain_bound_history_but_stop_collection(): void
    {
        $order = $this->buy();
        $this->authenticateVendor();
        $base = '/api/v2/vendor/counters/'.$this->vendor->id;
        app(ModuleGate::class)->change('vendors', false, $this->admin);
        $this->getJson($base.'/orders/'.$order->id)->assertOk()->assertJsonPath('data.can_collect', false);
        $key = (string) Str::uuid();
        $this->withHeader('Idempotency-Key', $key)->postJson($base.'/collect', ['operation_id' => $key, 'order_number' => $order->order_number, 'confirmed' => true])->assertForbidden();
        $this->assertSame(0, VendorOrderAction::count());
    }

    public function test_other_valid_counter_orders_and_qr_codes_are_not_visible_or_collectable(): void
    {
        $other = app(VendorCatalogues::class)->vendor($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'category_id' => $this->vendor->category_id, 'name' => 'Other assigned merchant', 'kind' => 'school', 'status' => 'active', 'legal_name' => 'Other school merchant', 'vat_registered' => false, 'refund_bearer' => 'school', 'chargeback_bearer' => 'school', 'settlement_method' => 'school_internal', 'settlement_cycle' => 'manual', 'settlement_delay_days' => 0, 'settlement_terms' => 'Retain original proceeds pending reviewed settlement.']);
        $product = app(VendorCatalogues::class)->product($this->admin, $this->productInput(['vendor_id' => $other->id, 'sku' => 'OTHER-COUNTER-01', 'tracks_stock' => false]));
        $quote = $this->orders->quote($this->parent, ['vendor_id' => $other->id, 'wallet_id' => $this->wallet->id, 'lines' => [['product_id' => $product->id, 'quantity' => 1]]]);
        $order = $this->orders->purchase($this->parent, ['operation_id' => (string) Str::uuid(), 'quote' => $quote['quote'], 'confirmed' => true]);
        $this->authenticateVendor();
        $base = '/api/v2/vendor/counters/'.$this->vendor->id;
        $this->getJson('/api/v2/vendor/counters/'.$other->id.'/orders')->assertNotFound();
        $this->getJson($base.'/orders/'.$order->id)->assertNotFound();
        $this->postJson($base.'/lookup', ['order_number' => $order->order_number])->assertNotFound();
        $key = (string) Str::uuid();
        $this->withHeader('Idempotency-Key', $key)->postJson($base.'/collect', ['operation_id' => $key, 'qr_token' => $order->private_qr_token, 'confirmed' => true])->assertNotFound();
        $this->assertSame(0, VendorOrderAction::count());
    }

    public function test_finance_provision_and_assignment_are_bound_private_no_money_operations(): void
    {
        $before = DB::table('ledger_entries')->count();
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $body = ['operation_id' => (string) Str::uuid(), 'first_name' => 'Reviewed', 'last_name' => 'Operator', 'email' => 'another.vendor@example.test', 'initial_password' => 'SyntheticTemporary2026', 'confirmed_operator_identity' => true] + $bind;
        $this->actingAs($this->admin, 'web')->get('/vendor-operators')->assertOk()->assertSee('Create dedicated vendor identity');
        $this->post('/vendor-operators/provision', $body)->assertRedirect('/vendor-operators')->assertSessionMissing('_old_input');
        $this->post('/vendor-operators/provision', $body)->assertRedirect('/vendor-operators');
        $operator = User::where('email', $body['email'])->sole();
        $this->assertTrue($operator->password_change_required);
        $this->assertSame(['Vendor'], app(WalletPolicy::class)->roles($operator));
        $this->post('/vendor-operators/provision', array_replace($body, ['operation_id' => (string) Str::uuid(), 'email' => 'invalid-email']))->assertUnprocessable()->assertDontSee('SyntheticTemporary2026')->assertSessionMissing('_old_input');
        $this->post('/vendor-operators/bind', $this->binding(['user_id' => (string) $operator->id, 'vendor_id' => (string) $this->vendor->id, 'revision' => '0', 'confirmed_operator_identity' => '1']) + $bind)->assertRedirect('/vendor-operators');
        $this->post('/vendor-operators/bind', array_replace($this->binding(['user_id' => $operator->id]), ['expected_account_id' => $this->admin->id, 'expected_school_id' => 2]))->assertForbidden();
        $this->assertSame($before, DB::table('ledger_entries')->count());
        $this->assertSame(1, VendorOperator::where('user_id', $operator->id)->count());
    }

    public function test_web_setup_domain_refusal_does_not_flash_credentials_or_issue_a_session(): void
    {
        $this->operator->forceFill(['password_change_required' => true])->save();
        $this->post('/vendor-counter/sign-in', ['school' => School::findOrFail(1)->slug, 'email' => $this->operator->email, 'password' => 'SyntheticVendor2026', 'new_password' => 'SyntheticVendor2026', 'new_password_confirmation' => 'SyntheticVendor2026'])
            ->assertUnprocessable()->assertHeader('Cache-Control', 'no-store, private')->assertSessionMissing('_old_input')->assertDontSee('SyntheticVendor2026');
        $this->assertGuest('web');
        $this->assertSame(0, $this->operator->tokens()->count());
        $this->assertTrue($this->operator->fresh()->password_change_required);
    }

    public function test_historical_vendor_role_does_not_reopen_an_adult_wallet_to_an_old_guardian(): void
    {
        $this->child->removeRole('Student');
        app(MembershipService::class)->end($this->child, 1, 'Student');
        $this->child->assignRole('Vendor');
        app(MembershipService::class)->grant($this->child, 1, 'Vendor');
        $this->child->removeRole('Vendor');
        app(MembershipService::class)->end($this->child, 1, 'Vendor');
        $index = app(Wallets::class)->accessible($this->parent);
        $this->assertNotContains((int) $this->child->id, array_column($index['owners'], 'owner_user_id'));
        $this->assertSame(5000, app(LedgerService::class)->balance($this->childWallet->ledger_account_id)->cents);
    }
}
