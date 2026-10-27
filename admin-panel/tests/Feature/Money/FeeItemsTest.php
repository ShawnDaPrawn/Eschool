<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\BillRun;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeCharge;
use App\Models\Money\FeeCreditNote;
use App\Models\Money\FeeItem;
use App\Models\Money\FeePayment;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\Money\PaymentAllocation;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\BillRuns;
use App\Services\Money\FeeAccounts;
use App\Services\Money\FeeAllocations;
use App\Services\Money\FeeCharges;
use App\Services\Money\FeeCreditNotes;
use App\Services\Money\FeeItems;
use App\Services\Money\FeePaymentReconciliation;
use App\Services\Money\FeePayments;
use App\Services\Money\FeePaymentSettings;
use App\Services\Money\FeePlans;
use App\Services\Money\FeeStatements;
use App\Services\Money\ParentFeeStatements;
use App\Services\ParentPolicyService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class FeeItemsTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private User $admin;

    private FeeAccount $account;

    private int $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDateTime(2026, 6, 15, 12, 0, 0));
        $this->setUpTwoSchools();
        $this->admin = $this->superAdmin();
        $this->year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        SchoolMembership::where('school_id', 1)->update(['starts_on' => '2026-01-01']);
        Enrolment::where('session_year_id', $this->year)->delete();
        foreach (['asha' => 'a', 'ben' => 'a', 'cara' => 'c'] as $name => $section) {
            Enrolment::create(['learner_id' => $this->schoolA->ids[$name], 'class_section_id' => $this->schoolA->ids['section_'.$section],
                'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'legacy_dates' => false, 'reason' => 'Synthetic receipt allocation']);
        }
        foreach (['asha', 'cara'] as $learner) {
            foreach (['farid', 'mona'] as $parent) {
                GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids[$learner], 'guardian_user_id' => $this->schoolA->ids[$parent.'_user']],
                    ['status' => 'active', 'relationship' => 'guardian', 'can_see_fees' => true, 'can_approve' => true]);
            }
        }
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->schoolA->ids['gina_user']],
            ['status' => 'active', 'relationship' => 'guardian', 'can_see_fees' => true, 'can_approve' => true]);
        app(FeePlans::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Annual tuition', 'session_year_id' => $this->year,
            'scope' => 'all', 'target_id' => null, 'currency' => 'ZAR', 'amount' => '10.01', 'state' => 'active']);
        $this->account = app(FeeAccounts::class)->save($this->admin, $this->family());
        app(FeeAccounts::class)->save($this->admin, $this->family(['name' => 'Gina family', 'learner_ids' => [$this->schoolA->ids['ben']],
            'holders' => [['user_id' => $this->schoolA->ids['gina_user'], 'share_percent' => '100']]]));
    }

    public function test_learner_charge_posts_a_new_immutable_invoice_and_does_not_suppress_annual_tuition(): void
    {
        $item = $this->item();
        $input = $this->charge($item);
        $preview = app(FeeCharges::class)->preview($this->admin, $input);
        $this->assertSame(1001, $preview['amount_cents']);
        $this->assertCount(1, $preview['lines']);
        $charge = app(FeeCharges::class)->post($this->admin, $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true]);
        $this->assertNotNull($charge->sealed_at);
        $this->assertSame(1001, (int) InvoiceLine::whereNotNull('fee_charge_id')->sum('amount_cents'));
        $this->bill();
        $this->assertSame(4004, (int) InvoiceLine::sum('amount_cents'));
        $this->assertSame(4, InvoiceLine::count());
        $this->assertSame(1, FeeCharge::count());
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    public function test_family_item_charges_once_with_exact_grand_holder_shares_and_can_coexist_with_annual_invoices(): void
    {
        $this->bill();
        $item = $this->item(['scope' => 'family']);
        $charge = $this->postCharge($item);
        $s = $charge->snapshot;
        $this->assertSame([501, 500], array_column($s['lines'], 'amount_cents'));
        $this->assertSame([501, 500], array_column($s['holder_totals'], 'amount_cents'));
        $this->assertSame(4004, (int) InvoiceLine::sum('amount_cents'));
        $this->assertSame(1503, app(ParentFeeStatements::class)->show(User::findOrFail($this->schoolA->ids['farid_user']), $this->account->id)['charged_cents']);
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
        foreach ([fn () => DB::table('fee_charges')->where('id', $charge->id)->update(['amount_cents' => 1]), fn () => DB::table('invoice_lines')->whereNotNull('fee_charge_id')->delete()] as $write) {
            try {
                $write();
                $this->fail('Expected immutable charge refusal');
            } catch (QueryException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_occurrence_and_uuid_replay_cannot_rebill_a_target_but_another_occurrence_can(): void
    {
        $item = $this->item();
        $input = $this->charge($item);
        $p = app(FeeCharges::class)->preview($this->admin, $input);
        $post = $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true];
        $charge = app(FeeCharges::class)->post($this->admin, $post);
        $this->travel(2)->days();
        $this->assertSame($charge->id, app(FeeCharges::class)->post($this->admin, $post)->id);
        $this->refuses(fn () => app(FeeCharges::class)->post($this->admin, [...$post, 'operation_id' => (string) Str::uuid()]), 409);
        $this->postCharge($item, ['source_reference' => 'ANOTHER-OCCURRENCE']);
        $this->assertSame(2, FeeCharge::count());
    }

    public function test_catalogue_changes_invalidate_review_without_rewriting_old_charges(): void
    {
        $item = $this->item();
        $input = $this->charge($item);
        $p = app(FeeCharges::class)->preview($this->admin, $input);
        $item = app(FeeItems::class)->save($this->admin, $this->itemInput(['revision' => 1, 'amount' => '12.00']), $item->id);
        $this->refuses(fn () => app(FeeCharges::class)->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]), 409);
        $charge = $this->postCharge($item);
        $this->assertSame(1200, $charge->amount_cents);
        $this->refuses(fn () => app(FeeItems::class)->save($this->admin, $this->itemInput(['revision' => 2, 'scope' => 'family']), $item->id), 422);
        app(FeeItems::class)->save($this->admin, $this->itemInput(['revision' => 2, 'state' => 'archived']), $item->id);
        $this->assertSame(1200, Invoice::where('bill_run_id', $charge->bill_run_id)->sole()->total_cents);
        $this->refuses(fn () => app(FeeCharges::class)->preview($this->admin, $this->charge($item, ['source_reference' => 'NEW'])), 422);
    }

    public function test_oldest_first_can_pay_an_item_but_tuition_first_reuses_its_credited_funds_for_tuition(): void
    {
        $item = $this->item();
        $charge = $this->postCharge($item, ['due_on' => '2026-06-15']);
        $this->bill();
        $this->policy();
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt());
        $this->apply($payment);
        $itemLine = InvoiceLine::where('fee_charge_id', $charge->id)->sole();
        $this->assertSame((int) $itemLine->id, (int) PaymentAllocation::sole()->invoice_line_id);
        $note = $this->postCredit(['invoice_line_id' => $itemLine->id, 'amount' => '4.00']);
        $this->assertSame(299, $note->released_cents);
        $this->assertSame(101, $note->unpaid_cents);
        $this->policy('tuition_first', 1);
        $preview = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        $this->assertSame(299, $preview['amount_cents']);
        $target = InvoiceLine::findOrFail($preview['rows'][0]['invoice_line_id']);
        $this->assertNull($target->fee_charge_id);
        $this->apply($payment);
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    public function test_family_item_apportionment_does_not_let_pinned_money_pay_a_sibling_or_another_holder(): void
    {
        $this->bill();
        $charge = $this->postCharge($this->item(['scope' => 'family']));
        $this->policy('tuition_first');
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt(['amount' => '9.00', 'pinned_learner_id' => $this->schoolA->ids['cara']]));
        $this->apply($payment);
        $this->assertSame(149, app(FeePayments::class)->available($payment));
        $line = InvoiceLine::where('fee_charge_id', $charge->id)->where('learner_id', $this->schoolA->ids['cara'])->sole();
        $this->postCredit(['invoice_line_id' => $line->id, 'amount' => '2.50']);
        $this->assertSame(399, app(FeePayments::class)->available($payment));
        $this->assertSame(0, app(FeeAllocations::class)->preview($this->admin, $payment->id)['amount_cents']);
        $this->assertSame([$this->schoolA->ids['cara']], InvoiceLine::whereIn('id', PaymentAllocation::pluck('invoice_line_id'))->pluck('learner_id')->unique()->all());
    }

    public function test_parent_item_contract_preserves_ownership_and_hides_private_occurrence_reference(): void
    {
        $charge = $this->postCharge($this->item(['scope' => 'family']), ['source_reference' => 'PRIVATE-OCCURRENCE']);
        $farid = User::findOrFail($this->schoolA->ids['farid_user']);
        ParentPolicyService::acceptCurrent($farid);
        $url = '/api/v2/parent/fees/accounts/'.$this->account->id.'/statement';
        $response = $this->api($farid)->getJson($url)->assertOk()->assertJsonPath('data.charged_cents', 501)->assertJsonPath('data.charges.items.0.charge_kind', 'fee_item')->assertJsonPath('data.charges.items.0.charge_scope', 'family');
        $this->assertStringNotContainsString('PRIVATE-OCCURRENCE', $response->getContent());
        $this->assertStringNotContainsString('reference_hash', $response->getContent());
        $this->assertStringNotContainsString('fee_charge_id', $response->getContent());
        $raw = DB::table('fee_charges')->where('id', $charge->id)->value('source_reference');
        $this->assertNotSame('PRIVATE-OCCURRENCE', $raw);
        GuardianLink::where('guardian_user_id', $farid->id)->where('learner_id', $this->schoolA->ids['asha'])->update(['can_see_fees' => false]);
        $this->api($farid)->getJson($url)->assertOk()->assertJsonPath('data.charged_cents', 250)->assertJsonPath('data.charges.total', 1);
        $gina = User::findOrFail($this->schoolA->ids['gina_user']);
        ParentPolicyService::acceptCurrent($gina);
        $this->api($gina)->getJson($url)->assertNotFound()->assertDontSee('Synthetic excursion');
    }

    public function test_later_invoice_line_failure_rolls_back_money_documents_audit_outbox_and_permanent_receipt(): void
    {
        $item = $this->item(['scope' => 'family']);
        $input = $this->charge($item);
        $p = app(FeeCharges::class)->preview($this->admin, $input);
        $post = $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true];
        $counts = fn () => array_map(fn ($table) => DB::table($table)->count(), ['fee_charges', 'bill_runs', 'invoices', 'invoice_lines', 'ledger_entries', 'ledger_postings', 'billing_operations', 'audit_log', 'domain_events']);
        $before = $counts();
        $original = InvoiceLine::getEventDispatcher();
        $this->assertNotNull($original);
        InvoiceLine::setEventDispatcher(clone $original);
        $writes = 0;
        InvoiceLine::created(function () use (&$writes): void {
            if (++$writes === 2) {
                throw new RuntimeException('Synthetic later item line failure');
            }
        });
        try {
            app(FeeCharges::class)->post($this->admin, $post);
            $this->fail('Expected rollback');
        } catch (RuntimeException $e) {
            $this->assertSame('Synthetic later item line failure', $e->getMessage());
        } finally {
            InvoiceLine::setEventDispatcher($original);
        }
        $this->assertSame($before, $counts());
        $this->assertNotNull(app(FeeCharges::class)->post($this->admin, $post)->sealed_at);
    }

    public function test_fresh_guardian_changes_and_wrong_targets_or_dates_refuse_a_charge(): void
    {
        $item = $this->item(['scope' => 'family']);
        $input = $this->charge($item);
        $p = app(FeeCharges::class)->preview($this->admin, $input);
        GuardianLink::where('guardian_user_id', $this->schoolA->ids['farid_user'])->where('learner_id', $this->schoolA->ids['cara'])->update(['can_see_fees' => false]);
        $this->refuses(fn () => app(FeeCharges::class)->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]), 422);
        $other = $this->item();
        foreach ([['learner_id' => $this->schoolA->ids['ben']], ['charged_on' => '2027-01-01', 'due_on' => '2027-01-02'], ['charged_on' => '2025-12-31'], ['charged_on' => '2026-06-16']] as $bad) {
            $this->refuses(fn () => app(FeeCharges::class)->preview($this->admin, $this->charge($other, $bad)), 422);
        }
        $this->assertSame(0, FeeCharge::count());
    }

    public function test_changed_occurrence_or_confirmation_cannot_reuse_a_review(): void
    {
        $item = $this->item();
        $input = $this->charge($item);
        $p = app(FeeCharges::class)->preview($this->admin, $input);
        $post = $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true];
        $this->refuses(fn () => app(FeeCharges::class)->post($this->admin, [...$post, 'source_reference' => 'DIFFERENT-OCCURRENCE']), 409);
        $this->refuses(fn () => app(FeeCharges::class)->post($this->admin, [...$post, 'confirmed' => false]), 422);
        $this->assertSame(0, FeeCharge::count());
    }

    public function test_roles_actual_foreign_items_and_fresh_permission_revocation_are_refused(): void
    {
        $item = $this->item();
        $input = $this->charge($item);
        foreach (['tara', 'farid', 'asha'] as $name) {
            $this->refuses(fn () => app(FeeCharges::class)->preview(User::findOrFail($this->schoolA->ids[$name.'_user']), $input), 403);
        }
        SchoolMembership::create(['school_id' => 2, 'user_id' => $this->admin->id, 'role' => 'Super Admin', 'status' => 'active', 'starts_on' => '2026-01-01']);
        app(CurrentSchool::class)->run(2, function () use ($input): void {
            $actor = $this->admin->fresh();
            $actor->assignRole('Super Admin');
            $this->refuses(fn () => app(FeeCharges::class)->preview($actor, $input), 404);
        });
        $p = app(FeeCharges::class)->preview($this->admin, $input);
        $post = $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true];
        $charge = app(FeeCharges::class)->post($this->admin, $post);
        $permission = DB::table('permissions')->where('name', 'fee-items-manage')->value('id');
        DB::table('role_has_permissions')->where('permission_id', $permission)->whereIn('role_id', DB::table('roles')->where('school_id', 1)->select('id'))->delete();
        $this->refuses(fn () => app(FeeCharges::class)->post($this->admin, $post), 403);
        $this->actingAs($this->admin)->get('/fee-item-charges/'.$charge->id)->assertForbidden();
        $this->get('/fee-items')->assertForbidden();
    }

    public function test_panel_create_review_confirmation_replay_and_disabled_history(): void
    {
        $bind = ['expected_school_id' => 1, 'expected_account_id' => $this->admin->id];
        $input = $this->itemInput();
        $this->actingAs($this->admin)->get('/fee-items')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/fee-items/new')->assertOk();
        $this->post('/fee-items/new', $input + $bind)->assertStatus(303);
        $this->post('/fee-items/new', $input + $bind)->assertStatus(303);
        $item = FeeItem::sole();
        $this->get('/fee-items/'.$item->id.'/edit')->assertOk()->assertSee('10.01');
        $query = '?fee_item_id='.$item->id.'&fee_account_id='.$this->account->id;
        $this->get('/fee-item-charges/new'.$query)->assertOk()->assertSee('Unique occurrence reference');
        $input = $this->charge($item);
        $this->post('/fee-item-charges/review', $input + $bind)->assertOk()->assertSee('Post item invoice once');
        $p = app(FeeCharges::class)->preview($this->admin, $input);
        $post = $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true] + $bind;
        $this->post('/fee-item-charges/new', [...$post, 'confirmed' => false])->assertStatus(422)->assertSessionMissing('_old_input');
        $this->post('/fee-item-charges/new', $post)->assertStatus(303);
        $this->post('/fee-item-charges/new', $post)->assertStatus(303);
        $charge = FeeCharge::sole();
        $this->get('/fee-item-charges/'.$charge->id)->assertOk()->assertSee('Synthetic excursion');
        $this->get('/fee-item-charges/'.$charge->id.'/invoice')->assertOk()->assertSee('No VAT charged on this invoice.');
        app(ModuleGate::class)->change('fees', false, $this->admin);
        foreach (['/fee-items', '/fee-item-charges', '/fee-item-charges/'.$charge->id, '/fee-item-charges/'.$charge->id.'/invoice'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->post('/fee-item-charges/new', $post)->assertForbidden();
        $this->post('/fee-items/new', $this->itemInput() + $bind)->assertForbidden();
    }

    public function test_private_panel_bindings_wrong_roles_and_actual_foreign_records_are_refused(): void
    {
        $item = $this->item();
        $input = $this->charge($item);
        $bind = ['expected_school_id' => 1, 'expected_account_id' => $this->admin->id];
        $this->actingAs($this->admin)->post('/fee-item-charges/review', $input + [...$bind, 'expected_school_id' => 2])->assertForbidden();
        $this->post('/fee-item-charges/review', $input + [...$bind, 'expected_account_id' => 999999])->assertForbidden();
        $this->post('/fee-item-charges/review?unexpected=1', $input + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $foreign = app(CurrentSchool::class)->run(2, fn () => FeeItem::create(['name' => 'Private Baobab item', 'scope' => 'learner', 'amount_cents' => 100, 'currency' => 'ZAR', 'tax_treatment' => 'no_vat', 'state' => 'active', 'revision' => 1]));
        $this->get('/fee-items/'.$foreign->id.'/edit')->assertNotFound()->assertDontSee('Private Baobab item');
        $this->get('/fee-item-charges/new?fee_item_id='.$foreign->id)->assertNotFound();
        $this->post('/fee-item-charges/review', [...$input, 'fee_item_id' => $foreign->id] + $bind)->assertNotFound();
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->actingAs($teacher)->get('/fee-items')->assertForbidden();
        $this->get('/fee-item-charges')->assertForbidden();
    }

    public function test_tiny_family_charges_keep_zero_rows_out_of_invoices_and_retained_schema_refuses_rollback(): void
    {
        $charge = $this->postCharge($this->item(['scope' => 'family', 'amount' => '0.01']));
        $this->assertSame(1, InvoiceLine::where('fee_charge_id', $charge->id)->count());
        $this->assertSame(1, $charge->amount_cents);
        $this->assertSame([1, 0], array_column($charge->snapshot['holder_totals'], 'amount_cents'));
        $migration = require database_path('migrations/2026_10_27_400000_create_fee_items.php');
        try {
            $migration->down();
            $this->fail('Expected retained history refusal');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Retain fee item', $e->getMessage());
        }
    }

    public function test_occurrence_identity_survives_application_key_rotation(): void
    {
        $item = $this->item();
        $this->postCharge($item);
        $prior = config('app.key');
        config(['app.key' => 'base64:'.base64_encode(str_repeat('x', 32))]);
        try {
            $this->refuses(fn () => app(FeeCharges::class)->preview($this->admin, $this->charge($item)), 409);
        } finally {
            config(['app.key' => $prior]);
        }
        $this->assertSame(1, FeeCharge::count());
    }

    public function test_incomplete_item_charge_blocks_reads_and_projection_rebuild(): void
    {
        $charge = $this->postCharge($this->item());
        $original = BillRun::findOrFail($charge->bill_run_id);
        $run = BillRun::create($original->only(['session_year_id', 'as_of', 'due_on', 'tax_treatment', 'fingerprint', 'snapshot', 'prepared_by']) + ['kind' => 'fee_item', 'state' => 'preview', 'revision' => 1]);
        $draft = $charge->replicate(['sealed_at']);
        $draft->bill_run_id = $run->id;
        $draft->reference_hash = hash('sha256', 'synthetic-draft');
        $draft->sealed_at = null;
        $draft->save();
        $this->assertSame(1, app(FeePaymentReconciliation::class)->inspect()['incomplete_fee_charges']);
        $this->refuses(fn () => app(FeeStatements::class)->finance($this->account), 409);
        $this->artisan('ledger:reconcile --school=1 --rebuild --json')->assertExitCode(1);
    }

    private function itemInput(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic excursion', 'scope' => 'learner', 'amount' => '10.01', 'currency' => 'ZAR', 'tax_treatment' => 'no_vat', 'state' => 'active'], $changes);
    }

    private function item(array $changes = []): FeeItem
    {
        return app(FeeItems::class)->save($this->admin, $this->itemInput($changes));
    }

    private function charge(FeeItem $item, array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'fee_item_id' => $item->id, 'fee_account_id' => $this->account->id, 'session_year_id' => $this->year, 'learner_id' => $item->scope === 'family' ? null : $this->schoolA->ids['asha'], 'charged_on' => '2026-06-15', 'due_on' => '2026-07-01', 'source_reference' => 'TRIP-2026'], $changes);
    }

    private function postCharge(FeeItem $item, array $changes = []): FeeCharge
    {
        $input = $this->charge($item, $changes);
        $p = app(FeeCharges::class)->preview($this->admin, $input);

        return app(FeeCharges::class)->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]);
    }

    private function api(User $actor): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($actor->createToken('synthetic-credit-test')->plainTextToken)->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $actor->id]);
    }

    private function line(): InvoiceLine
    {
        return InvoiceLine::where('learner_id', $this->schoolA->ids['asha'])->firstOrFail();
    }

    private function credit(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'invoice_line_id' => $this->line()->id, 'holder_user_id' => $this->schoolA->ids['farid_user'], 'amount' => '2.00', 'reason_code' => 'billing_error', 'reason' => 'Synthetic private correction'], $changes);
    }

    private function postCredit(array $changes = []): FeeCreditNote
    {
        $input = $this->credit($changes);
        $preview = app(FeeCreditNotes::class)->preview($this->admin, $input);

        return app(FeeCreditNotes::class)->post($this->admin, $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true]);
    }

    private function apply(FeePayment $payment): void
    {
        $preview = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        app(FeeAllocations::class)->post($this->admin, $payment->id, $this->allocate($preview));
    }

    private function bill(): void
    {
        $run = app(BillRuns::class)->prepare($this->admin, ['operation_id' => (string) Str::uuid(), 'session_year_id' => $this->year, 'as_of' => '2026-06-15', 'due_on' => '2026-07-01', 'tax_treatment' => 'no_vat']);
        app(BillRuns::class)->approve($this->admin, $run->id, ['operation_id' => (string) Str::uuid(), 'revision' => 1]);
        app(BillRuns::class)->post($this->admin, $run->id, ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'confirmed' => true]);
    }

    private function policy(string $order = 'oldest_first', int $revision = 0): void
    {
        app(FeePaymentSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => $revision, 'allocation_order' => $order]);
    }

    private function family(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic split family', 'currency' => 'ZAR', 'session_year_id' => $this->year,
            'learner_ids' => [$this->schoolA->ids['asha'], $this->schoolA->ids['cara']], 'holders' => [['user_id' => $this->schoolA->ids['farid_user'], 'share_percent' => '50'], ['user_id' => $this->schoolA->ids['mona_user'], 'share_percent' => '50']]], $changes);
    }

    private function receipt(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'fee_account_id' => $this->account->id, 'holder_user_id' => $this->schoolA->ids['farid_user'], 'pinned_learner_id' => null,
            'method' => 'cash', 'source_reference' => 'SYNTHETIC-'.Str::uuid(), 'amount' => '4.00', 'received_on' => '2026-06-15', 'confirmed' => true], $changes);
    }

    private function allocate(array $preview): array
    {
        return ['operation_id' => (string) Str::uuid(), 'fingerprint' => $preview['fingerprint'], 'confirmed' => true];
    }

    private function refuses(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Expected financial operation refusal');
        } catch (ValidationException) {
            $this->assertSame(422, $status);
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($status, $e->getStatusCode());
        }
    }
}
