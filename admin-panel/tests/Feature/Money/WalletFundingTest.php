<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\WalletFunding;
use App\Models\Money\WalletSetting;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\FeeAccounts;
use App\Services\Money\FeePayments;
use App\Services\Money\WalletFundingReconciliation;
use App\Services\Money\WalletFundingService;
use App\Services\Money\WalletSettings;
use App\Services\Platform\Ledger\AccountType;
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
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class WalletFundingTest extends TestCase
{
    use UsesTwoSchools;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDateTime(2026, 6, 15, 12, 0, 0));
        $this->setUpTwoSchools();
        $this->admin = $this->superAdmin();
        app(ModuleGate::class)->change('wallet', true, $this->admin);
        app(ModuleGate::class)->change('fees', false, $this->admin);
    }

    private function configure(): void
    {
        app(WalletSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'currency' => 'ZAR', 'daily_spend_cents' => 5000, 'child_limits_required' => true]);
        app(WalletFundingService::class)->policy($this->admin, $this->policy());
    }

    private function policy(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true], $changes);
    }

    private function funding(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'method' => 'cash', 'source_reference' => 'SCHOOL-'.Str::uuid(), 'amount' => '100.01', 'received_on' => '2026-06-15', 'school_owned' => true, 'evidence' => 'Synthetic verified school-owned cash evidence'], $changes);
    }

    private function refuses(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Expected refusal');
        } catch (ValidationException) {
            $this->assertSame(422, $status);
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($status, $e->getStatusCode(), $e->getMessage());
        }
    }

    public function test_verified_school_cash_and_eft_fund_only_master_with_balanced_cents_and_permanent_receipts(): void
    {
        $this->configure();
        $input = $this->funding(['source_reference' => '  school cash-1  ']);
        $cash = app(WalletFundingService::class)->record($this->admin, $input);
        $this->assertSame('SCHOOL CASH-1', $cash->source_reference);
        $this->assertSame(10001, $cash->amount_cents);
        $this->assertSame($cash->id, app(WalletFundingService::class)->record($this->admin, $input)->id);
        $bank = app(WalletFundingService::class)->record($this->admin, $this->funding(['method' => 'eft', 'amount' => '20.02']));
        $this->assertSame(12003, app(WalletFundingService::class)->balance($this->admin)['balance_cents']);
        $this->assertSame(2, WalletFunding::count());
        $this->assertSame(2, DB::table('financial_receipts')->count());
        $this->assertSame(4, DB::table('ledger_postings')->count());
        $this->assertSame(0, DB::table('wallet_accounts')->count());
        $this->assertSame('wallet_bank', DB::table('ledger_accounts')->where('id', $bank->asset_account_id)->value('type'));
        $this->assertSame('school', $bank->collected_by);
        $this->assertSame(0, $bank->fee_cents);
        $this->assertSame(2002, $bank->net_cents);
        $this->assertSame($bank->source_reference, $bank->settlement_reference);
        $this->assertNotSame($cash->evidence, DB::table('wallet_fundings')->where('id', $cash->id)->value('evidence'));
        $this->assertArrayNotHasKey('evidence', $cash->toArray());
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        $this->assertTrue(WalletFundingReconciliation::healthy(app(WalletFundingReconciliation::class)->inspect()));
        $this->assertSame(0, (int) DB::table('ledger_accounts')->whereIn('type', ['clearing', 'due_to_school'])->sum('balance_cents'));
    }

    public function test_missing_explicit_policy_disallowed_method_future_date_and_parent_money_are_refused_without_partial_money(): void
    {
        $this->refuses(fn () => app(WalletFundingService::class)->record($this->admin, $this->funding()), 422);
        $this->configure();
        $this->refuses(fn () => app(WalletFundingService::class)->policy($this->admin, $this->policy(['revision' => 1, 'cash_allowed' => false, 'eft_allowed' => false])), 422);
        app(WalletFundingService::class)->policy($this->admin, $this->policy(['revision' => 1, 'cash_allowed' => false]));
        foreach ([$this->funding(), $this->funding(['method' => 'eft', 'received_on' => '2026-06-16']), $this->funding(['method' => 'eft', 'school_owned' => false]), $this->funding(['method' => 'eft', 'amount' => '1.001']), $this->funding(['method' => 'eft', 'amount' => '0.00'])] as $input) {
            $this->refuses(fn () => app(WalletFundingService::class)->record($this->admin, $input), 422);
        }
        $this->assertSame(0, WalletFunding::count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    public function test_duplicate_external_sources_and_changed_operations_cannot_create_money_twice(): void
    {
        $this->configure();
        $input = $this->funding();
        app(WalletFundingService::class)->record($this->admin, $input);
        $this->refuses(fn () => app(WalletFundingService::class)->record($this->admin, [...$input, 'operation_id' => (string) Str::uuid()]), 409);
        $this->refuses(fn () => app(WalletFundingService::class)->record($this->admin, [...$input, 'amount' => '100.02']), 409);
        $this->assertSame(1, DB::table('ledger_entries')->count());
        $this->assertSame(10001, app(WalletFundingService::class)->balance($this->admin)['balance_cents']);
    }

    public function test_fee_and_wallet_receipts_cannot_book_the_same_external_money_in_either_direction(): void
    {
        $this->configure();
        app(ModuleGate::class)->change('fees', true, $this->admin);
        SessionYear::whereKey($this->schoolA->ids['session_year'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->schoolA->ids['session_year'], 'starts_on' => '2026-01-01', 'legacy_dates' => false, 'reason' => 'Synthetic cross-domain receipt check']);
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->schoolA->ids['farid_user']], ['status' => 'active', 'can_see_fees' => true, 'can_approve' => true, 'relationship' => 'guardian']);
        $account = app(FeeAccounts::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic receipt family', 'currency' => 'ZAR', 'session_year_id' => $this->schoolA->ids['session_year'], 'learner_ids' => [$this->schoolA->ids['asha']], 'holders' => [['user_id' => $this->schoolA->ids['farid_user'], 'share_percent' => '100']]]);
        $fee = ['operation_id' => (string) Str::uuid(), 'fee_account_id' => $account->id, 'holder_user_id' => $this->schoolA->ids['farid_user'], 'pinned_learner_id' => null, 'method' => 'cash', 'source_reference' => 'SHARED-1', 'amount' => '10.00', 'received_on' => '2026-06-15', 'confirmed' => true];
        app(FeePayments::class)->record($this->admin, $fee);
        $this->refuses(fn () => app(WalletFundingService::class)->record($this->admin, $this->funding(['source_reference' => ' shared-1 '])), 409);
        app(WalletFundingService::class)->record($this->admin, $this->funding(['source_reference' => 'SHARED-2']));
        $this->refuses(fn () => app(FeePayments::class)->record($this->admin, [...$fee, 'operation_id' => (string) Str::uuid(), 'source_reference' => 'SHARED-2']), 409);
        $this->assertSame(2, DB::table('financial_receipts')->count());
        $this->assertSame(2, DB::table('ledger_entries')->count());
        $this->assertTrue(WalletFundingReconciliation::healthy(app(WalletFundingReconciliation::class)->inspect()));
    }

    public function test_funded_currency_and_all_receipt_identities_are_immutable_in_sql(): void
    {
        $this->configure();
        $funding = app(WalletFundingService::class)->record($this->admin, $this->funding());
        foreach ([fn () => DB::table('wallet_fundings')->update(['amount_cents' => 1]), fn () => DB::table('wallet_fundings')->delete(), fn () => DB::table('financial_receipts')->update(['source_reference' => 'CHANGED']), fn () => DB::table('financial_receipts')->delete(), fn () => DB::table('wallet_settings')->update(['currency' => 'USD', 'revision' => 2])] as $write) {
            try {
                $write();
                $this->fail('Expected immutable identity');
            } catch (QueryException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->refuses(fn () => app(WalletSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'currency' => 'USD', 'daily_spend_cents' => 5000, 'child_limits_required' => true]), 409);
        $this->assertSame('ZAR', WalletSetting::sole()->currency);
    }

    public function test_current_permission_and_disabled_wallet_apply_before_replay_but_finance_history_remains_readable(): void
    {
        $this->configure();
        $input = $this->funding();
        $funding = app(WalletFundingService::class)->record($this->admin, $input);
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        foreach ([$teacher, $parent] as $actor) {
            $this->refuses(fn () => app(WalletFundingService::class)->record($actor, $this->funding()), 403);
            $this->refuses(fn () => app(WalletFundingService::class)->balance($actor), 403);
        }
        app(ModuleGate::class)->change('wallet', false, $this->admin);
        $this->assertSame(10001, app(WalletFundingService::class)->balance($this->admin)['balance_cents']);
        $this->refuses(fn () => app(WalletFundingService::class)->record($this->admin, $input), 403);
        $this->actingAs($this->admin)->get('/wallet-funding')->assertOk()->assertSee('SCHOOL-');
        $this->get('/wallet-funding/'.$funding->id.'/receipt')->assertOk()->assertSee('School wallet funding receipt');
    }

    public function test_finance_panel_binds_actor_school_and_private_evidence_and_refuses_wrong_roles(): void
    {
        $this->configure();
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $input = $this->funding(['source_reference' => 'panel-school-1']);
        $this->actingAs($this->admin)->post('/wallet-funding/receipts', $input + [...$bind, 'expected_school_id' => 2])->assertForbidden();
        $this->post('/wallet-funding/receipts', $input + $bind)->assertStatus(303);
        $funding = WalletFunding::sole();
        $this->assertSame('PANEL-SCHOOL-1', $funding->source_reference);
        $this->get('/wallet-funding/'.$funding->id.'/receipt')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertSee($input['evidence']);
        $this->post('/wallet-funding/receipts', [...$this->funding(), 'amount' => 'invalid'] + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        foreach (['tara_user', 'farid_user', 'asha_user'] as $key) {
            $actor = User::findOrFail($this->schoolA->ids[$key]);
            foreach ([['GET', '/wallet-funding', []], ['GET', '/wallet-funding/'.$funding->id.'/receipt', []], ['POST', '/wallet-funding/receipts', $this->funding() + $bind], ['POST', '/wallet-funding/policy', $this->policy() + $bind]] as [$method,$url,$body]) {
                $response = $this->actingAs($actor)->call($method, $url, $body);
                if ($key === 'tara_user') {
                    $response->assertForbidden();
                } else {
                    $response->assertRedirect(route('login'));
                }
            }
        }
        $this->assertSame(1, WalletFunding::count());
    }

    public function test_permission_revocation_refuses_an_old_permanent_receipt_replay(): void
    {
        $this->configure();
        $input = $this->funding();
        app(WalletFundingService::class)->record($this->admin, $input);
        $permission = DB::table('permissions')->where('name', 'wallet-finance-manage')->value('id');
        DB::table('role_has_permissions')->where('permission_id', $permission)->delete();
        $this->refuses(fn () => app(WalletFundingService::class)->record($this->admin, $input), 403);
        $this->assertSame(1, WalletFunding::count());
    }

    public function test_sql_funding_refuses_a_receipt_with_additional_unrelated_balanced_postings(): void
    {
        $this->configure();
        $fund = app(WalletFundingService::class)->record($this->admin, $this->funding());
        app(ModuleGate::class)->change('fees', true, $this->admin);
        $ledger = app(LedgerService::class);
        $cash = $ledger->openAccount(AccountType::CashDesk, 'fees:extra:cash', 'ZAR');
        $family = $ledger->openAccount(AccountType::Family, 'fees:extra:family', 'ZAR');
        try {
            DB::transaction(function () use ($ledger, $cash, $family, $fund): void {
                $money = Money::ofCents(10001, 'ZAR');
                $entry = $ledger->post('extra-postings', 'extra-postings', 'Synthetic extra balanced postings', [Posting::debit($fund->asset_account_id, $money), Posting::credit($fund->master_account_id, $money), Posting::debit($cash, $money), Posting::credit($family, $money)]);
                $data = $fund->getRawOriginal();
                unset($data['id']);
                $data['ledger_entry_id'] = $entry;
                $data['source_reference'] = 'EXTRA-POSTINGS';
                DB::table('wallet_fundings')->insert($data);
            });
            $this->fail('Expected exact two-sided funding');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
        $this->assertSame(1, WalletFunding::count());
        $this->assertSame(1, DB::table('ledger_entries')->count());
        $this->assertSame(10001, $ledger->balance($fund->master_account_id)->cents);
    }

    public function test_projection_drift_blocks_a_new_funding_without_partial_receipt_or_posting(): void
    {
        $this->configure();
        $fund = app(WalletFundingService::class)->record($this->admin, $this->funding());
        DB::table('ledger_accounts')->where('id', $fund->master_account_id)->update(['balance_cents' => 10002]);
        $this->refuses(fn () => app(WalletFundingService::class)->record($this->admin, $this->funding()), 409);
        $this->assertSame(1, WalletFunding::count());
        $this->assertSame(1, DB::table('ledger_entries')->count());
        $this->assertSame(1, DB::table('financial_receipts')->count());
    }

    public function test_funding_references_support_the_same_canonical_characters_as_fee_receipts(): void
    {
        $this->configure();
        $receipt = app(WalletFundingService::class)->record($this->admin, $this->funding(['source_reference' => 'school/cash/4:01._-']));
        $this->assertSame('SCHOOL/CASH/4:01._-', $receipt->source_reference);
    }

    public function test_nightly_funding_attribution_failure_blocks_projection_repair(): void
    {
        $this->configure();
        $funding = app(WalletFundingService::class)->record($this->admin, $this->funding());
        $money = Money::ofCents(1, 'ZAR');
        app(LedgerService::class)->post('wallet-funding:orphan', 'wallet-funding:orphan', 'Synthetic missing domain receipt', [Posting::debit($funding->asset_account_id, $money), Posting::credit($funding->master_account_id, $money)]);
        DB::table('ledger_accounts')->where('id', $funding->master_account_id)->update(['balance_cents' => 10003]);
        $report = app(WalletFundingReconciliation::class)->inspect();
        $this->assertSame(1, $report['unattributed_wallet_funding_entries']);
        $this->assertFalse(WalletFundingReconciliation::healthy($report));
        $this->artisan('ledger:reconcile', ['--school' => '1', '--json' => true, '--rebuild' => true])->expectsOutputToContain('"domain_history_valid": false')->assertFailed();
        $this->assertSame(10003, (int) DB::table('ledger_accounts')->where('id', $funding->master_account_id)->value('balance_cents'));
        app(CurrentSchool::class)->run(2, function (): void {
            $this->assertTrue(WalletFundingReconciliation::healthy(app(WalletFundingReconciliation::class)->inspect()));
        });
    }

    public function test_other_school_cannot_read_school_receipt_or_use_its_currency_policy(): void
    {
        $this->configure();
        $funding = app(WalletFundingService::class)->record($this->admin, $this->funding());
        SchoolMembership::create(['school_id' => 2, 'user_id' => $this->admin->id, 'role' => 'Super Admin', 'status' => 'active']);
        app(CurrentSchool::class)->run(2, function () use ($funding): void {
            $actor = $this->admin->fresh();
            $actor->assignRole('Super Admin');
            app(ModuleGate::class)->change('wallet', true, $actor);
            $this->refuses(fn () => WalletFunding::findOrFail($funding->id), 404);
            $this->assertSame(['currency' => null, 'balance_cents' => 0], app(WalletFundingService::class)->balance($actor));
        });
        $this->actingAs($this->admin)->withSession([config('tenancy.session_key') => 2])->get('/wallet-funding/'.$funding->id.'/receipt')->assertNotFound();
    }
}
