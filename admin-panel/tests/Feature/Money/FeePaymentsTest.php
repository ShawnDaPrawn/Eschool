<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\BillingOperation;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeePayment;
use App\Models\Money\PaymentAllocation;
use App\Models\Money\PaymentAllocationRun;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\BillRuns;
use App\Services\Money\FeeAccounts;
use App\Services\Money\FeeAllocations;
use App\Services\Money\FeePaymentReconciliation;
use App\Services\Money\FeePayments;
use App\Services\Money\FeePaymentSettings;
use App\Services\Money\FeePlans;
use App\Services\Money\FeeStatements;
use App\Services\Money\ParentFeeStatements;
use App\Services\ParentPolicyService;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
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

class FeePaymentsTest extends TestCase
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

    public function test_receipts_are_balanced_held_credit_with_permanent_uuid_and_business_source_uniqueness(): void
    {
        $this->bill();
        $input = $this->receipt(['source_reference' => ' cash-001 ']);
        $payment = app(FeePayments::class)->record($this->admin, $input);
        $this->assertSame(400, $payment->amount_cents);
        $this->assertSame('CASH-001', $payment->source_reference);
        $this->assertSame('school', $payment->collected_by);
        $this->assertSame(0, $payment->fee_cents);
        $this->assertSame(400, $payment->net_cents);
        $this->assertSame($payment->id, app(FeePayments::class)->record($this->admin, $input)->id);
        $this->refuses(fn () => app(FeePayments::class)->record($this->admin, [...$input, 'operation_id' => (string) Str::uuid()]), 409);
        $this->refuses(fn () => app(FeePayments::class)->record($this->admin, [...$input, 'amount' => '4.01']), 409);
        $statement = app(FeeStatements::class)->finance($this->account->fresh());
        $this->assertSame(2002, $statement['outstanding_cents']);
        $this->assertSame(400, $statement['credit_cents']);
        $this->assertSame(400, app(LedgerService::class)->balance($payment->credit_account_id)->cents);
        $this->assertSame(3003, (int) DB::table('ledger_accounts')->where('type', 'fee_income')->sum('balance_cents'));
        $this->assertSame(3, DB::table('ledger_entries')->count());
        $this->assertSame(1, DB::table('domain_events')->where('type', 'fees.payment_recorded')->count());
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
    }

    public function test_partial_payment_settles_only_its_explicit_holders_share_without_changing_income(): void
    {
        $this->bill();
        $this->policy();
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt(['method' => 'eft']));
        $preview = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        $this->assertSame(400, $preview['amount_cents']);
        $this->assertSame(1, count($preview['rows']));
        $run = app(FeeAllocations::class)->post($this->admin, $payment->id, $this->allocate($preview));
        $this->assertNotNull($run->sealed_at);
        $rows = app(FeeStatements::class)->charges($this->account, $this->schoolA->ids['farid_user']);
        $this->assertSame(602, array_sum(array_column($rows, 'outstanding_cents')));
        $other = app(FeeStatements::class)->charges($this->account, $this->schoolA->ids['mona_user']);
        $this->assertSame(1000, array_sum(array_column($other, 'outstanding_cents')));
        $this->assertSame(1602, app(FeeStatements::class)->finance($this->account->fresh())['outstanding_cents']);
        $this->assertSame(0, app(LedgerService::class)->balance($payment->credit_account_id)->cents);
        $this->assertSame(400, (int) DB::table('ledger_accounts')->where('type', 'fee_bank')->sum('balance_cents'));
        $this->assertSame(3003, (int) DB::table('ledger_accounts')->where('type', 'fee_income')->sum('balance_cents'));
        $this->assertSame(0, DB::table('ledger_accounts')->whereIn('type', ['clearing', 'due_to_school'])->count());
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
    }

    public function test_overpayment_and_child_pinning_hold_credit_without_paying_a_sibling_or_another_holder(): void
    {
        $this->bill();
        $this->policy();
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt(['amount' => '20.00', 'pinned_learner_id' => $this->schoolA->ids['asha']]));
        $preview = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        $this->assertSame(501, $preview['amount_cents']);
        $this->assertSame(1499, $preview['credit_left_cents']);
        app(FeeAllocations::class)->post($this->admin, $payment->id, $this->allocate($preview));
        $this->assertSame(1499, app(FeePayments::class)->available($payment));
        $this->assertSame(501, array_sum(array_column(app(FeeStatements::class)->charges($this->account, $this->schoolA->ids['farid_user']), 'outstanding_cents')));
        $next = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        $this->assertSame(0, $next['amount_cents']);
        $this->refuses(fn () => app(FeeAllocations::class)->post($this->admin, $payment->id, $this->allocate($next)), 422);
        $this->assertSame(1, PaymentAllocation::count());
    }

    public function test_sibling_allocation_is_stable_and_unpinned_excess_remains_with_the_holder(): void
    {
        $this->bill();
        $this->policy('tuition_first');
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt(['amount' => '20.00']));
        $preview = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        $this->assertSame([501, 501], array_column($preview['rows'], 'amount_cents'));
        $this->assertSame(1002, $preview['amount_cents']);
        app(FeeAllocations::class)->post($this->admin, $payment->id, $this->allocate($preview));
        $statement = app(FeeStatements::class)->finance($this->account->fresh());
        $this->assertSame(1000, $statement['outstanding_cents']);
        $this->assertSame(998, $statement['credit_cents']);
        $this->assertSame(998, app(LedgerService::class)->balance($payment->credit_account_id)->cents);
    }

    public function test_prepayment_is_held_until_invoices_and_an_explicit_school_policy_exist(): void
    {
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt());
        $preview = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        $this->assertSame(0, $preview['policy_revision']);
        $this->assertSame(0, $preview['amount_cents']);
        $this->assertSame(400, $preview['credit_left_cents']);
        $this->bill();
        $this->policy();
        $new = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        $this->refuses(fn () => app(FeeAllocations::class)->post($this->admin, $payment->id, $this->allocate($preview)), 409);
        $this->assertSame(400, app(FeeAllocations::class)->post($this->admin, $payment->id, $this->allocate($new))->amount_cents);
    }

    public function test_competing_previews_and_durable_replay_never_consume_credit_twice(): void
    {
        $this->bill();
        $this->policy();
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt());
        $preview = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        $input = $this->allocate($preview);
        $run = app(FeeAllocations::class)->post($this->admin, $payment->id, $input);
        $this->travel(3)->days();
        $this->assertSame($run->id, app(FeeAllocations::class)->post($this->admin, $payment->id, $input)->id);
        $this->refuses(fn () => app(FeeAllocations::class)->post($this->admin, $payment->id, $this->allocate($preview)), 409);
        $this->assertSame(1, PaymentAllocationRun::count());
        $this->assertSame(1, PaymentAllocation::count());
        $this->assertSame(1, DB::table('domain_events')->where('type', 'fees.payment_allocated')->count());
    }

    public function test_changed_policy_invalidates_a_retained_allocation_preview(): void
    {
        $this->bill();
        $this->policy();
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt());
        $preview = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        $this->policy('tuition_first', 1);
        $this->refuses(fn () => app(FeeAllocations::class)->post($this->admin, $payment->id, $this->allocate($preview)), 409);
        $this->assertSame(0, PaymentAllocationRun::count());
        $this->assertSame(400, app(FeePayments::class)->available($payment));
    }

    public function test_synthetic_second_row_failure_rolls_back_money_history_events_and_receipt(): void
    {
        $this->bill();
        $this->policy();
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt(['amount' => '9.00']));
        $input = $this->allocate(app(FeeAllocations::class)->preview($this->admin, $payment->id));
        $original = PaymentAllocation::getEventDispatcher();
        PaymentAllocation::setEventDispatcher(clone $original);
        $count = 0;
        PaymentAllocation::created(function () use (&$count): void {
            if (++$count === 2) {
                throw new RuntimeException('Synthetic second allocation failure');
            }
        });
        try {
            app(FeeAllocations::class)->post($this->admin, $payment->id, $input);
            $this->fail('Expected synthetic allocation failure');
        } catch (RuntimeException $e) {
            $this->assertSame('Synthetic second allocation failure', $e->getMessage());
        } finally {
            PaymentAllocation::setEventDispatcher($original);
        }
        $this->assertSame(0, PaymentAllocation::count());
        $this->assertSame(0, PaymentAllocationRun::count());
        $this->assertSame(3, DB::table('ledger_entries')->count());
        $this->assertSame(900, app(LedgerService::class)->balance($payment->credit_account_id)->cents);
        $this->assertSame(0, BillingOperation::where('operation_id', $input['operation_id'])->count());
        $this->assertSame(0, DB::table('domain_events')->where('type', 'fees.payment_allocated')->count());
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        $this->assertSame(900, app(FeeAllocations::class)->post($this->admin, $payment->id, $input)->amount_cents);
    }

    public function test_posted_receipts_batches_and_allocations_are_database_immutable(): void
    {
        $this->bill();
        $this->policy();
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt());
        $run = app(FeeAllocations::class)->post($this->admin, $payment->id, $this->allocate(app(FeeAllocations::class)->preview($this->admin, $payment->id)));
        $row = PaymentAllocation::firstOrFail();
        foreach ([fn () => $payment->update(['amount_cents' => 1]), fn () => $payment->delete(), fn () => $run->update(['amount_cents' => 1]), fn () => $run->delete(),
            fn () => $row->update(['amount_cents' => 1]), fn () => $row->delete(),
            fn () => DB::table('payment_allocations')->insert(collect($row->getRawOriginal())->except('id')->all())] as $write) {
            try {
                $write();
                $this->fail('Expected immutable payment history refusal');
            } catch (QueryException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame(400, $payment->fresh()->amount_cents);
    }

    public function test_receipt_requires_real_confirmation_holder_and_date_and_freezes_currency_with_held_credits(): void
    {
        foreach ([['confirmed' => false], ['received_on' => '2026-06-16'], ['holder_user_id' => $this->schoolA->ids['gina_user']],
            ['pinned_learner_id' => $this->schoolB->ids['asha']], ['amount' => '0.00'], ['amount' => '4.001']] as $bad) {
            $this->refuses(fn () => app(FeePayments::class)->record($this->admin, $this->receipt($bad)), 422);
        }
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt(['pinned_learner_id' => $this->schoolA->ids['asha']]));
        $this->refuses(fn () => app(FeeAccounts::class)->save($this->admin, $this->family(['revision' => 1, 'currency' => 'USD']), $this->account->id), 422);
        $this->refuses(fn () => app(FeeAccounts::class)->save($this->admin, $this->family(['revision' => 1, 'learner_ids' => [$this->schoolA->ids['cara']]]), $this->account->id), 422);
        $this->assertSame(400, app(FeePayments::class)->available($payment));
        $this->assertSame('ZAR', $this->account->fresh()->currency);
        $this->assertSame(1, FeePayment::count());
    }

    public function test_wrong_roles_other_school_revoked_finance_and_disabled_module_cannot_write_or_recover(): void
    {
        foreach (['farid', 'tara', 'asha'] as $name) {
            $actor = User::findOrFail($this->schoolA->ids[$name.'_user']);
            $this->refuses(fn () => app(FeePayments::class)->record($actor, $this->receipt()), 403);
            $this->refuses(fn () => app(FeeAllocations::class)->preview($actor, 999), 403);
        }
        $this->refuses(fn () => app(FeePayments::class)->record($this->admin, $this->receipt(['fee_account_id' => 999999])), 404);
        $input = $this->receipt();
        $payment = app(FeePayments::class)->record($this->admin, $input);
        SchoolMembership::where('school_id', 1)->where('user_id', $this->admin->id)->update(['status' => 'suspended']);
        $this->refuses(fn () => app(FeePayments::class)->record($this->admin, $input), 403);
        SchoolMembership::where('school_id', 1)->where('user_id', $this->admin->id)->update(['status' => 'active']);
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->refuses(fn () => app(FeePayments::class)->record($this->admin, $input), 403);
        $this->assertSame(400, app(FeeStatements::class)->finance($this->account)['credit_cents']);
        $this->assertSame($payment->id, FeePayment::firstOrFail()->id);
    }

    public function test_parent_statement_shows_only_own_two_child_shares_and_receipts(): void
    {
        $this->bill();
        $this->policy();
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt(['amount' => '9.00']));
        app(FeeAllocations::class)->post($this->admin, $payment->id, $this->allocate(app(FeeAllocations::class)->preview($this->admin, $payment->id)));
        app(FeePayments::class)->record($this->admin, $this->receipt(['holder_user_id' => $this->schoolA->ids['mona_user'], 'source_reference' => 'OTHER-HOLDER-SECRET', 'amount' => '20.00']));
        $farid = User::findOrFail($this->schoolA->ids['farid_user']);
        $statement = app(ParentFeeStatements::class)->show($farid, $this->account->id, perPage: 1);
        $this->assertSame(1002, $statement['charged_cents']);
        $this->assertSame(900, $statement['allocated_cents']);
        $this->assertSame(102, $statement['outstanding_cents']);
        $this->assertSame(0, $statement['credit_cents']);
        $this->assertSame(2, $statement['charges']['total']);
        $this->assertCount(1, $statement['charges']['items']);
        $this->assertSame(1, $statement['receipts']['total']);
        $json = json_encode($statement, JSON_THROW_ON_ERROR);
        $this->assertSame($farid->id, $statement['holder_user_id']);
        foreach ($statement['charges']['items'] as $row) {
            $this->assertArrayNotHasKey('holder_user_id', $row);
        }
        foreach (['OTHER-HOLDER-SECRET', 'holder_name', 'credit_account_id', 'source_reference', 'share_bp'] as $private) {
            $this->assertStringNotContainsString($private, $json);
        }
        $index = app(ParentFeeStatements::class)->index($farid);
        $this->assertSame(1, $index['total']);
        $this->assertSame($this->account->id, $index['items'][0]['id']);
    }

    public function test_revoked_child_fee_visibility_redacts_its_charge_and_mixed_receipt_immediately(): void
    {
        $this->bill();
        $this->policy();
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt(['amount' => '20.00']));
        app(FeeAllocations::class)->post($this->admin, $payment->id, $this->allocate(app(FeeAllocations::class)->preview($this->admin, $payment->id)));
        $farid = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->assertSame(998, app(ParentFeeStatements::class)->show($farid, $this->account->id)['credit_cents']);
        GuardianLink::where('guardian_user_id', $farid->id)->where('learner_id', $this->schoolA->ids['cara'])->update(['can_see_fees' => false]);
        $statement = app(ParentFeeStatements::class)->show($farid, $this->account->id);
        $this->assertSame(501, $statement['charged_cents']);
        $this->assertSame(0, $statement['outstanding_cents']);
        $this->assertSame(0, $statement['credit_cents']);
        $this->assertSame(0, $statement['receipts']['total']);
        $this->assertSame([$this->schoolA->ids['asha']], array_column($statement['charges']['items'], 'learner_id'));
        GuardianLink::where('guardian_user_id', $farid->id)->update(['can_see_fees' => false]);
        $this->assertSame([], app(ParentFeeStatements::class)->index($farid)['items']);
        $this->refuses(fn () => app(ParentFeeStatements::class)->show($farid, $this->account->id), 404);
        $this->assertSame(998, app(FeeStatements::class)->finance($this->account->fresh())['credit_cents']);
    }

    public function test_other_parent_staff_and_other_school_cannot_read_or_guess_the_statement(): void
    {
        $this->bill();
        $gina = User::findOrFail($this->schoolA->ids['gina_user']);
        $this->refuses(fn () => app(ParentFeeStatements::class)->show($gina, $this->account->id), 404);
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->refuses(fn () => app(ParentFeeStatements::class)->index($teacher), 403);
        app(CurrentSchool::class)->run(2, function (): void {
            $other = User::findOrFail($this->schoolB->ids['farid_user']);
            $this->refuses(fn () => app(ParentFeeStatements::class)->show($other, $this->account->id), 404);
        });
        $farid = User::findOrFail($this->schoolA->ids['farid_user']);
        SchoolMembership::where('school_id', 1)->where('user_id', $farid->id)->where('role', 'Parent')->update(['status' => 'suspended']);
        $this->refuses(fn () => app(ParentFeeStatements::class)->show($farid, $this->account->id), 403);
    }

    public function test_http_parent_statement_contract_owner_and_fresh_visibility_are_enforced(): void
    {
        $this->bill();
        $farid = User::findOrFail($this->schoolA->ids['farid_user']);
        ParentPolicyService::acceptCurrent($farid);
        $this->api($farid)->getJson('/api/v2/parent/fees/accounts')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('data.total', 1)->assertJsonPath('data.holder_user_id', $farid->id);
        $url = '/api/v2/parent/fees/accounts/'.$this->account->id.'/statement';
        $this->api($farid)->getJson($url)->assertOk()->assertJsonPath('data.charged_cents', 1002)->assertJsonPath('data.charges.total', 2);
        $this->api($farid)->getJson($url.'?per_page=51')->assertUnprocessable();
        $gina = User::findOrFail($this->schoolA->ids['gina_user']);
        ParentPolicyService::acceptCurrent($gina);
        $this->api($gina)->getJson($url)->assertNotFound();
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->api($farid)->getJson($url)->assertOk()->assertJsonPath('data.outstanding_cents', 1002);
        $this->api($this->admin)->getJson($url)->assertForbidden();
        $this->api($farid)->withHeader('X-Account-Id', (string) $gina->id)->getJson($url)->assertForbidden();
        GuardianLink::where('guardian_user_id', $farid->id)->update(['can_see_fees' => false]);
        $this->api($farid)->getJson($url)->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_semantic_credit_drift_blocks_allocation_and_projection_rebuild(): void
    {
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt());
        app(LedgerService::class)->post('synthetic-unmapped-credit', 'synthetic-unmapped-credit', 'Synthetic unlinked receipt correction', [
            Posting::debit($payment->credit_account_id, Money::ofCents(100, 'ZAR')), Posting::credit($payment->debit_account_id, Money::ofCents(100, 'ZAR')),
        ]);
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        $report = app(FeePaymentReconciliation::class)->inspect();
        $this->assertCount(1, $report['family_credit_differences']);
        $this->refuses(fn () => app(FeeStatements::class)->finance($this->account), 409);
        $this->refuses(fn () => app(ParentFeeStatements::class)->show(User::findOrFail($this->schoolA->ids['farid_user']), $this->account->id), 409);
        $this->refuses(fn () => app(FeeAllocations::class)->preview($this->admin, $payment->id), 409);
        $this->artisan('ledger:reconcile --school=1 --rebuild --json')->assertExitCode(1);
    }

    public function test_incomplete_allocation_is_reported_and_cannot_hide_behind_balanced_postings(): void
    {
        $this->bill();
        $this->policy();
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt());
        $snapshot = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        $entry = app(LedgerService::class)->post('synthetic-incomplete-allocation', 'synthetic-incomplete-allocation', 'Synthetic saved incomplete allocation', [
            Posting::debit($payment->credit_account_id, Money::ofCents(400, 'ZAR')), Posting::credit((int) $this->account->fresh()->ledger_account_id, Money::ofCents(400, 'ZAR')),
        ]);
        PaymentAllocationRun::create(['payment_id' => $payment->id, 'ledger_entry_id' => $entry, 'allocation_order' => 'oldest_first', 'policy_revision' => 1,
            'fingerprint' => $snapshot['fingerprint'], 'snapshot' => $snapshot, 'amount_cents' => 400, 'created_by' => $this->admin->id, 'created_at' => now('UTC')]);
        $report = app(FeePaymentReconciliation::class)->inspect();
        $this->assertSame(1, $report['incomplete_allocations']);
        $this->assertCount(1, $report['fee_receivable_differences']);
        $this->assertCount(1, $report['family_credit_differences']);
        $this->refuses(fn () => app(FeeAllocations::class)->preview($this->admin, $payment->id), 409);
        $this->artisan('ledger:reconcile --school=1 --rebuild --json')->assertExitCode(1);
    }

    private function api(User $actor): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($actor->createToken('synthetic-fee-statement-test')->plainTextToken)
            ->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $actor->id]);
    }

    public function test_actual_foreign_account_and_payment_ids_never_cross_school_boundaries(): void
    {
        $foreign = app(CurrentSchool::class)->run(2, fn () => FeeAccount::create(['reference' => 'NEF-FOREIGN', 'name' => 'Private other school', 'currency' => 'ZAR', 'active' => true, 'revision' => 1]));
        $this->refuses(fn () => app(FeePayments::class)->record($this->admin, $this->receipt(['fee_account_id' => $foreign->id])), 404);
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt());
        SchoolMembership::create(['school_id' => 2, 'user_id' => $this->admin->id, 'role' => 'Super Admin', 'status' => 'active', 'starts_on' => '2026-01-01']);
        app(CurrentSchool::class)->run(2, function () use ($payment): void {
            $other = $this->admin->fresh();
            $other->assignRole('Super Admin');
            $this->refuses(fn () => app(FeeAllocations::class)->preview($other, $payment->id), 404);
            $this->refuses(fn () => app(FeeStatements::class)->finance($this->account), 404);
        });
        $this->assertSame(1, FeePayment::count());
    }

    public function test_panel_records_reviews_and_allocates_once_with_private_bound_forms(): void
    {
        $this->bill();
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $this->actingAs($this->admin)->get('/fee-payments')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/fee-payments/new?account_id='.$this->account->id)->assertOk()->assertSee('Holder whose share this pays');
        $input = $this->receipt(['method' => 'eft', 'amount' => '20.00']);
        $this->post('/fee-payments/new', [...$input, 'confirmed' => false] + $bind)->assertStatus(422)->assertSessionMissing('_old_input')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(0, FeePayment::count());
        $this->post('/fee-payments/new', $input + $bind)->assertStatus(303);
        $this->post('/fee-payments/new', $input + $bind)->assertStatus(303);
        $payment = FeePayment::sole();
        $this->get('/fee-payments/'.$payment->id)->assertOk()->assertSee('ZAR 20.00')->assertSee('Review credit allocation');
        $this->get('/fee-payments/'.$payment->id.'/allocation')->assertOk()->assertSee('Choose the school allocation rule before allocating this credit.');
        $this->post('/fee-payments/policy', ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'allocation_order' => 'oldest_first'] + $bind)->assertStatus(303);
        $this->get('/fee-payments/'.$payment->id.'/allocation')->assertOk()->assertSee('ZAR 10.02')->assertSee('ZAR 9.98');
        $preview = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        $allocation = $this->allocate($preview) + $bind;
        $this->post('/fee-payments/'.$payment->id.'/allocation', $allocation)->assertStatus(303);
        $this->post('/fee-payments/'.$payment->id.'/allocation', $allocation)->assertStatus(303);
        $this->assertSame(1, PaymentAllocationRun::count());
        $this->assertSame(2, PaymentAllocation::count());
        $this->get('/fee-payments/accounts/'.$this->account->id.'/statement')->assertOk()->assertSee('ZAR 10.00')->assertSee('ZAR 9.98')->assertHeader('Cache-Control', 'no-store, private');
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->get('/fee-payments/'.$payment->id)->assertOk()->assertSee('Payment history is read-only while fees are disabled.')->assertDontSee('Review credit allocation');
        $this->post('/fee-payments/new', $this->receipt() + $bind)->assertForbidden();
    }

    public function test_panel_rejects_wrong_role_stale_account_school_and_query_writes(): void
    {
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $input = $this->receipt();
        $this->actingAs($this->admin)->post('/fee-payments/new', $input + [...$bind, 'expected_school_id' => 2])->assertForbidden();
        $this->post('/fee-payments/new', $input + [...$bind, 'expected_account_id' => $this->admin->id + 999])->assertForbidden();
        $this->post('/fee-payments/new?unsafe=1', $input + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $this->post('/fee-payments/new', [...$input, 'fee_account_id' => 99999999] + $bind)->assertNotFound();
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->actingAs($teacher)->get('/fee-payments')->assertForbidden();
        $this->get('/fee-payments/new')->assertForbidden();
        $this->post('/fee-payments/new', $input + ['expected_account_id' => $teacher->id, 'expected_school_id' => 1])->assertForbidden();
        $this->assertSame(0, FeePayment::count());
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
