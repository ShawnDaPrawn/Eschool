<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Commerce\Ticket;
use App\Models\Commerce\TicketAction;
use App\Models\Commerce\TicketEvent;
use App\Models\Commerce\TicketType;
use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorOrder;
use App\Models\Commerce\VendorProduct;
use App\Models\Money\WalletAccount;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Commerce\TicketCatalogues;
use App\Services\Commerce\TicketCounters;
use App\Services\Commerce\TicketHolders;
use App\Services\Commerce\TicketReconciliation;
use App\Services\Commerce\VendorCatalogues;
use App\Services\Commerce\VendorOperators;
use App\Services\Commerce\VendorOrderReconciliation;
use App\Services\Commerce\VendorOrders;
use App\Services\Money\WalletDeposits;
use App\Services\Money\Wallets;
use App\Services\Money\WalletSettings;
use App\Services\Money\WalletTransfers;
use App\Services\ParentPolicyService;
use App\Services\Platform\Identity\AppTokens;
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

class TicketsTest extends TestCase
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
            $this->fail('Expected ticket authority or invariant refusal');
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (ValidationException $e) {
            $this->assertSame(422, $status, $e->getMessage());
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($status, $e->getStatusCode(), $e->getMessage());
        }
    }

    private function event(): TicketEvent
    {
        app(ModuleGate::class)->change('ticketing', true, $this->admin);
        $time = now('Africa/Johannesburg');

        return app(TicketCatalogues::class)->event($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic school admission', 'status' => 'active', 'sales_from' => $time->copy()->subHour()->format('Y-m-d\TH:i'), 'sales_until' => $time->copy()->addHour()->format('Y-m-d\TH:i'), 'starts_at' => $time->copy()->subMinutes(10)->format('Y-m-d\TH:i'), 'ends_at' => $time->copy()->addHours(2)->format('Y-m-d\TH:i'), 'private_reason' => 'Explicit synthetic school sale and admission policy']);
    }

    private function type(string $kind = 'general', string $rule = 'families', int $capacity = 3): TicketType
    {
        $event = $this->event();
        $p = app(VendorCatalogues::class)->product($this->admin, $this->productInput(['sku' => 'ADMIT-'.Str::uuid(), 'name' => 'Synthetic original event admission', 'tracks_stock' => false]));

        return app(TicketCatalogues::class)->type($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'event_id' => $event->id, 'product_id' => $p->id, 'kind' => $kind, 'status' => 'active', 'transfer_rule' => $rule, 'capacity' => $capacity, 'private_reason' => 'Explicit synthetic admission capacity and transfer policy']);
    }

    private function admission(TicketType $type, int $quantity = 1, ?int $learner = null): VendorOrder
    {
        return $this->buy($this->parent, ['vendor_id' => $this->vendor->id, 'wallet_id' => $this->wallet->id, 'learner_id' => $learner, 'lines' => [['product_id' => $type->product_id, 'quantity' => $quantity]]]);
    }

    public function test_digital_ticket_checkout_issues_exact_capacity_and_original_fulfilment_atomically(): void
    {
        $type = $this->type();
        $q = $this->orders->quote($this->parent, ['vendor_id' => $this->vendor->id, 'wallet_id' => $this->wallet->id, 'lines' => [['product_id' => $type->product_id, 'quantity' => 2]]]);
        $input = ['operation_id' => (string) Str::uuid(), 'quote' => $q['quote'], 'confirmed' => true];
        $order = $this->orders->purchase($this->parent, $input);
        $this->assertSame($order->id, $this->orders->purchase($this->parent, $input)->id);
        $this->assertSame(2, Ticket::count());
        $this->assertSame('collected', $this->orders->state($order));
        $this->assertSame(42500, app(LedgerService::class)->balance($this->wallet->ledger_account_id)->cents);
        $this->assertTrue(VendorOrderReconciliation::healthy(app(VendorOrderReconciliation::class)->inspect()));
        $this->refuses(fn () => $this->admission($type, 2), 409);
        $this->assertSame(2, Ticket::count());
        $this->refuses(fn () => $this->orders->cancel($this->parent, $order->id, ['operation_id' => (string) Str::uuid(), 'private_reason' => 'Synthetic attempted physical cancellation', 'confirmed' => true]), 409);
        $held = app(TicketHolders::class)->listing($this->parent);
        $this->assertCount(2, $held['items']);
        $this->assertStringStartsWith('navigateed:ticket:', $held['items'][0]['qr']);
        $this->assertStringNotContainsString($held['items'][0]['ticket_number'], DB::table('tickets')->where('id', $held['items'][0]['id'])->value('private_number'));
    }

    public function test_mixed_carts_closed_sales_and_unconfigured_ticketing_cannot_bypass_original_checkout(): void
    {
        $type = $this->type();
        $this->refuses(fn () => $this->orders->quote($this->parent, ['vendor_id' => $this->vendor->id, 'wallet_id' => $this->wallet->id, 'lines' => [['product_id' => $type->product_id, 'quantity' => 1], ['product_id' => $this->product->id, 'quantity' => 1]]]), 422);
        app(ModuleGate::class)->change('ticketing', false, $this->admin);
        $this->refuses(fn () => $this->admission($type), 403);
        $this->assertSame(0, Ticket::count());
    }

    public function test_transfer_rotates_both_credentials_and_previous_owner_cannot_read_recipient_ticket(): void
    {
        $type = $this->type();
        $this->admission($type);
        $ticket = Ticket::sole();
        $s = app(TicketHolders::class);
        $original = $s->receipt($this->parent, $ticket->id);
        $recipient = User::findOrFail($this->schoolA->ids['gina_user']);
        $recipient->forceFill(['email_verified_at' => now()])->save();
        $code = $s->receiveCode($recipient);
        $preview = $s->previewTransfer($this->parent, $ticket->id, $code['receive_code']);
        $input = ['operation_id' => (string) Str::uuid(), 'revision' => $preview['revision'], 'recipient_id' => $recipient->id, 'receive_code' => $code['receive_code'], 'confirmed' => true];
        $before = DB::table('ledger_entries')->count();
        $ack = $s->transfer($this->parent, $ticket->id, $input);
        $this->assertSame($ack, $s->transfer($this->parent, $ticket->id, $input));
        $this->assertArrayNotHasKey('qr', $ack);
        $this->assertSame($before, DB::table('ledger_entries')->count());
        $this->refuses(fn () => $s->receipt($this->parent, $ticket->id), 404);
        $this->assertCount(0, $s->listing($this->parent)['items']);
        $next = $s->receipt($recipient, $ticket->id);
        $this->assertNotSame($original['qr'], $next['qr']);
        $this->assertNotSame($original['ticket_number'], $next['ticket_number']);
        $counter = app(TicketCounters::class);
        $this->refuses(fn () => $counter->lookup($this->admin, $this->vendor->id, ['qr_token' => substr($original['qr'], 18)], false), 404);
        $this->refuses(fn () => $counter->lookup($this->admin, $this->vendor->id, ['ticket_number' => $original['ticket_number']], false), 404);
        $this->assertTrue($counter->lookup($this->admin, $this->vendor->id, ['ticket_number' => $next['ticket_number']], false)['can_redeem']);
    }

    public function test_named_tickets_require_original_authorised_learner_and_current_guardians_for_transfer_and_reads(): void
    {
        $type = $this->type('named');
        $this->refuses(fn () => $this->admission($type), 422);
        $this->admission($type, 1, $this->schoolA->ids['asha']);
        $ticket = Ticket::sole();
        $s = app(TicketHolders::class);
        $other = User::findOrFail($this->schoolA->ids['gina_user']);
        $other->forceFill(['email_verified_at' => now()])->save();
        $code = $s->receiveCode($other);
        $this->refuses(fn () => $s->previewTransfer($this->parent, $ticket->id, $code['receive_code']), 404);
        GuardianLink::where('learner_id', $this->schoolA->ids['asha'])->where('guardian_user_id', $this->parent->id)->update(['status' => 'ended']);
        $this->refuses(fn () => $s->receipt($this->parent, $ticket->id), 404);
        $this->assertCount(0, $s->listing($this->parent)['items']);
    }

    public function test_admission_is_single_use_and_same_original_request_reports_replay_without_moving_money(): void
    {
        $type = $this->type();
        $this->admission($type);
        $ticket = Ticket::sole();
        $s = app(TicketHolders::class);
        $r = $s->receipt($this->parent, $ticket->id);
        $c = app(TicketCounters::class);
        $input = ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'ticket_number' => $r['ticket_number'], 'confirmed' => true];
        $entries = DB::table('ledger_entries')->count();
        $first = $c->redeem($this->admin, $this->vendor->id, $input, false);
        $again = $c->redeem($this->admin, $this->vendor->id, $input, false);
        $this->assertFalse($first['replayed']);
        $this->assertTrue($again['replayed']);
        $this->assertSame('redeemed', $again['receipt']['status']);
        $this->assertSame($entries, DB::table('ledger_entries')->count());
        $this->assertSame(1, TicketAction::count());
        $this->refuses(fn () => $c->redeem($this->admin, $this->vendor->id, array_replace($input, ['operation_id' => (string) Str::uuid()]), false), 409);
        $this->assertNull($s->receipt($this->parent, $ticket->id)['qr']);
        $this->assertFalse($s->receipt($this->parent, $ticket->id)['can_transfer']);
    }

    public function test_wrong_role_school_and_owner_are_refused_before_ticket_and_catalogue_data(): void
    {
        $type = $this->type();
        $this->admission($type);
        $ticket = Ticket::sole();
        $s = app(TicketHolders::class);
        $other = User::findOrFail($this->schoolA->ids['gina_user']);
        $other->forceFill(['email_verified_at' => now()])->save();
        $this->refuses(fn () => $s->receipt($other, $ticket->id), 404);
        $this->refuses(fn () => app(CurrentSchool::class)->run(2, fn () => $s->receipt($this->parent, $ticket->id)), 403);
        $this->refuses(fn () => app(TicketCounters::class)->lookup($this->parent, $this->vendor->id, ['ticket_number' => 'TK-'.str_repeat('A', 20)], false), 403);
        $this->refuses(fn () => app(TicketCatalogues::class)->event($this->parent, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Hidden', 'status' => 'active']), 422);
        $this->assertSame(1, Ticket::count());
    }

    public function test_private_finance_event_forms_bind_school_actor_and_do_not_flash_evidence(): void
    {
        $type = $this->type();
        $this->actingAs($this->admin)->get('/ticket-events?event_id='.$type->event_id.'&type_id='.$type->id)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $this->post('/ticket-events/events', ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'private_reason' => 'Private synthetic event reason'] + array_replace($bind, ['expected_school_id' => 2]))->assertForbidden();
        $this->post('/ticket-events/events', ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'private_reason' => 'Private synthetic event reason'] + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']))->get('/ticket-events')->assertForbidden();
    }

    public function test_ticket_holder_api_contracts_rotate_codes_and_cached_transfer_ack_has_no_new_owner_secrets(): void
    {
        $type = $this->type();
        $this->admission($type);
        $ticket = Ticket::sole();
        $s = app(TicketHolders::class);
        $recipient = User::findOrFail($this->schoolA->ids['gina_user']);
        $recipient->forceFill(['email_verified_at' => now()])->save();
        Sanctum::actingAs($recipient);
        $headers = ['X-School-Id' => '1'];
        $code = $this->postJson('/api/v2/commerce/tickets/receive-code', [], $headers)->assertOk()->json('data.receive_code');
        Sanctum::actingAs($this->parent);
        $this->getJson('/api/v2/commerce/tickets', $headers)->assertOk()->assertJsonCount(1, 'data.items');
        $this->getJson('/api/v2/commerce/tickets/'.$ticket->id, $headers)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $url = '/api/v2/commerce/tickets/'.$ticket->id.'/transfer';
        $preview = $this->postJson($url.'-preview', ['receive_code' => $code], $headers)->assertOk()->json('data');
        $op = ['operation_id' => (string) Str::uuid(), 'revision' => $preview['revision'], 'receive_code' => $code, 'recipient_id' => $preview['recipient_id'], 'confirmed' => true];
        $headers['Idempotency-Key'] = $op['operation_id'];
        $this->postJson($url, $op, $headers)->assertOk()->assertJsonPath('data.status', 'transferred')->assertJsonMissingPath('data.qr');
        $this->postJson($url, $op, $headers)->assertOk()->assertHeader('Idempotent-Replayed', 'true')->assertJsonMissingPath('data.receive_code')->assertJsonMissingPath('data.qr');
        $this->getJson('/api/v2/commerce/tickets/'.$ticket->id, ['X-School-Id' => '1'])->assertNotFound();
        Sanctum::actingAs($recipient);
        $this->getJson('/api/v2/commerce/tickets/'.$ticket->id, ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.holder_user_id', $recipient->id);
    }

    public function test_dedicated_vendor_ticket_api_checks_assignment_before_cached_single_use_admission(): void
    {
        $type = $this->type();
        $this->admission($type);
        $ticket = Ticket::sole();
        $s = app(TicketHolders::class);
        $number = $s->receipt($this->parent, $ticket->id)['ticket_number'];
        $operators = app(VendorOperators::class);
        $password = Str::random(40);
        $operator = $operators->provision($this->admin, ['operation_id' => (string) Str::uuid(), 'first_name' => 'Synthetic', 'last_name' => 'Admission operator', 'email' => 'admission.operator@example.test', 'initial_password' => $password, 'confirmed_operator_identity' => true]);
        $operator->forceFill(['password_change_required' => false])->save();
        ParentPolicyService::acceptCurrent($operator);
        $binding = ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'vendor_id' => $this->vendor->id, 'user_id' => $operator->id, 'status' => 'active', 'private_reason' => 'Explicit synthetic admission counter assignment', 'confirmed_operator_identity' => true];
        $operators->bind($this->admin, $binding);
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $token = $operator->createToken(AppTokens::VENDOR)->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token, 'X-School-Id' => '1', 'X-Account-Id' => (string) $operator->id];
        $base = '/api/v2/vendor/counters/'.$this->vendor->id;
        $this->postJson($base.'/tickets/lookup', ['ticket_number' => $number], $headers)->assertOk()->assertJsonPath('data.can_redeem', true)->assertJsonMissingPath('data.holder_user_id');
        $op = ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'ticket_number' => $number, 'confirmed' => true];
        $headers['Idempotency-Key'] = $op['operation_id'];
        $this->postJson($base.'/tickets/redeem', $op, $headers)->assertOk()->assertJsonPath('data.replayed', false);
        $this->postJson($base.'/tickets/redeem', $op, $headers)->assertOk()->assertJsonPath('data.replayed', true)->assertJsonPath('data.receipt.status', 'redeemed');
        unset($headers['Idempotency-Key']);
        $this->postJson($base.'/ticket-operations/'.$op['operation_id'].'/resolve', [], $headers)->assertOk()->assertJsonPath('data.status', 'committed');
        $unknown = (string) Str::uuid();
        $this->postJson($base.'/ticket-operations/'.$unknown.'/resolve', [], $headers)->assertOk()->assertJsonPath('data.status', 'abandoned');
        $operators->bind($this->admin, array_replace($binding, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'status' => 'revoked']));
        $headers['Idempotency-Key'] = $op['operation_id'];
        $this->postJson($base.'/tickets/redeem', $op, $headers)->assertForbidden();
        $this->assertSame(1, TicketAction::where('action', 'redeemed')->count());
    }

    public function test_ticket_history_cannot_be_changed_and_capacity_cannot_be_reduced_below_original_sales(): void
    {
        $type = $this->type();
        try {
            DB::table('vendor_products')->where('id', $type->product_id)->update(['tracks_stock' => true, 'revision' => 2]);
            $this->fail('Ticket product became physical counted stock');
        } catch (QueryException) {
            $this->assertFalse(VendorProduct::findOrFail($type->product_id)->tracks_stock);
        }
        $this->admission($type, 2);
        $ticket = Ticket::firstOrFail();
        foreach (['UPDATE', 'DELETE'] as $action) {
            try {
                DB::statement($action === 'UPDATE' ? 'UPDATE tickets SET original_holder_id=? WHERE id=?' : 'DELETE FROM tickets WHERE original_holder_id=? AND id=?', [$this->parent->id, $ticket->id]);
                $this->fail('Original ticket history changed');
            } catch (QueryException) {
                $this->assertSame(2, Ticket::count());
            }
        }
        $this->refuses(fn () => app(TicketCatalogues::class)->type($this->admin, ['operation_id' => (string) Str::uuid(), 'id' => $type->id, 'revision' => 1, 'event_id' => $type->event_id, 'product_id' => $type->product_id, 'kind' => 'general', 'status' => 'active', 'transfer_rule' => 'families', 'capacity' => 1, 'private_reason' => 'Synthetic attempt to reduce capacity below original issued admissions']), 422);
        $migration = require database_path('migrations/2026_10_28_230000_add_wallet_event_tickets.php');
        try {
            $migration->down();
            $this->fail('Original event and admission history dropped');
        } catch (\RuntimeException) {
            $this->assertSame(2, Ticket::count());
        }
    }

    public function test_school_transfer_refusal_and_expired_admission_window_do_not_move_tickets(): void
    {
        $type = $this->type('general', 'none');
        $this->admission($type);
        $ticket = Ticket::sole();
        $service = app(TicketHolders::class);
        $other = User::findOrFail($this->schoolA->ids['gina_user']);
        $other->forceFill(['email_verified_at' => now()])->save();
        $code = $service->receiveCode($other);
        $this->refuses(fn () => $service->previewTransfer($this->parent, $ticket->id, $code['receive_code']), 422);
        $r = $service->receipt($this->parent, $ticket->id);
        $this->assertFalse($r['can_transfer']);
        $this->travelTo(now()->addHours(3));
        $this->assertFalse(app(TicketCounters::class)->lookup($this->admin, $this->vendor->id, ['ticket_number' => $r['ticket_number']], false)['can_redeem']);
        $this->refuses(fn () => app(TicketCounters::class)->redeem($this->admin, $this->vendor->id, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'ticket_number' => $r['ticket_number'], 'confirmed' => true], false), 409);
        $this->assertSame(0, TicketAction::count());
    }

    public function test_expired_receiving_code_is_refused_before_ownership_moves(): void
    {
        $type = $this->type();
        $this->admission($type);
        $ticket = Ticket::sole();
        $service = app(TicketHolders::class);
        $other = User::findOrFail($this->schoolA->ids['gina_user']);
        $other->forceFill(['email_verified_at' => now()])->save();
        $code = $service->receiveCode($other);
        $this->travelTo(now()->addMinutes(6));
        $this->refuses(fn () => $service->previewTransfer($this->parent, $ticket->id, $code['receive_code']), 409);
        $this->assertSame(0, TicketAction::count());
        $this->assertSame((int) $this->parent->id, $service->receipt($this->parent, $ticket->id)['holder_user_id']);
    }

    public function test_guardian_named_history_keeps_learner_identity_after_student_membership_ends(): void
    {
        $type = $this->type('named');
        $this->admission($type, 1, $this->schoolA->ids['asha']);
        $ticket = Ticket::sole();
        $this->child->forceFill(['status' => 0])->save();
        SchoolMembership::where('school_id', 1)->where('user_id', $this->child->id)->where('role', 'Student')->update(['status' => 'ended', 'ends_on' => now()->subDay()->toDateString()]);
        $owners = app(Wallets::class)->accessible($this->parent);
        $child = collect($owners['owners'])->firstWhere('owner_user_id', (int) $this->child->id);
        $this->assertSame($this->schoolA->ids['asha'], $child['learner_id']);
        $this->assertFalse($child['can_open']);
        $rows = app(TicketHolders::class)->listing($this->parent);
        $this->assertCount(1, $rows['items']);
        $this->assertSame($ticket->id, $rows['items'][0]['id']);
        $this->assertSame($this->schoolA->ids['asha'], $rows['items'][0]['named_learner_id']);
        $this->refuses(fn () => $this->admission($type, 1, $this->schoolA->ids['asha']), 403);
    }

    public function test_other_counter_cannot_look_up_ticket_and_original_lineage_reconciles(): void
    {
        $type = $this->type();
        $this->admission($type);
        $ticket = Ticket::sole();
        $r = app(TicketHolders::class)->receipt($this->parent, $ticket->id);
        $other = app(VendorCatalogues::class)->vendor($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'category_id' => $this->vendor->category_id, 'name' => 'Other synthetic event counter', 'kind' => 'school', 'status' => 'active', 'legal_name' => 'Other original school vendor', 'vat_registered' => false, 'refund_bearer' => 'school', 'chargeback_bearer' => 'school', 'settlement_method' => 'school_internal', 'settlement_cycle' => 'manual', 'settlement_delay_days' => 0, 'settlement_terms' => 'Original independent counter terms']);
        $this->refuses(fn () => app(TicketCounters::class)->lookup($this->admin, $other->id, ['ticket_number' => $r['ticket_number']], false), 404);
        $this->assertTrue(TicketReconciliation::healthy(app(TicketReconciliation::class)->inspect()));
    }
}
