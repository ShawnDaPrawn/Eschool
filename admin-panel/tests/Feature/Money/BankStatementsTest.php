<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\BankStatementImport;
use App\Models\Money\BankStatementLine;
use App\Models\Money\BankStatementResolution;
use App\Models\Money\BillingOperation;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeePayment;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\BankCsv;
use App\Services\Money\BankStatements;
use App\Services\Money\FeeAccounts;
use App\Services\Money\FeePaymentReconciliation;
use App\Services\Money\FeePayments;
use App\Services\Money\FeeStatements;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class BankStatementsTest extends TestCase
{
    use UsesTwoSchools;

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
        $this->account = app(FeeAccounts::class)->save($this->admin, $this->family());
    }

    public function test_preview_never_posts_and_duplicate_files_rows_order_and_overlaps_are_idempotent(): void
    {
        $bank = app(BankStatements::class);
        $review = $bank->previewImport($this->admin, $this->csv());
        $this->assertSame(0, BankStatementLine::count());
        $this->assertSame(0, FeePayment::count());
        $input = $this->importInput($review);
        $import = $bank->import($this->admin, $input);
        $this->assertNotNull($import->sealed_at);
        $this->assertSame($import->id, $bank->import($this->admin, $input)->id);
        $this->assertSame($import->id, $bank->import($this->admin, $this->importInput($review))->id);
        $rows = [['2026-06-15', 'BANK-002', '', 'Bank fee', '', '0.01', 'ZAR'], ['2026-06-15', 'BANK-001', $this->account->reference, 'Synthetic family payment', '4.00', '', 'ZAR']];
        $next = $bank->previewImport($this->admin, $this->csv($rows));
        $this->assertSame(1, $next['existing_count']);
        $second = $bank->import($this->admin, $this->importInput($next));
        $reordered = $bank->previewImport($this->admin, $this->csv(array_reverse($rows)));
        $this->assertSame($second->id, $bank->import($this->admin, $this->importInput($reordered))->id);
        $this->assertSame(2, BankStatementLine::count());
        $this->assertSame(2, BankStatementImport::count());
        $this->assertSame(3, DB::table('bank_statement_import_rows')->count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $this->assertSame(0, FeePayment::count());
        $raw = DB::table('bank_statement_lines')->first();
        $this->assertStringNotContainsString('Synthetic family', $raw->details);
        $this->assertStringNotContainsString('BANK-001', $raw->details);
        $this->assertStringNotContainsString('details', BankStatementLine::first()->toJson());
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    public function test_conflicting_business_reference_rejects_the_entire_import_and_key_rotation_cannot_duplicate_rows(): void
    {
        $this->imported();
        $conflict = $this->csv([['2026-06-15', 'BANK-000', '', 'New row before conflict', '1.00', '', 'ZAR'], ['2026-06-15', 'BANK-001', $this->account->reference, 'Changed details', '4.00', '', 'ZAR']]);
        $this->refuses(fn () => app(BankStatements::class)->previewImport($this->admin, $conflict), 409);
        $this->assertSame(1, BankStatementLine::count());
        config(['app.key' => 'base64:'.base64_encode(str_repeat('R', 32))]);
        $review = app(BankStatements::class)->previewImport($this->admin, $this->csv());
        $this->assertSame(1, $review['existing_count']);
        $this->assertSame(BankStatementImport::sole()->id, app(BankStatements::class)->import($this->admin, $this->importInput($review))->id);
        $this->assertSame(1, BankStatementLine::count());
    }

    public function test_reviewed_credit_records_exactly_one_balanced_eft_receipt_and_keeps_allocation_separate(): void
    {
        $line = $this->imported();
        $bank = app(BankStatements::class);
        $this->assertSame($this->account->id, $bank->suggestion($line)->id);
        $review = $bank->previewMatch($this->admin, $line->id, $this->selection(['pinned_learner_id' => $this->schoolA->ids['asha']]));
        $this->assertNull($review['existing_payment_id']);
        $this->assertSame(0, FeePayment::count());
        $input = $this->resolveInput($review);
        $resolution = $bank->resolve($this->admin, $line->id, $input);
        $this->assertSame($resolution->id, $bank->resolve($this->admin, $line->id, $input)->id);
        $this->refuses(fn () => $bank->resolve($this->admin, $line->id, [...$input, 'operation_id' => (string) Str::uuid()]), 409);
        $payment = FeePayment::sole();
        $this->assertSame($payment->id, $resolution->payment_id);
        $this->assertSame('eft', $payment->method);
        $this->assertSame('manual', $payment->provider);
        $this->assertSame(400, $payment->amount_cents);
        $this->assertSame($this->schoolA->ids['asha'], $payment->pinned_learner_id);
        $this->assertSame('BANK-001', $payment->source_reference);
        $this->assertSame(400, app(LedgerService::class)->balance($payment->debit_account_id)->cents);
        $this->assertSame(400, app(LedgerService::class)->balance($payment->credit_account_id)->cents);
        $this->assertSame(0, DB::table('payment_allocations')->count());
        $this->assertSame(0, DB::table('ledger_accounts')->whereIn('type', ['fee_income', 'clearing', 'due_to_school'])->count());
        $this->assertSame(1, DB::table('domain_events')->where('type', 'fees.payment_recorded')->count());
        $this->assertSame(1, DB::table('ledger_entries')->count());
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
        $this->assertSame(400, app(FeeStatements::class)->finance($this->account)['credit_cents']);
    }

    public function test_existing_manual_eft_is_linked_without_creating_another_receipt_or_posting(): void
    {
        $payment = $this->existing();
        $line = $this->imported();
        $review = app(BankStatements::class)->previewMatch($this->admin, $line->id, $this->selection());
        $this->assertSame($payment->id, $review['existing_payment_id']);
        $resolution = app(BankStatements::class)->resolve($this->admin, $line->id, $this->resolveInput($review));
        $this->assertSame($payment->id, $resolution->payment_id);
        $this->assertSame(1, FeePayment::count());
        $this->assertSame(1, DB::table('ledger_entries')->count());
        $this->assertSame(1, DB::table('domain_events')->where('type', 'fees.payment_recorded')->count());
    }

    public function test_a_receipt_recorded_after_preview_invalidates_the_new_receipt_review(): void
    {
        $line = $this->imported();
        $review = app(BankStatements::class)->previewMatch($this->admin, $line->id, $this->selection());
        $this->existing();
        $this->refuses(fn () => app(BankStatements::class)->resolve($this->admin, $line->id, $this->resolveInput($review)), 409);
        $this->assertSame(0, BankStatementResolution::count());
        $next = app(BankStatements::class)->previewMatch($this->admin, $line->id, $this->selection());
        app(BankStatements::class)->resolve($this->admin, $line->id, $this->resolveInput($next));
        $this->assertSame(1, FeePayment::count());
    }

    public function test_existing_eft_amount_date_holder_currency_and_pin_must_agree(): void
    {
        $line = $this->imported();
        $this->existing(['holder_user_id' => $this->schoolA->ids['mona_user']]);
        $this->refuses(fn () => app(BankStatements::class)->previewMatch($this->admin, $line->id, $this->selection()), 409);
        $next = app(BankStatements::class)->previewMatch($this->admin, $line->id, $this->selection(['holder_user_id' => $this->schoolA->ids['mona_user']]));
        $this->assertNotNull($next['existing_payment_id']);
        $this->assertSame(0, BankStatementResolution::count());
        foreach ([['amount' => '4.01', 'source_reference' => 'DIFFERENT-AMOUNT'], ['received_on' => '2026-06-14', 'source_reference' => 'DIFFERENT-DATE'], ['pinned_learner_id' => $this->schoolA->ids['asha'], 'source_reference' => 'DIFFERENT-PIN']] as $change) {
            $this->existing($change);
            $other = $this->imported($this->csv([['2026-06-15', $change['source_reference'], '', 'Mismatch evidence', '4.00', '', 'ZAR']]));
            $this->refuses(fn () => app(BankStatements::class)->previewMatch($this->admin, $other->id, $this->selection()), 409);
        }
    }

    public function test_missing_reference_stays_unmatched_and_non_fee_rows_need_reviewed_reasoned_exclusion(): void
    {
        $line = $this->imported($this->csv([['2026-06-15', 'BANK-001', '', 'Other transaction', '', '0.01', 'ZAR']]));
        $this->assertNull(app(BankStatements::class)->suggestion($line));
        $this->refuses(fn () => app(BankStatements::class)->previewMatch($this->admin, $line->id, $this->selection()), 422);
        $review = app(BankStatements::class)->previewMatch($this->admin, $line->id, $this->selection(['kind' => 'excluded', 'reason' => 'Verified bank charge, unrelated to school fee receipts.']));
        $result = app(BankStatements::class)->resolve($this->admin, $line->id, $this->resolveInput($review));
        $this->assertSame('excluded', $result->kind);
        $this->assertNull($result->payment_id);
        $this->assertSame(0, FeePayment::count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $this->assertStringNotContainsString('Verified bank charge', DB::table('bank_statement_resolutions')->value('reason'));
    }

    public function test_forged_previews_changed_payer_and_stale_account_configuration_never_post(): void
    {
        $line = $this->imported();
        $review = app(BankStatements::class)->previewMatch($this->admin, $line->id, $this->selection());
        $this->refuses(fn () => app(BankStatements::class)->resolve($this->admin, $line->id, [...$this->resolveInput($review), 'holder_user_id' => $this->schoolA->ids['mona_user']]), 409);
        $this->account->update(['revision' => 2]);
        $this->refuses(fn () => app(BankStatements::class)->resolve($this->admin, $line->id, $this->resolveInput($review)), 409);
        $this->assertSame(0, FeePayment::count());
        $importReview = app(BankStatements::class)->previewImport($this->admin, $this->csv());
        $this->refuses(fn () => app(BankStatements::class)->import($this->admin, [...$this->importInput($importReview), 'payload' => base64_encode('[]')]), 422);
        $this->refuses(fn () => app(BankStatements::class)->import($this->admin, [...$this->importInput($importReview), 'fingerprint' => str_repeat('f', 64)]), 409);
        $this->assertSame(1, BankStatementLine::count());
    }

    public function test_currency_ownership_and_learner_pins_are_checked_before_recording_a_receipt(): void
    {
        $line = $this->imported();
        $bank = app(BankStatements::class);
        $this->refuses(fn () => $bank->previewMatch($this->admin, $line->id, $this->selection(['holder_user_id' => $this->schoolA->ids['gina_user']])), 422);
        $this->refuses(fn () => $bank->previewMatch($this->admin, $line->id, $this->selection(['pinned_learner_id' => $this->schoolA->ids['ben']])), 422);
        $other = $this->imported($this->csv([['2026-06-15', 'FOREIGN-CURRENCY', '', '', '4.00', '', 'USD']]));
        $this->refuses(fn () => $bank->previewMatch($this->admin, $other->id, $this->selection()), 422);
        $this->assertSame(0, FeePayment::count());
    }

    public function test_injected_late_failure_rolls_back_receipt_postings_audit_events_and_both_replay_records(): void
    {
        $line = $this->imported();
        $review = app(BankStatements::class)->previewMatch($this->admin, $line->id, $this->selection());
        $before = [DB::table('ledger_entries')->count(), DB::table('domain_events')->count(), DB::table('audit_log')->count(), BillingOperation::count()];
        $original = BankStatementResolution::getEventDispatcher();
        BankStatementResolution::setEventDispatcher(clone $original);
        BankStatementResolution::creating(fn () => throw new RuntimeException('Synthetic late bank resolution failure'));
        try {
            app(BankStatements::class)->resolve($this->admin, $line->id, $this->resolveInput($review));
            $this->fail('Expected injected failure');
        } catch (RuntimeException $e) {
            $this->assertSame('Synthetic late bank resolution failure', $e->getMessage());
        } finally {
            BankStatementResolution::setEventDispatcher($original);
        }
        $this->assertSame($before, [DB::table('ledger_entries')->count(), DB::table('domain_events')->count(), DB::table('audit_log')->count(), BillingOperation::count()]);
        $this->assertSame(0, FeePayment::count());
        $this->assertSame(0, BankStatementResolution::count());
        app(BankStatements::class)->resolve($this->admin, $line->id, $this->resolveInput($review));
        $this->assertSame(1, FeePayment::count());
    }

    public function test_database_guards_reject_changed_bank_history_wrong_receipt_and_populated_rollback(): void
    {
        $line = $this->imported();
        $this->existing(['amount' => '5.00']);
        foreach ([fn () => DB::table('bank_statement_lines')->where('id', $line->id)->update(['amount_cents' => 500]),
            fn () => DB::table('bank_statement_lines')->where('id', $line->id)->delete(),
            fn () => DB::table('bank_statement_imports')->update(['row_count' => 2]),
            fn () => DB::table('bank_statement_import_rows')->delete(),
            fn () => BankStatementResolution::create(['bank_statement_line_id' => $line->id, 'kind' => 'matched', 'payment_id' => FeePayment::sole()->id, 'reason' => 'Synthetic wrong match', 'fingerprint' => str_repeat('a', 64), 'created_by' => $this->admin->id, 'created_at' => now('UTC')]),
        ] as $write) {
            try {
                $write();
                $this->fail('Expected immutable history refusal');
            } catch (QueryException $e) {
                $this->assertStringContainsString('45000', $e->getMessage());
            }
        }
        $migration = require database_path('migrations/2026_10_27_500000_create_bank_statement_imports.php');
        try {
            $migration->down();
            $this->fail('Expected populated rollback refusal');
        } catch (RuntimeException $e) {
            $this->assertSame('Retain bank import and reconciliation history.', $e->getMessage());
        }
    }

    public function test_real_cross_school_line_and_account_ids_and_same_bank_reference_remain_isolated(): void
    {
        $line = $this->imported();
        $foreign = app(CurrentSchool::class)->run(2, fn () => FeeAccount::create(['reference' => 'NEF-FOREIGN', 'name' => 'Baobab private family', 'currency' => 'ZAR', 'active' => true, 'revision' => 1]));
        $this->refuses(fn () => app(BankStatements::class)->previewMatch($this->admin, $line->id, $this->selection(['fee_account_id' => $foreign->id])), 404);
        SchoolMembership::create(['school_id' => 2, 'user_id' => $this->admin->id, 'role' => 'Super Admin', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $foreignLine = app(CurrentSchool::class)->run(2, function () use ($line): BankStatementLine {
            $other = $this->admin->fresh();
            $other->assignRole('Super Admin');
            $this->refuses(fn () => app(BankStatements::class)->previewMatch($other, $line->id, $this->selection()), 404);
            $review = app(BankStatements::class)->previewImport($other, $this->csv());
            app(BankStatements::class)->import($other, $this->importInput($review));

            return BankStatementLine::sole();
        });
        $this->assertNotSame($line->id, $foreignLine->id);
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $this->actingAs($this->admin)->get('/bank-statements/'.$foreignLine->id)->assertNotFound()->assertDontSee('Baobab');
        $this->post('/bank-statements/'.$foreignLine->id.'/review', $this->selection() + $bind)->assertNotFound();
        $this->get('/bank-statements?account_id='.$foreign->id)->assertOk()->assertDontSee('Baobab private family');
    }

    public function test_panel_upload_review_confirmation_and_queue_are_private_actor_bound_and_immutable(): void
    {
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $this->actingAs($this->admin)->get('/bank-statements')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/bank-statements/new')->assertOk()->assertSee('Download bank CSV headings');
        $this->get('/bank-statements/template')->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8')->assertSee(implode(',', BankCsv::HEADER));
        $this->post('/bank-statements/review-import', ['statement' => UploadedFile::fake()->createWithContent('bank.csv', $this->csv())] + $bind)->assertOk()->assertSee('ZAR 4.00')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(0, BankStatementLine::count());
        $review = app(BankStatements::class)->previewImport($this->admin, $this->csv());
        $input = $this->importInput($review);
        $this->post('/bank-statements/import', [...$input, 'confirmed' => false] + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $this->post('/bank-statements/import', $input + $bind)->assertStatus(303);
        $this->post('/bank-statements/import', $input + $bind)->assertStatus(303);
        $line = BankStatementLine::sole();
        $this->get('/bank-statements/'.$line->id)->assertOk()->assertSee('Suggested family from exact payment reference')->assertSee('Holder whose share this pays');
        $this->post('/bank-statements/'.$line->id.'/review', $this->selection() + $bind)->assertOk()->assertSee('Confirmation creates one verified EFT receipt');
        $preview = app(BankStatements::class)->previewMatch($this->admin, $line->id, $this->selection());
        $resolve = $this->resolveInput($preview);
        $this->post('/bank-statements/'.$line->id.'/resolve', $resolve + $bind)->assertStatus(303);
        $this->post('/bank-statements/'.$line->id.'/resolve', $resolve + $bind)->assertStatus(303);
        $this->get('/bank-statements/'.$line->id)->assertOk()->assertSee('Open matched fee receipt')->assertDontSee('Review EFT match');
        $this->get('/bank-statements')->assertOk()->assertDontSee('BANK-001');
        $this->get('/bank-statements?status=matched')->assertOk()->assertSee('BANK-001');
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->get('/bank-statements?status=all')->assertOk()->assertSee('Bank history is read-only while fees are disabled.');
        $this->post('/bank-statements/import', $this->importInput($review) + $bind)->assertForbidden();
        $this->assertSame(1, FeePayment::count());
    }

    public function test_wrong_role_actor_school_query_and_permission_revocation_are_refused_without_flashing_private_inputs(): void
    {
        $line = $this->imported();
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $this->actingAs($this->admin)->post('/bank-statements/'.$line->id.'/review', $this->selection() + [...$bind, 'expected_school_id' => 2])->assertForbidden();
        $this->post('/bank-statements/'.$line->id.'/review', $this->selection() + [...$bind, 'expected_account_id' => $this->admin->id + 999])->assertForbidden();
        $this->post('/bank-statements/'.$line->id.'/review?unsafe=1', $this->selection() + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $this->post('/bank-statements/'.$line->id.'/review', $this->selection(['reason' => 'short']) + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->actingAs($teacher)->get('/bank-statements')->assertForbidden();
        $this->get('/bank-statements/'.$line->id)->assertForbidden();
        $this->get('/bank-statements/template')->assertForbidden();
        $this->post('/bank-statements/'.$line->id.'/review', $this->selection() + ['expected_account_id' => $teacher->id, 'expected_school_id' => 1])->assertForbidden();
        $review = app(BankStatements::class)->previewMatch($this->admin, $line->id, $this->selection());
        SchoolMembership::where('user_id', $this->admin->id)->update(['status' => 'suspended']);
        $this->refuses(fn () => app(BankStatements::class)->resolve($this->admin, $line->id, $this->resolveInput($review)), 403);
        $this->assertSame(0, FeePayment::count());
    }

    public function test_a_different_finance_actor_cannot_use_another_actors_import_or_match_fingerprint(): void
    {
        $other = User::findOrFail($this->schoolA->ids['tara_user']);
        $other->givePermissionTo('fee-payments-manage');
        $review = app(BankStatements::class)->previewImport($this->admin, $this->csv());
        $this->refuses(fn () => app(BankStatements::class)->import($other, $this->importInput($review)), 409);
        $line = $this->imported();
        $match = app(BankStatements::class)->previewMatch($this->admin, $line->id, $this->selection());
        $this->refuses(fn () => app(BankStatements::class)->resolve($other, $line->id, $this->resolveInput($match)), 409);
        $this->assertSame(0, FeePayment::count());
    }

    public function test_incomplete_import_is_reported_and_projection_rebuild_refuses_it_without_treating_unmatched_as_an_error(): void
    {
        $this->imported();
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
        BankStatementImport::create(['digest' => str_repeat('b', 64), 'row_count' => 1, 'created_by' => $this->admin->id, 'created_at' => now('UTC')]);
        $report = app(FeePaymentReconciliation::class)->inspect();
        $this->assertSame(1, $report['incomplete_bank_imports']);
        $this->assertFalse(FeePaymentReconciliation::healthy($report));
        $this->artisan('ledger:reconcile --school=1 --rebuild --json')->assertExitCode(1);
    }

    public function test_import_rechecks_business_conflicts_that_appear_after_preview_and_commits_no_partial_rows(): void
    {
        $bank = app(BankStatements::class);
        $review = $bank->previewImport($this->admin, $this->csv([
            ['2026-06-15', 'BANK-000', '', 'New first row', '1.00', '', 'ZAR'],
            ['2026-06-15', 'BANK-001', '', 'Original details', '4.00', '', 'ZAR'],
        ]));
        $this->imported($this->csv([['2026-06-15', 'BANK-001', '', 'Changed details', '5.00', '', 'ZAR']]));
        $this->refuses(fn () => $bank->import($this->admin, $this->importInput($review)), 409);
        $this->assertSame(1, BankStatementLine::count());
        $this->assertSame(1, BankStatementImport::count());
    }

    public function test_school_local_calendar_rejects_future_rows_but_accepts_current_local_day_ahead_of_utc(): void
    {
        $this->travelTo(now('UTC')->setDateTime(2026, 6, 15, 22, 30, 0));
        app(BankStatements::class)->previewImport($this->admin, $this->csv([['2026-06-16', 'BANK-LOCAL-DAY', '', '', '0.01', '', 'ZAR']]));
        $this->refuses(fn () => app(BankStatements::class)->previewImport($this->admin, $this->csv([['2026-06-17', 'BANK-FUTURE', '', '', '0.01', '', 'ZAR']])), 422);
        $this->assertSame(0, BankStatementLine::count());
    }

    public function test_replay_and_confirmation_require_fresh_permission_even_when_prior_operation_exists(): void
    {
        $line = $this->imported();
        $review = app(BankStatements::class)->previewMatch($this->admin, $line->id, $this->selection());
        $input = $this->resolveInput($review);
        app(BankStatements::class)->resolve($this->admin, $line->id, $input);
        $permission = DB::table('permissions')->where('name', 'fee-payments-manage')->value('id');
        DB::table('role_has_permissions')->where('permission_id', $permission)->delete();
        DB::table('model_has_permissions')->where('permission_id', $permission)->delete();
        $this->refuses(fn () => app(BankStatements::class)->resolve($this->admin, $line->id, $input), 403);
        $this->actingAs($this->admin)->get('/bank-statements')->assertForbidden();
        $this->assertSame(1, FeePayment::count());
    }

    public function test_excluded_resolution_and_import_lineage_cannot_be_edited_deleted_or_reopened(): void
    {
        $line = $this->imported();
        $review = app(BankStatements::class)->previewMatch($this->admin, $line->id, $this->selection(['kind' => 'excluded']));
        app(BankStatements::class)->resolve($this->admin, $line->id, $this->resolveInput($review));
        foreach ([fn () => DB::table('bank_statement_resolutions')->update(['reason' => 'Edited']),
            fn () => DB::table('bank_statement_resolutions')->delete(),
            fn () => DB::table('bank_statement_import_rows')->update(['bank_statement_line_id' => $line->id]),
            fn () => DB::table('bank_statement_imports')->delete(),
            fn () => DB::table('bank_statement_import_rows')->insert(['school_id' => 1, 'bank_statement_import_id' => BankStatementImport::sole()->id, 'bank_statement_line_id' => $line->id]),
        ] as $write) {
            try {
                $write();
                $this->fail('Expected immutable bank history refusal');
            } catch (QueryException $e) {
                $this->assertStringContainsString('45000', $e->getMessage());
            }
        }
        $this->refuses(fn () => app(BankStatements::class)->previewMatch($this->admin, $line->id, $this->selection()), 409);
        $this->assertSame(0, FeePayment::count());
    }

    public function test_large_valid_utf8_csv_can_confirm_within_the_private_form_payload_limit(): void
    {
        $rows = [];
        for ($i = 0; $i < 400; $i++) {
            $rows[] = ['2026-06-15', 'UTF8-'.$i, '', str_repeat($i < 200 ? 'é' : "\u{2028}", 240), '0.01', '', 'ZAR'];
        }
        $csv = $this->csv($rows);
        $this->assertLessThanOrEqual(262144, strlen($csv));
        $preview = app(BankStatements::class)->previewImport($this->admin, $csv);
        $this->assertCount(400, $preview['rows']);
        $this->assertLessThanOrEqual(600000, strlen($preview['payload']));
        $import = app(BankStatements::class)->import($this->admin, $this->importInput($preview));
        $this->assertNotNull($import->sealed_at);
        $this->assertSame(400, BankStatementLine::count());
        $this->assertSame(0, FeePayment::count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    private function family(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic split family', 'currency' => 'ZAR', 'session_year_id' => $this->year,
            'learner_ids' => [$this->schoolA->ids['asha'], $this->schoolA->ids['cara']], 'holders' => [['user_id' => $this->schoolA->ids['farid_user'], 'share_percent' => '50'], ['user_id' => $this->schoolA->ids['mona_user'], 'share_percent' => '50']]], $changes);
    }

    private function csv(array $rows = []): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, BankCsv::HEADER, escape: '');
        foreach ($rows ?: [['2026-06-15', 'BANK-001', $this->account->reference, 'Synthetic family payment', '4.00', '', 'ZAR']] as $row) {
            fputcsv($stream, $row, escape: '');
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }

    private function imported(?string $csv = null): BankStatementLine
    {
        $review = app(BankStatements::class)->previewImport($this->admin, $csv ?? $this->csv());
        app(BankStatements::class)->import($this->admin, $this->importInput($review));

        return BankStatementLine::orderByDesc('id')->firstOrFail();
    }

    private function importInput(array $review): array
    {
        return ['operation_id' => (string) Str::uuid(), 'payload' => $review['payload'], 'fingerprint' => $review['fingerprint'], 'confirmed' => true];
    }

    private function selection(array $changes = []): array
    {
        return array_replace(['kind' => 'matched', 'fee_account_id' => $this->account->id, 'holder_user_id' => $this->schoolA->ids['farid_user'], 'pinned_learner_id' => null, 'reason' => 'Verified synthetic bank evidence against payer and legacy records.'], $changes);
    }

    private function resolveInput(array $review): array
    {
        return array_intersect_key($review, BankStatements::selectionRules()) + ['operation_id' => (string) Str::uuid(), 'fingerprint' => $review['fingerprint'], 'confirmed' => true];
    }

    private function existing(array $changes = []): FeePayment
    {
        return app(FeePayments::class)->record($this->admin, array_replace(['operation_id' => (string) Str::uuid(), 'fee_account_id' => $this->account->id, 'holder_user_id' => $this->schoolA->ids['farid_user'], 'pinned_learner_id' => null, 'method' => 'eft', 'source_reference' => 'BANK-001', 'amount' => '4.00', 'received_on' => '2026-06-15', 'confirmed' => true], $changes));
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
