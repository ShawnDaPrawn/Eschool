<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Cards\Card;
use App\Models\Cards\CardChipProfile;
use App\Models\Cards\CardReaderDevice;
use App\Models\Commerce\Ticket;
use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorOrder;
use App\Models\Commerce\VendorOrderAction;
use App\Models\Commerce\VendorProduct;
use App\Models\Commerce\WalletCounterPayment;
use App\Models\Commerce\WalletPaymentIntent;
use App\Models\Money\WalletAccount;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Cards\CardPortraits;
use App\Services\Cards\CardReaders;
use App\Services\Cards\Cards;
use App\Services\Commerce\CounterSpendAuthority;
use App\Services\Commerce\TicketCatalogues;
use App\Services\Commerce\VendorCatalogues;
use App\Services\Commerce\VendorOperators;
use App\Services\Commerce\VendorOrderReconciliation;
use App\Services\Commerce\VendorOrders;
use App\Services\Commerce\VendorPaymentReaders;
use App\Services\Commerce\WalletCardPayments;
use App\Services\Commerce\WalletCounterPayments;
use App\Services\Money\WalletDeposits;
use App\Services\Money\Wallets;
use App\Services\Money\WalletSettings;
use App\Services\Money\WalletTransfers;
use App\Services\ParentPolicyService;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class WalletCounterPaymentsTest extends TestCase
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
        app(ModuleGate::class)->change('wallet_payments', true, $this->admin);
        $this->operator = app(VendorOperators::class)->provision($this->admin, ['operation_id' => (string) Str::uuid(), 'first_name' => 'Synthetic', 'last_name' => 'Vendor', 'email' => 'vendor.synthetic@example.test', 'initial_password' => Str::password(24), 'confirmed_operator_identity' => true]);
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

    private function paymentQr(?User $actor = null, ?array $cart = null): array
    {
        $actor ??= $this->parent;
        $quote = $this->orders->quote($actor, $cart ?? $this->cart());

        return app(WalletCounterPayments::class)->qr($actor, ['operation_id' => (string) Str::uuid(), 'quote' => $quote['quote'], 'confirmed' => true]);
    }

    private function paymentInput(array $qr): array
    {
        return ['operation_id' => (string) Str::uuid(), 'intent_id' => $qr['id'], 'qr_token' => substr($qr['qr'], strlen('navigateed:wallet-pay:')), 'confirmed' => true];
    }

    public function test_qr_issues_without_debit_then_counter_pays_and_hands_over_exactly_once(): void
    {
        $walletBalance = app(LedgerService::class)->balance($this->wallet->ledger_account_id)->cents;
        $qr = $this->paymentQr();
        $this->assertSame($walletBalance, app(LedgerService::class)->balance($this->wallet->ledger_account_id)->cents);
        $input = $this->paymentInput($qr);
        $service = app(WalletCounterPayments::class);
        $lookup = $service->lookup($this->operator, $this->vendor->id, ['qr_token' => $input['qr_token']]);
        $this->assertSame($qr['id'], $lookup['id']);
        $this->assertArrayNotHasKey('wallet_account_id', $lookup);
        $this->assertArrayNotHasKey('private_quote', $lookup);
        $result = $service->pay($this->operator, $this->vendor->id, $input);
        $this->assertSame('paid', $result['status']);
        $this->assertSame('collected', $result['order']['status']);
        $repeat = $service->pay($this->operator, $this->vendor->id, $input);
        $this->assertFalse($result['replayed']);
        $this->assertTrue($repeat['replayed']);
        $this->assertSame($result['id'], $repeat['id']);
        $this->assertSame(1, WalletCounterPayment::count());
        $this->assertSame(1, VendorOrder::count());
        $this->assertSame($walletBalance - 2500, app(LedgerService::class)->balance($this->wallet->ledger_account_id)->cents);
        $order = VendorOrder::sole();
        $this->assertSame($this->parent->id, $order->actor_id);
        $this->assertSame($this->operator->id, VendorOrderAction::sole()->actor_id);
        $this->assertSame('committed', $service->resolve($this->operator, $this->vendor->id, $input['operation_id'])['status']);
        $personal = $service->personal($this->parent, $qr['id']);
        $this->assertSame('paid', $personal['status']);
        $this->assertNull($personal['qr']);
        $this->assertTrue(VendorOrderReconciliation::healthy(app(VendorOrderReconciliation::class)->inspect()));
    }

    public function test_wrong_role_school_holder_operator_and_missing_qr_possession_are_refused(): void
    {
        $service = app(WalletCounterPayments::class);
        $qr = $this->paymentQr();
        $input = $this->paymentInput($qr);
        $this->refuses(fn () => $service->lookup($this->parent, $this->vendor->id, ['qr_token' => $input['qr_token']]), 403);
        $this->refuses(fn () => $service->personal(User::findOrFail($this->schoolA->ids['mona_user']), $qr['id']), 404);
        $this->refuses(fn () => $service->pay($this->operator, $this->vendor->id, array_replace($input, ['qr_token' => str_repeat('b', 64)])), 403);
        app(CurrentSchool::class)->set(2);
        $this->refuses(fn () => $service->personal($this->parent, $qr['id']), 404);
        app(CurrentSchool::class)->set(1);
        app(VendorOperators::class)->bind($this->admin, $this->binding(['revision' => 1, 'status' => 'revoked']));
        $this->refuses(fn () => $service->pay($this->operator, $this->vendor->id, $input), 403);
        $this->assertSame(0, VendorOrder::count());
    }

    public function test_cancel_expiry_stale_price_and_disabled_module_never_debit(): void
    {
        $service = app(WalletCounterPayments::class);
        $qr = $this->paymentQr();
        $input = $this->paymentInput($qr);
        $this->assertSame('cancelled', $service->cancel($this->parent, $qr['id'])['status']);
        $this->refuses(fn () => $service->pay($this->operator, $this->vendor->id, $input), 409);
        $qr = $this->paymentQr();
        $input = $this->paymentInput($qr);
        $this->travel(3)->minutes();
        $this->refuses(fn () => $service->pay($this->operator, $this->vendor->id, $input), 409);
        $this->travelBack();
        $qr = $this->paymentQr();
        $input = $this->paymentInput($qr);
        app(VendorCatalogues::class)->product($this->admin, $this->productInput(['id' => $this->product->id, 'revision' => 1, 'price' => '13.00']));
        $this->refuses(fn () => $service->pay($this->operator, $this->vendor->id, $input), 409);
        app(ModuleGate::class)->change('wallet_payments', false, $this->admin);
        $this->assertNull($service->personal($this->parent, $qr['id'])['qr']);
        $this->refuses(fn () => $service->pay($this->operator, $this->vendor->id, $input), 403);
        $this->assertSame(0, VendorOrder::count());
    }

    public function test_unknown_recovery_permanently_abandons_a_late_counter_request(): void
    {
        $service = app(WalletCounterPayments::class);
        $qr = $this->paymentQr();
        $input = $this->paymentInput($qr);
        $this->assertSame('abandoned', $service->resolve($this->operator, $this->vendor->id, $input['operation_id'])['status']);
        $this->refuses(fn () => $service->pay($this->operator, $this->vendor->id, $input), 409);
        $this->assertSame(0, VendorOrder::count());
    }

    public function test_original_guardian_and_category_limits_are_rechecked_at_debit(): void
    {
        app(Wallets::class)->controls($this->parent, $this->childWallet->id, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'daily_spend_cents' => 5000, 'all_categories' => true, 'categories' => []]);
        $service = app(WalletCounterPayments::class);
        $qr = $this->paymentQr($this->parent, $this->cart(1, $this->childWallet->id));
        $input = $this->paymentInput($qr);
        GuardianLink::where('learner_id', $this->schoolA->ids['asha'])->where('guardian_user_id', $this->parent->id)->update(['status' => 'revoked']);
        $this->refuses(fn () => $service->pay($this->operator, $this->vendor->id, $input), 404);
        $this->assertSame(0, VendorOrder::count());
    }

    public function test_sql_retains_original_instruction_and_counter_outcome(): void
    {
        $qr = $this->paymentQr();
        $this->expectException(QueryException::class);
        DB::table('wallet_payment_intents')->where('id', $qr['id'])->update(['amount_cents' => 1]);
    }

    private function cardFixture(): array
    {
        app(ModuleGate::class)->change('cards', true, $this->admin);
        Storage::fake(CardPortraits::DISK);
        $this->app->bind(UploadScanner::class, fn () => new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                return ['clean' => true, 'engine' => 'synthetic-test-only', 'signature' => null];
            }
        });
        Enrolment::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->schoolA->ids['session_year'], 'class_section_id' => $this->schoolA->ids['section_a']], ['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'legacy_dates' => false]);
        app(Wallets::class)->controls($this->parent, $this->childWallet->id, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'daily_spend_cents' => 5000, 'all_categories' => true, 'categories' => []]);
        app(CardPortraits::class)->upload($this->parent, $this->child->id, 'learner', (string) Str::uuid(), UploadedFile::fake()->image('synthetic-card.jpg', 120, 160));
        $card = app(Cards::class)->issue($this->admin, ['operation_id' => (string) Str::uuid(), 'person_id' => $this->child->id, 'type' => 'learner']);
        $public = base64_encode(sodium_crypto_box_publickey(sodium_crypto_box_keypair()));
        $reader = app(VendorPaymentReaders::class)->register($this->admin, $this->vendor->id, ['operation_id' => (string) Str::uuid(), 'name' => 'Synthetic purpose-bound payment reader', 'public_key' => $public, 'days' => 1]);
        $encoder = app(CardReaders::class)->register($this->admin, ['operation_id' => (string) Str::uuid(), 'name' => 'Synthetic encoder', 'kind' => 'encoder', 'public_key' => $public, 'days' => 1]);
        $profile = CardChipProfile::create(['card_id' => $card['id'], 'encoder_device_id' => $encoder['id'], 'uid' => '04DE5F1EACC040', 'uid_hash' => hash('sha256', '04DE5F1EACC040'), 'metadata_key' => bin2hex(str_repeat("\0", 16)), 'file_read_key' => bin2hex(str_repeat("\0", 16)), 'encoding_package' => 'synthetic-fixture-only', 'encoded_at' => now('UTC')]);

        return ['card' => $card, 'reader' => $reader, 'profile' => $profile, 'public' => $public, 'input' => ['operation_id' => (string) Str::uuid(), 'card_id' => $card['id'], 'picc' => 'EF963FF7828658A599F3041510671E88', 'mac' => '94EED9EE65337086', 'lines' => [['product_id' => $this->product->id, 'quantity' => 1]]]];
    }

    public function test_sun_payment_uses_private_photo_and_independent_card_authority_before_first_app_login(): void
    {
        $f = $this->cardFixture();
        $this->child->forceFill(['password_change_required' => true])->save();
        $balance = app(LedgerService::class)->balance($this->childWallet->ledger_account_id)->cents;
        $cards = app(WalletCardPayments::class);
        $review = $cards->prepare($this->operator, $this->vendor->id, $f['reader']['credential'], $f['input']);
        $this->assertSame(61, $f['profile']->fresh()->last_counter);
        $this->assertSame(1250, $review['amount_cents']);
        $this->assertSame($balance, app(LedgerService::class)->balance($this->childWallet->ledger_account_id)->cents);
        $photo = $cards->portrait($this->operator, $this->vendor->id, $f['reader']['credential'], $review['id']);
        $this->assertSame('image/jpeg', $photo['mime']);
        $this->assertNotEmpty($photo['bytes']);
        $input = ['operation_id' => (string) Str::uuid(), 'intent_id' => $review['id'], 'portrait_confirmed' => true, 'portrait_revision' => $review['portrait_revision'], 'confirmed' => true];
        $service = app(WalletCounterPayments::class);
        $paid = $service->pay($this->operator, $this->vendor->id, $input, $f['reader']['credential']);
        $this->assertSame('paid', $paid['status']);
        $this->assertSame($balance - 1250, app(LedgerService::class)->balance($this->childWallet->ledger_account_id)->cents);
        $repeat = $service->pay($this->operator, $this->vendor->id, $input, $f['reader']['credential']);
        $this->assertFalse($paid['replayed']);
        $this->assertTrue($repeat['replayed']);
        $this->assertSame($paid['id'], $repeat['id']);
        $this->assertSame($this->child->id, VendorOrder::sole()->actor_id);
        $this->assertSame($this->operator->id, VendorOrderAction::sole()->actor_id);
        $this->assertTrue(VendorOrderReconciliation::healthy(app(VendorOrderReconciliation::class)->inspect()));
    }

    public function test_forged_sun_gate_reader_and_replayed_counter_do_not_create_new_card_instructions(): void
    {
        $f = $this->cardFixture();
        $cards = app(WalletCardPayments::class);
        $this->refuses(fn () => $cards->prepare($this->operator, $this->vendor->id, $f['reader']['credential'], array_replace($f['input'], ['mac' => '0000000000000000'])), 403);
        $this->assertSame(-1, $f['profile']->fresh()->last_counter);
        $gate = app(CardReaders::class)->register($this->admin, ['operation_id' => (string) Str::uuid(), 'name' => 'Synthetic gate only', 'kind' => 'gate', 'public_key' => $f['public'], 'days' => 1]);
        $this->refuses(fn () => $cards->prepare($this->operator, $this->vendor->id, $gate['credential'], $f['input']), 403);
        $review = $cards->prepare($this->operator, $this->vendor->id, $f['reader']['credential'], $f['input']);
        $this->assertSame($review, $cards->prepare($this->operator, $this->vendor->id, $f['reader']['credential'], $f['input']));
        $this->refuses(fn () => $cards->prepare($this->operator, $this->vendor->id, $f['reader']['credential'], array_replace($f['input'], ['operation_id' => (string) Str::uuid()])), 409);
        $this->assertSame(1, WalletPaymentIntent::count());
        $this->assertSame(0, VendorOrder::count());
    }

    public function test_changed_photo_and_frozen_card_refuse_confirmation_and_private_photo(): void
    {
        $f = $this->cardFixture();
        $cards = app(WalletCardPayments::class);
        $review = $cards->prepare($this->operator, $this->vendor->id, $f['reader']['credential'], $f['input']);
        $input = ['operation_id' => (string) Str::uuid(), 'intent_id' => $review['id'], 'portrait_confirmed' => true, 'portrait_revision' => $review['portrait_revision'], 'confirmed' => true];
        app(CardPortraits::class)->upload($this->parent, $this->child->id, 'learner', (string) Str::uuid(), UploadedFile::fake()->image('changed-photo.jpg', 120, 160));
        $this->refuses(fn () => app(WalletCounterPayments::class)->pay($this->operator, $this->vendor->id, $input, $f['reader']['credential']), 409);
        $this->refuses(fn () => $cards->portrait($this->operator, $this->vendor->id, $f['reader']['credential'], $review['id']), 409);
        Card::whereKey($f['card']['id'])->update(['status' => 'frozen', 'frozen_at' => now('UTC'), 'revision' => 2]);
        $this->refuses(fn () => app(WalletCounterPayments::class)->pay($this->operator, $this->vendor->id, $input, $f['reader']['credential']), 409);
        $this->assertSame(0, VendorOrder::count());
    }

    public function test_revoked_payment_reader_and_missing_photo_confirmation_prevent_card_debit(): void
    {
        $f = $this->cardFixture();
        $cards = app(WalletCardPayments::class);
        $review = $cards->prepare($this->operator, $this->vendor->id, $f['reader']['credential'], $f['input']);
        $input = ['operation_id' => (string) Str::uuid(), 'intent_id' => $review['id'], 'confirmed' => true];
        $this->refuses(fn () => app(WalletCounterPayments::class)->pay($this->operator, $this->vendor->id, $input, $f['reader']['credential']), 422);
        app(CardReaders::class)->revoke($this->admin, $f['reader']['id']);
        $this->refuses(fn () => $cards->portrait($this->operator, $this->vendor->id, $f['reader']['credential'], $review['id']), 403);
        $this->refuses(fn () => app(WalletCounterPayments::class)->pay($this->operator, $this->vendor->id, $input, $f['reader']['credential']), 403);
        $this->assertSame(0, VendorOrder::count());
    }

    private function apiAs(User $actor): static
    {
        $this->app['auth']->forgetGuards();
        $token = $actor->createToken($actor->id === $this->operator->id ? AppTokens::VENDOR : AppTokens::PARENT)->plainTextToken;

        return $this->flushHeaders()->withHeaders(['Authorization' => 'Bearer '.$token, 'X-School-Id' => '1', 'X-Account-Id' => (string) $actor->id, 'Accept' => 'application/json']);
    }

    public function test_private_qr_api_contract_recovery_confirmation_and_role_refusals(): void
    {
        ParentPolicyService::acceptCurrent($this->parent);
        $quote = $this->orders->quote($this->parent, $this->cart());
        $operation = (string) Str::uuid();
        $this->apiAs($this->parent);
        $this->withHeader('Idempotency-Key', $operation);
        $input = ['operation_id' => $operation, 'quote' => $quote['quote'], 'confirmed' => true];
        $qr = $this->postJson('/api/v2/commerce/wallet-payments', $input)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $this->getJson('/api/v2/commerce/wallet-payment-operations/'.$operation)->assertOk()->assertJsonPath('data.id', $qr['id']);
        $this->getJson('/api/v2/commerce/wallet-payments')->assertOk()->assertJsonPath('data.items.0.id', $qr['id']);
        $this->postJson('/api/v2/vendor/counters/'.$this->vendor->id.'/payments/lookup', ['qr_token' => $qr['qr']])->assertForbidden();
        $this->apiAs($this->operator);
        $this->getJson('/api/v2/vendor/counters/'.$this->vendor->id.'/payments/catalogue')->assertOk()->assertJsonPath('data.items.0.id', $this->product->id);
        $this->postJson('/api/v2/vendor/counters/'.$this->vendor->id.'/payments/lookup', ['qr_token' => $qr['qr']])->assertOk()->assertJsonPath('data.id', $qr['id']);
        $pay = $this->paymentInput($qr);
        $this->withHeader('Idempotency-Key', $pay['operation_id']);
        $response = $this->postJson('/api/v2/vendor/counters/'.$this->vendor->id.'/payments/pay', $pay)->assertOk();
        $receipt = $response->json('data.id');
        $this->postJson('/api/v2/vendor/counters/'.$this->vendor->id.'/payments/pay', $pay)->assertOk()->assertJsonPath('data.id', $receipt);
        $this->postJson('/api/v2/vendor/counters/'.$this->vendor->id.'/payments/operations/'.$pay['operation_id'].'/resolve')->assertOk()->assertJsonPath('data.status', 'committed');
        $this->getJson('/api/v2/vendor/counters/'.$this->vendor->id.'/payments/receipts/'.$receipt)->assertOk();
        $this->apiAs($this->parent);
        $this->getJson('/api/v2/commerce/wallet-payments/'.$qr['id'])->assertOk()->assertJsonPath('data.status', 'paid')->assertJsonPath('data.qr', null);
    }

    public function test_sun_api_uses_private_reader_header_and_serves_only_current_intent_portrait(): void
    {
        $f = $this->cardFixture();
        $this->apiAs($this->operator);
        $this->withHeaders(['Idempotency-Key' => $f['input']['operation_id'], 'X-Payment-Reader' => $f['reader']['credential']]);
        $review = $this->postJson('/api/v2/vendor/counters/'.$this->vendor->id.'/payments/card', $f['input'])->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $this->get('/api/v2/vendor/counters/'.$this->vendor->id.'/payments/card/'.$review['id'].'/portrait')->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson('/api/v2/vendor/counters/'.$this->vendor->id.'/payments/card/'.$review['id'].'/portrait?credential=discarded-synthetic')->assertUnprocessable();
        $this->withoutHeader('X-Payment-Reader');
        $this->getJson('/api/v2/vendor/counters/'.$this->vendor->id.'/payments/card/'.$review['id'].'/portrait')->assertUnauthorized();
        $this->assertSame(0, VendorOrder::count());
    }

    public function test_finance_payment_reader_forms_bind_actor_and_school_and_revoke_while_paused(): void
    {
        $f = $this->cardFixture();
        $url = '/vendor-payment-readers';
        $this->actingAs($this->admin)->get($url)->assertOk()->assertSee('Vendor payment readers')->assertDontSee($f['reader']['credential']);
        $input = ['expected_school_id' => 1, 'expected_account_id' => $this->admin->id, 'operation_id' => (string) Str::uuid(), 'vendor_id' => $this->vendor->id, 'name' => 'Synthetic new paired counter', 'days' => 1];
        $this->post($url, array_replace($input, ['expected_school_id' => 2]))->assertForbidden()->assertSessionMissing('_old_input');
        $this->post($url, $input)->assertOk()->assertSee('Private reader pairing credential')->assertSee('NED1.1.')->assertHeader('Cache-Control', 'no-store, private');
        $new = CardReaderDevice::where('name', 'Synthetic new paired counter')->sole();
        $this->assertSame('vendor', $new->kind);
        app(ModuleGate::class)->change('wallet_payments', false, $this->admin);
        $this->get($url)->assertOk()->assertSee('Existing readers can still be revoked.');
        $this->post($url.'/'.$new->id.'/revoke', ['expected_school_id' => 1, 'expected_account_id' => $this->admin->id])->assertOk();
        $this->assertSame('revoked', $new->fresh()->status);
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']))->get($url)->assertForbidden();
    }

    public function test_vendor_portal_qr_confirmation_replay_and_bound_forms(): void
    {
        $qr = $this->paymentQr();
        $input = $this->paymentInput($qr);
        $url = '/vendor-counter/counters/'.$this->vendor->id.'/payments';
        $this->app['auth']->shouldUse('web');
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->operator, 'web')->withSession(['portal_app' => AppTokens::VENDOR]);
        $bind = ['expected_school_id' => 1, 'expected_account_id' => $this->operator->id];
        $this->get($url)->assertOk()->assertSee('Review wallet QR payment')->assertHeader('Cache-Control', 'no-store, private');
        $this->post($url.'/lookup', ['qr_token' => $qr['qr']] + $bind)->assertOk()->assertSee('Review this counter payment')->assertSee($qr['id'])->assertSessionMissing('_old_input');
        $this->post($url.'/pay', array_replace($input + $bind, ['expected_school_id' => 2]))->assertForbidden();
        $this->post($url.'/pay', $input + $bind)->assertOk()->assertSee('Payment recorded. Hand over the listed goods once.');
        $this->post($url.'/pay', $input + $bind)->assertOk()->assertSee('Payment was already recorded. Check the original receipt; do not hand over goods again.');
        $this->assertSame(1, WalletCounterPayment::count());
        $this->post($url.'/lookup?qr_token=discarded-synthetic-proof', ['qr_token' => $qr['qr']] + $bind)->assertUnprocessable()->assertSessionMissing('_old_input');
    }

    public function test_vendor_portal_sun_review_shows_current_private_photo_and_one_payment(): void
    {
        $f = $this->cardFixture();
        $url = '/vendor-counter/counters/'.$this->vendor->id.'/payments';
        $this->app['auth']->shouldUse('web');
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->operator, 'web')->withSession(['portal_app' => AppTokens::VENDOR]);
        $bind = ['expected_school_id' => 1, 'expected_account_id' => $this->operator->id];
        $tap = rtrim(config('app.url'), '/').'/card-tap#s=1&c='.$f['card']['id'].'&e='.$f['input']['picc'].'&m='.$f['input']['mac'];
        $this->post($url.'/card', $bind + ['operation_id' => (string) Str::uuid(), 'reader_credential' => $f['reader']['credential'], 'tap_uri' => $tap, 'lines' => $f['input']['lines']])->assertOk()->assertSee('data:image/jpeg;base64,', false)->assertSee('Current cardholder photo');
        $intent = WalletPaymentIntent::where('kind', 'sun')->sole();
        $input = $bind + ['operation_id' => (string) Str::uuid(), 'intent_id' => $intent->id, 'reader_credential' => $f['reader']['credential'], 'portrait_revision' => $intent->portrait_revision, 'portrait_confirmed' => true, 'confirmed' => true];
        $this->post($url.'/pay', $input)->assertOk()->assertSee('Payment recorded. Hand over the listed goods once.');
        $this->assertSame(1, WalletCounterPayment::count());
    }

    public function test_reconciliation_detects_a_counter_order_missing_its_final_payment_receipt(): void
    {
        $qr = $this->paymentQr();
        DB::transaction(function () use ($qr): void {
            School::whereKey(1)->lockForUpdate()->firstOrFail();
            $intent = WalletPaymentIntent::lockForUpdate()->findOrFail($qr['id']);
            $authority = CounterSpendAuthority::fromIntent($this->operator, $intent);
            $this->orders->counterPurchase($authority, (string) Str::uuid());
        });
        $report = app(VendorOrderReconciliation::class)->inspect();
        $this->assertSame(0, $report['vendor_order_mismatches']);
        $this->assertSame(1, $report['incomplete_counter_orders']);
        $this->assertFalse(VendorOrderReconciliation::healthy($report));
        $this->assertSame('counter', VendorOrder::sole()->purchase_channel);
        $this->assertSame($qr['id'], VendorOrder::sole()->wallet_intent_id);
    }

    public function test_counter_qr_keeps_digital_admission_delivery_and_actual_vendor_attribution(): void
    {
        app(ModuleGate::class)->change('ticketing', true, $this->admin);
        $time = now('Africa/Johannesburg');
        $catalogue = app(TicketCatalogues::class);
        $event = $catalogue->event($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic counter admission', 'description' => 'Original explicit admission', 'status' => 'active', 'sales_from' => $time->copy()->subHour()->format('Y-m-d\TH:i'), 'sales_until' => $time->copy()->addHour()->format('Y-m-d\TH:i'), 'starts_at' => $time->copy()->subMinutes(10)->format('Y-m-d\TH:i'), 'ends_at' => $time->copy()->addHours(2)->format('Y-m-d\TH:i'), 'private_reason' => 'Explicit synthetic counter admission windows']);
        $product = app(VendorCatalogues::class)->product($this->admin, $this->productInput(['sku' => 'COUNTER-ADMISSION', 'name' => 'Synthetic counter admission', 'tracks_stock' => false]));
        $catalogue->type($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'event_id' => $event->id, 'product_id' => $product->id, 'kind' => 'general', 'capacity' => 2, 'status' => 'active', 'transfer_rule' => 'none', 'private_reason' => 'Explicit general admission counter capacity']);
        $qr = $this->paymentQr($this->parent, ['vendor_id' => $this->vendor->id, 'wallet_id' => $this->wallet->id, 'lines' => [['product_id' => $product->id, 'quantity' => 1]]]);
        app(WalletCounterPayments::class)->pay($this->operator, $this->vendor->id, $this->paymentInput($qr));
        $this->assertSame(1, Ticket::count());
        $this->assertSame($this->operator->id, VendorOrderAction::sole()->actor_id);
        $this->assertSame('counter', VendorOrder::sole()->purchase_channel);
        $this->assertTrue(VendorOrderReconciliation::healthy(app(VendorOrderReconciliation::class)->inspect()));
    }
}
