<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\BillingOperation;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeCreditNote;
use App\Models\Money\FeePayment;
use App\Models\Money\FeeRefund;
use App\Models\Money\FeeRefundAction;
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
use App\Services\Money\FeeCreditNotes;
use App\Services\Money\FeePaymentReconciliation;
use App\Services\Money\FeePayments;
use App\Services\Money\FeePaymentSettings;
use App\Services\Money\FeePlans;
use App\Services\Money\FeeRefundPolicies;
use App\Services\Money\FeeRefunds;
use App\Services\Money\FeeStatements;
use App\Services\ParentPolicyService;
use App\Services\Platform\Ledger\LedgerService;
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

class FeeRefundsTest extends TestCase
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

    public function test_reservation_is_permanent_and_allocations_cannot_spend_reserved_credit(): void
    {
        $this->bill();
        $this->policy();
        $this->refundPolicy();
        $p = $this->pay('9.00');
        $r = $this->prepare($p, '4.00');
        $op = $this->action();
        app(FeeRefunds::class)->reserve($this->admin, $r->id, $op);
        app(FeeRefunds::class)->reserve($this->admin, $r->id, $op);
        $this->assertSame(500, app(FeePayments::class)->available($p));
        $this->assertSame(1, FeeRefundAction::count());
        $this->apply($p);
        $this->assertSame(0, app(FeePayments::class)->available($p));
        $this->assertSame(500, (int) PaymentAllocation::sum('amount_cents'));
        $row = app(FeeStatements::class)->receipts($this->account)[0];
        $this->assertSame(400, $row['pending_refund_cents']);
        $this->assertSame(0, $row['refunded_cents']);
        $this->assertSame(900, $row['allocated_cents'] + $row['credit_cents'] + $row['pending_refund_cents'] + $row['refunded_cents']);
        $this->healthy();
    }

    public function test_verified_payout_retains_original_holder_pin_and_receipt_and_is_once_only(): void
    {
        $this->refundPolicy();
        $p = $this->pay('9.00');
        $raw = $p->getRawOriginal();
        $r = $this->reserve($p, '4.00');
        $op = $this->payout();
        app(FeeRefunds::class)->payout($this->admin, $r->id, $op);
        app(FeeRefunds::class)->payout($this->admin, $r->id, $op);
        $this->assertSame('paid', app(FeeRefunds::class)->state($r));
        $this->assertSame(2, FeeRefundAction::count());
        $this->assertSame($raw, $p->fresh()->getRawOriginal());
        $this->assertSame(500, app(FeePayments::class)->available($p));
        $this->assertSame(500, (int) DB::table('ledger_accounts')->where('type', 'cash_desk')->sum('balance_cents'));
        $this->assertSame(0, (int) DB::table('ledger_accounts')->where('type', 'fee_refund')->sum('balance_cents'));
        $row = app(FeeStatements::class)->receipts($this->account)[0];
        $this->assertSame(400, $row['refunded_cents']);
        $this->assertSame(0, $row['pending_refund_cents']);
        $this->refuses(fn () => app(FeeRefunds::class)->cancel($this->admin, $r->id, $this->action() + ['private_evidence' => 'Synthetic cancellation']), 409);
        $this->healthy();
    }

    public function test_cancellation_restores_original_restricted_credit_and_prevents_payout(): void
    {
        $this->refundPolicy();
        $p = $this->pay();
        $r = $this->reserve($p, '4.00');
        $op = $this->action() + ['private_evidence' => 'Original holder cancelled outside payout'];
        app(FeeRefunds::class)->cancel($this->admin, $r->id, $op);
        app(FeeRefunds::class)->cancel($this->admin, $r->id, $op);
        $this->assertSame(400, app(FeePayments::class)->available($p));
        $this->assertSame('cancelled', app(FeeRefunds::class)->state($r));
        $this->refuses(fn () => app(FeeRefunds::class)->payout($this->admin, $r->id, $this->payout()), 409);
        $this->assertSame($this->schoolA->ids['asha'], $p->fresh()->pinned_learner_id);
        $this->healthy();
    }

    public function test_changed_credit_and_policy_require_review_and_duplicate_payout_references_fail(): void
    {
        $this->refundPolicy();
        $p = $this->pay('9.00');
        $old = $this->prepare($p, '4.00');
        $r = $this->reserve($p, '2.00');
        $this->refuses(fn () => app(FeeRefunds::class)->reserve($this->admin, $old->id, $this->action()), 409);
        $this->refundPolicy(['revision' => 1, 'cash_allowed' => false]);
        $this->refuses(fn () => app(FeeRefunds::class)->payout($this->admin, $r->id, $this->payout()), 422);
        $this->refundPolicy(['revision' => 2]);
        app(FeeRefunds::class)->payout($this->admin, $r->id, $this->payout(['payout_reference' => 'SAME-REFERENCE']));
        $next = $this->reserve($p, '2.00');
        $this->refuses(fn () => app(FeeRefunds::class)->payout($this->admin, $next->id, $this->payout(['payout_reference' => ' same-reference '])), 409);
        $this->assertSame(500, app(FeePayments::class)->available($p));
        $this->healthy();
    }

    public function test_paid_invoice_credit_must_restore_receipt_before_refunding(): void
    {
        $this->bill();
        $this->policy();
        $this->refundPolicy();
        $p = $this->pay();
        $this->apply($p);
        $this->refuses(fn () => $this->prepare($p, '1.00'), 422);
        $this->postCredit(['amount' => '4.00']);
        $r = $this->reserve($p, '2.99');
        app(FeeRefunds::class)->payout($this->admin, $r->id, $this->payout());
        $this->assertSame(0, app(FeePayments::class)->available($p));
        $this->healthy();
    }

    public function test_roles_foreign_records_and_fresh_permission_revocation_are_refused(): void
    {
        $this->refundPolicy();
        $p = $this->pay();
        $input = $this->refundInput($p, '2.00');
        foreach (['farid', 'tara', 'asha'] as $name) {
            $this->refuses(fn () => app(FeeRefunds::class)->prepare(User::findOrFail($this->schoolA->ids[$name.'_user']), $input), 403);
        }$r = app(FeeRefunds::class)->prepare($this->admin, $input);
        SchoolMembership::create(['school_id' => 2, 'user_id' => $this->admin->id, 'role' => 'Super Admin', 'status' => 'active', 'starts_on' => '2026-01-01']);
        app(CurrentSchool::class)->run(2, function () use ($r, $input): void {
            $actor = $this->admin->fresh();
            $actor->assignRole('Super Admin');
            $this->refundPolicy();
            $this->refuses(fn () => app(FeeRefunds::class)->prepare($actor, $input), 404);
            $this->refuses(fn () => app(FeeRefunds::class)->reserve($actor, $r->id, $this->action()), 404);
        });
        app(FeeRefunds::class)->reserve($this->admin, $r->id, $this->action());
        $permission = DB::table('permissions')->where('name', 'fee-payments-manage')->where('guard_name', 'web')->value('id');
        DB::table('role_has_permissions')->where('permission_id', $permission)->whereIn('role_id', DB::table('roles')->where('school_id', 1)->select('id'))->delete();
        DB::table('model_has_permissions')->where('school_id', 1)->where('permission_id', $permission)->delete();
        $this->refuses(fn () => app(FeeRefunds::class)->payout($this->admin, $r->id, $this->payout()), 403);
        $this->assertSame(1, FeeRefundAction::count());
    }

    public function test_independent_approval_and_private_encrypted_evidence(): void
    {
        $this->refundPolicy(['independent_approval' => true]);
        $p = $this->pay();
        $r = $this->prepare($p, '2.00');
        $this->refuses(fn () => app(FeeRefunds::class)->reserve($this->admin, $r->id, $this->action()), 403);
        $other = User::findOrFail($this->schoolA->ids['tara_user']);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $other->id, 'role' => 'Super Admin', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $other->assignRole('Super Admin');
        app(FeeRefunds::class)->reserve($other, $r->id, $this->action());
        app(FeeRefunds::class)->payout($this->admin, $r->id, $this->payout());
        $this->assertStringNotContainsString('CONFIDENTIAL', DB::table('fee_refunds')->where('id', $r->id)->value('private_explanation'));
        $this->assertSame('CONFIDENTIAL original holder refund', $r->fresh()->private_explanation);
        $this->assertStringNotContainsString('CONFIDENTIAL', json_encode(DB::table('fee_refund_actions')->get(), JSON_THROW_ON_ERROR));
        $this->healthy();
    }

    public function test_action_failure_rolls_back_ledger_accounts_and_operation_then_retry_succeeds(): void
    {
        $this->refundPolicy();
        $p = $this->pay();
        $r = $this->prepare($p, '2.00');
        $op = $this->action();
        $counts = fn () => [FeeRefundAction::count(), BillingOperation::count(), DB::table('ledger_entries')->count(), DB::table('ledger_accounts')->count()];
        $before = $counts();
        $original = FeeRefundAction::getEventDispatcher();
        FeeRefundAction::setEventDispatcher(clone $original);
        FeeRefundAction::created(fn () => throw new RuntimeException('Synthetic reservation failure'));
        try {
            app(FeeRefunds::class)->reserve($this->admin, $r->id, $op);
            $this->fail('Expected rollback');
        } catch (RuntimeException $e) {
            $this->assertSame('Synthetic reservation failure', $e->getMessage());
        } finally {
            FeeRefundAction::setEventDispatcher($original);
        }$this->assertSame($before, $counts());
        $this->assertSame(400, app(FeePayments::class)->available($p));
        app(FeeRefunds::class)->reserve($this->admin, $r->id, $op);
        $this->healthy();
    }

    public function test_sql_history_and_populated_migration_are_retained(): void
    {
        $this->refundPolicy();
        $p = $this->pay();
        $r = $this->reserve($p, '2.00');
        foreach ([fn () => DB::table('fee_refunds')->update(['amount_cents' => 1]), fn () => DB::table('fee_refunds')->delete(), fn () => DB::table('fee_refund_actions')->update(['counter_account_id' => 1]), fn () => DB::table('fee_refund_actions')->delete()] as $write) {
            try {
                $write();
                $this->fail('Expected immutable refund');
            } catch (QueryException) {
                $this->addToAssertionCount(1);
            }
        }try {
            (require database_path('migrations/2026_10_27_960000_add_reserved_manual_fee_refunds.php'))->down();
            $this->fail('Expected retained migration');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Retain refund', $e->getMessage());
        }$this->healthy();
    }

    public function test_parent_contract_exposes_only_own_safe_refund_amounts_and_rechecks_guardian_visibility(): void
    {
        $this->refundPolicy();
        $p = $this->pay();
        $r = $this->reserve($p, '2.00');
        $farid = User::findOrFail($this->schoolA->ids['farid_user']);
        ParentPolicyService::acceptCurrent($farid);
        $url = '/api/v2/parent/fees/accounts/'.$this->account->id.'/statement';
        $response = $this->api($farid)->getJson($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.receipts.items.0.pending_refund_cents', 200)->assertJsonPath('data.receipts.items.0.refunded_cents', 0)->assertJsonPath('data.credit_cents', 200);
        $this->assertStringNotContainsString('CONFIDENTIAL', $response->getContent());
        $this->assertStringNotContainsString('holder_name', $response->getContent());
        app(FeeRefunds::class)->payout($this->admin, $r->id, $this->payout());
        $this->api($farid)->getJson($url)->assertOk()->assertJsonPath('data.receipts.items.0.pending_refund_cents', 0)->assertJsonPath('data.receipts.items.0.refunded_cents', 200);
        $mona = User::findOrFail($this->schoolA->ids['mona_user']);
        ParentPolicyService::acceptCurrent($mona);
        $this->api($mona)->getJson($url)->assertOk()->assertJsonCount(0, 'data.receipts.items');
        GuardianLink::where('guardian_user_id', $farid->id)->update(['can_see_fees' => false]);
        $this->api($farid)->getJson($url)->assertNotFound();
    }

    public function test_panel_review_payout_binding_private_errors_and_disabled_history(): void
    {
        $this->refundPolicy();
        $p = $this->pay();
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $this->actingAs($this->admin)->get('/fee-refunds')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/fee-refunds/new?payment_id='.$p->id)->assertOk()->assertSee('Available original receipt credit');
        $input = $this->refundInput($p, '2.00');
        $this->post('/fee-refunds/prepare', $input + [...$bind, 'expected_school_id' => 2])->assertForbidden();
        $this->post('/fee-refunds/prepare', $input + $bind)->assertStatus(303);
        $r = FeeRefund::sole();
        $url = '/fee-refunds/'.$r->id;
        $this->get($url)->assertOk()->assertSee('CONFIDENTIAL original holder refund');
        $this->post($url.'/reserve', $this->action() + $bind)->assertStatus(303);
        $this->post($url.'/payout', [...$this->payout(), 'verified_original_holder' => false] + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $this->post($url.'/payout', $this->payout() + $bind)->assertStatus(303);
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->get($url)->assertOk()->assertSee('Refund history is read-only while fees are disabled.');
        $this->post($url.'/cancel', $this->action() + ['private_evidence' => 'No payout happened'] + $bind)->assertForbidden();
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->actingAs($teacher)->get($url)->assertForbidden()->assertDontSee('CONFIDENTIAL');
        $this->get('/fee-refunds')->assertForbidden();
    }

    public function test_missing_explicit_policy_and_insufficient_method_assets_refuse_without_creating_money(): void
    {
        $p = $this->pay();
        $this->refuses(fn () => $this->prepare($p, '2.00'), 422);
        $this->refundPolicy();
        $input = [...$this->refundInput($p, '2.00'), 'method' => 'eft'];
        $r = app(FeeRefunds::class)->prepare($this->admin, $input);
        app(FeeRefunds::class)->reserve($this->admin, $r->id, $this->action());
        $this->refuses(fn () => app(FeeRefunds::class)->payout($this->admin, $r->id, $this->payout()), 422);
        $this->assertSame('reserved', app(FeeRefunds::class)->state($r));
        $this->assertSame(1, FeeRefundAction::count());
        $this->healthy();
    }

    public function test_database_refuses_terminal_action_without_original_reservation_posting(): void
    {
        $this->refundPolicy();
        $p = $this->pay();
        $r = $this->reserve($p, '2.00');
        $raw = FeeRefundAction::sole()->getRawOriginal();
        unset($raw['id']);
        $raw['operation_id'] = (string) Str::uuid();
        $raw['action'] = 'paid';
        $raw['reference_hash'] = str_repeat('a', 64);
        $raw['private_evidence'] = encrypt('Synthetic proof');
        try {
            DB::table('fee_refund_actions')->insert($raw);
            $this->fail('Expected invalid paid posting refusal');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }$this->healthy();
    }

    public function test_current_separate_approval_requirement_applies_before_outside_payout(): void
    {
        $this->refundPolicy();
        $p = $this->pay();
        $r = $this->reserve($p, '2.00');
        $this->refundPolicy(['revision' => 1, 'independent_approval' => true]);
        $this->refuses(fn () => app(FeeRefunds::class)->payout($this->admin, $r->id, $this->payout()), 409);
        $this->assertSame('reserved', app(FeeRefunds::class)->state($r));
        $this->healthy();
    }

    private function healthy(): void
    {
        $report = app(FeePaymentReconciliation::class)->inspect();
        $this->assertTrue(FeePaymentReconciliation::healthy($report), json_encode($report));
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        $this->assertSame(0, (int) DB::table('ledger_accounts')->whereIn('type', ['clearing', 'due_to_school'])->sum('balance_cents'));
    }

    private function refundPolicy(array $changes = []): void
    {
        app(FeeRefundPolicies::class)->save($this->admin, array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true, 'independent_approval' => false], $changes));
    }

    private function pay(string $amount = '4.00'): FeePayment
    {
        return app(FeePayments::class)->record($this->admin, $this->receipt(['amount' => $amount, 'pinned_learner_id' => $this->schoolA->ids['asha']]));
    }

    private function prepare(FeePayment $p, string $amount): FeeRefund
    {
        return app(FeeRefunds::class)->prepare($this->admin, $this->refundInput($p, $amount));
    }

    private function reserve(FeePayment $p, string $amount): FeeRefund
    {
        $r = $this->prepare($p, $amount);
        app(FeeRefunds::class)->reserve($this->admin, $r->id, $this->action());

        return $r;
    }

    private function refundInput(FeePayment $p, string $amount): array
    {
        return ['operation_id' => (string) Str::uuid(), 'payment_id' => $p->id, 'amount' => $amount, 'method' => 'cash', 'private_explanation' => 'CONFIDENTIAL original holder refund'];
    }

    private function action(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'confirmed' => true];
    }

    private function payout(array $changes = []): array
    {
        return array_replace($this->action() + ['verified_original_holder' => true, 'payout_reference' => 'REFUND-'.Str::uuid(), 'private_evidence' => 'CONFIDENTIAL verified outside payout'], $changes);
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
            $this->assertSame($status, $e->getStatusCode(), $e->getMessage());
        }
    }
}
