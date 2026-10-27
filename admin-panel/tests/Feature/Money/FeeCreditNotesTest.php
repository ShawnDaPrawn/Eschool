<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\BillingOperation;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeCreditNote;
use App\Models\Money\FeeCreditRelease;
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
use App\Services\Money\FeeCreditNotes;
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

class FeeCreditNotesTest extends TestCase
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

    public function test_unpaid_credit_reduces_only_the_selected_share_and_preserves_the_posted_invoice(): void
    {
        $this->bill();
        $line = $this->line();
        $original = $line->getRawOriginal();
        $input = $this->credit(['amount' => '2.00']);
        $preview = app(FeeCreditNotes::class)->preview($this->admin, $input);
        $this->assertSame(200, $preview['unpaid_cents']);
        $this->assertSame(0, $preview['released_cents']);
        $note = app(FeeCreditNotes::class)->post($this->admin, $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true]);
        $this->assertNotNull($note->sealed_at);
        $this->assertSame($original, $line->fresh()->getRawOriginal());
        $statement = app(FeeStatements::class)->finance($this->account->fresh());
        $this->assertSame(1802, $statement['outstanding_cents']);
        $this->assertSame(200, $statement['credited_cents']);
        $this->assertSame(2002, $statement['gross_charged_cents']);
        $this->assertSame(0, $statement['credit_cents']);
        $this->assertSame(2803, (int) DB::table('ledger_accounts')->where('type', 'fee_income')->sum('balance_cents'));
        $this->assertSame(0, FeeCreditRelease::count());
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    public function test_paid_credit_restores_the_original_receipt_and_reallocation_can_exceed_its_gross_historic_rows(): void
    {
        $this->bill();
        $this->policy();
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt());
        $this->apply($payment);
        $note = $this->postCredit(['amount' => '4.00']);
        $this->assertSame(101, $note->unpaid_cents);
        $this->assertSame(299, $note->released_cents);
        $this->assertSame(299, app(FeePayments::class)->available($payment));
        $this->apply($payment);
        $this->assertSame(0, app(FeePayments::class)->available($payment));
        $this->assertSame(699, (int) PaymentAllocation::where('payment_id', $payment->id)->sum('amount_cents'));
        $this->postCredit(['amount' => '1.01']);
        $this->assertSame(101, app(FeePayments::class)->available($payment));
        $this->apply($payment);
        $statement = app(FeeStatements::class)->finance($this->account->fresh());
        $this->assertSame(501, $statement['credited_cents']);
        $this->assertSame(1101, $statement['outstanding_cents']);
        $this->assertSame(0, $statement['credit_cents']);
        $this->assertSame(800, (int) PaymentAllocation::where('payment_id', $payment->id)->sum('amount_cents'));
        $this->assertSame(400, (int) FeeCreditRelease::sum('amount_cents'));
        $this->assertSame(400, (int) DB::table('ledger_accounts')->where('type', 'cash_desk')->sum('balance_cents'));
        $this->assertSame(2502, (int) DB::table('ledger_accounts')->where('type', 'fee_income')->sum('balance_cents'));
        app(FeePayments::class)->verifyCredit($payment);
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    public function test_restoration_preserves_a_learner_pin_and_never_pays_another_holder(): void
    {
        $this->bill();
        $this->policy();
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt(['amount' => '9.00', 'pinned_learner_id' => $this->schoolA->ids['asha']]));
        $this->apply($payment);
        $this->postCredit(['amount' => '5.01']);
        $this->assertSame(900, app(FeePayments::class)->available($payment));
        $preview = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        $this->assertSame(0, $preview['amount_cents']);
        $this->assertSame(900, $preview['credit_left_cents']);
        $this->assertSame($this->schoolA->ids['asha'], $payment->fresh()->pinned_learner_id);
        $this->assertSame(1000, array_sum(array_column(app(FeeStatements::class)->charges($this->account, $this->schoolA->ids['mona_user']), 'outstanding_cents')));
    }

    public function test_releases_take_latest_allocations_first_with_exact_partial_cents(): void
    {
        $this->bill();
        $this->policy();
        $first = app(FeePayments::class)->record($this->admin, $this->receipt(['amount' => '2.00']));
        $this->apply($first);
        $second = app(FeePayments::class)->record($this->admin, $this->receipt(['amount' => '1.00']));
        $this->apply($second);
        $note = $this->postCredit(['amount' => '4.50']);
        $this->assertSame(201, $note->unpaid_cents);
        $this->assertSame(249, $note->released_cents);
        $this->assertSame([$second->id, $first->id], FeeCreditRelease::orderBy('id')->pluck('payment_id')->all());
        $this->assertSame([100, 149], FeeCreditRelease::orderBy('id')->pluck('amount_cents')->all());
        $this->assertSame(149, app(FeePayments::class)->available($first));
        $this->assertSame(100, app(FeePayments::class)->available($second));
    }

    public function test_changed_allocations_credit_and_private_reason_invalidate_a_preview_but_durable_exact_replay_is_safe(): void
    {
        $this->bill();
        $this->policy();
        $input = $this->credit();
        $preview = app(FeeCreditNotes::class)->preview($this->admin, $input);
        $post = $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true];
        $this->refuses(fn () => app(FeeCreditNotes::class)->post($this->admin, [...$post, 'reason' => 'Different private explanation']), 409);
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt());
        $this->apply($payment);
        $this->refuses(fn () => app(FeeCreditNotes::class)->post($this->admin, $post), 409);
        $current = app(FeeCreditNotes::class)->preview($this->admin, $input);
        $post = [...$post, 'fingerprint' => $current['fingerprint']];
        $note = app(FeeCreditNotes::class)->post($this->admin, $post);
        $this->travel(3)->days();
        $this->assertSame($note->id, app(FeeCreditNotes::class)->post($this->admin, $post)->id);
        $this->refuses(fn () => app(FeeCreditNotes::class)->post($this->admin, [...$post, 'operation_id' => (string) Str::uuid()]), 409);
        $this->assertSame(1, FeeCreditNote::count());
    }

    public function test_limits_confirmation_and_recorded_holder_are_required(): void
    {
        $this->bill();
        foreach (['0.00', '-1.00', '5.02', '1.001'] as $amount) {
            $this->refuses(fn () => app(FeeCreditNotes::class)->preview($this->admin, $this->credit(['amount' => $amount])), 422);
        }
        $this->refuses(fn () => app(FeeCreditNotes::class)->preview($this->admin, $this->credit(['holder_user_id' => $this->schoolA->ids['gina_user']])), 422);
        $input = $this->credit();
        $preview = app(FeeCreditNotes::class)->preview($this->admin, $input);
        $this->refuses(fn () => app(FeeCreditNotes::class)->post($this->admin, $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => false]), 422);
        $this->postCredit(['amount' => '5.01']);
        $this->refuses(fn () => app(FeeCreditNotes::class)->preview($this->admin, $this->credit(['amount' => '0.01'])), 422);
    }

    public function test_private_explanations_are_encrypted_and_never_enter_audit_events_or_parent_statements(): void
    {
        $this->bill();
        $note = $this->postCredit(['reason' => 'CONFIDENTIAL-MONA payment discussion']);
        $stored = DB::table('fee_credit_notes')->where('id', $note->id)->value('reason');
        $this->assertStringNotContainsString('CONFIDENTIAL-MONA', $stored);
        $this->assertSame('CONFIDENTIAL-MONA payment discussion', $note->fresh()->reason);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $json = json_encode(app(ParentFeeStatements::class)->show($parent, $this->account->id), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('CONFIDENTIAL-MONA', $json);
        $this->assertSame(1, DB::table('domain_events')->where('type', 'fees.credit_posted')->count());
        $this->assertStringNotContainsString('CONFIDENTIAL-MONA', json_encode(DB::table('domain_events')->where('type', 'fees.credit_posted')->get(), JSON_THROW_ON_ERROR));
    }

    public function test_synthetic_later_release_failure_rolls_back_documents_money_audit_and_operation_receipt(): void
    {
        $this->bill();
        $this->policy();
        $first = app(FeePayments::class)->record($this->admin, $this->receipt(['amount' => '2.00']));
        $this->apply($first);
        $second = app(FeePayments::class)->record($this->admin, $this->receipt(['amount' => '1.00']));
        $this->apply($second);
        $input = $this->credit(['amount' => '4.50']);
        $preview = app(FeeCreditNotes::class)->preview($this->admin, $input);
        $post = $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true];
        $counts = fn () => [FeeCreditNote::count(), FeeCreditRelease::count(), DB::table('ledger_entries')->count(), DB::table('ledger_postings')->count(), DB::table('domain_events')->count(), BillingOperation::count()];
        $before = $counts();
        $original = FeeCreditRelease::getEventDispatcher();
        FeeCreditRelease::setEventDispatcher(clone $original);
        $created = 0;
        FeeCreditRelease::created(function () use (&$created): void {
            if (++$created === 2) {
                throw new RuntimeException('Synthetic later release failure');
            }
        });
        try {
            app(FeeCreditNotes::class)->post($this->admin, $post);
            $this->fail('Expected synthetic release refusal');
        } catch (RuntimeException $e) {
            $this->assertSame('Synthetic later release failure', $e->getMessage());
        } finally {
            FeeCreditRelease::setEventDispatcher($original);
        }
        $this->assertSame($before, $counts());
        $this->assertSame(0, app(FeePayments::class)->available($first));
        $this->assertNotNull(app(FeeCreditNotes::class)->post($this->admin, $post)->sealed_at);
    }

    public function test_sealed_notes_and_release_rows_are_database_immutable_and_retained(): void
    {
        $this->bill();
        $this->policy();
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt());
        $this->apply($payment);
        $note = $this->postCredit(['amount' => '4.00']);
        foreach ([fn () => DB::table('fee_credit_notes')->where('id', $note->id)->update(['reason_code' => 'other']), fn () => DB::table('fee_credit_notes')->where('id', $note->id)->delete(), fn () => DB::table('fee_credit_releases')->delete(), fn () => DB::table('fee_credit_releases')->update(['amount_cents' => 1])] as $write) {
            try {
                $write();
                $this->fail('Expected immutable history refusal');
            } catch (QueryException) {
                $this->addToAssertionCount(1);
            }
        }
        $migration = require database_path('migrations/2026_10_27_300000_create_fee_credit_notes.php');
        try {
            $migration->down();
            $this->fail('Expected retained history refusal');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Retain credit', $e->getMessage());
        }
    }

    public function test_other_roles_revocation_and_disabled_fees_cannot_post_or_recover_credit_notes(): void
    {
        $this->bill();
        $input = $this->credit();
        foreach (['farid', 'tara', 'asha'] as $actor) {
            $this->refuses(fn () => app(FeeCreditNotes::class)->preview(User::findOrFail($this->schoolA->ids[$actor.'_user']), $input), 403);
        }
        $preview = app(FeeCreditNotes::class)->preview($this->admin, $input);
        $post = $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true];
        app(FeeCreditNotes::class)->post($this->admin, $post);
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->refuses(fn () => app(FeeCreditNotes::class)->post($this->admin, $post), 403);
        $this->assertSame(1, FeeCreditNote::count());
    }

    public function test_parent_credit_history_contract_pagination_ownership_and_visibility(): void
    {
        $this->bill();
        $this->postCredit(['amount' => '1.00', 'reason' => 'SECRET-PARENT-EXPLANATION']);
        $this->postCredit(['amount' => '1.00']);
        $farid = User::findOrFail($this->schoolA->ids['farid_user']);
        ParentPolicyService::acceptCurrent($farid);
        $url = '/api/v2/parent/fees/accounts/'.$this->account->id.'/statement?per_page=1&credits_page=2';
        $response = $this->api($farid)->getJson($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.gross_charged_cents', 1002)->assertJsonPath('data.credited_cents', 200)->assertJsonPath('data.charged_cents', 802)->assertJsonPath('data.credit_notes.total', 2)->assertJsonPath('data.credit_notes.current_page', 2);
        $this->assertCount(1, $response->json('data.credit_notes.items'));
        $this->assertStringNotContainsString('SECRET-PARENT-EXPLANATION', $response->getContent());
        $mona = User::findOrFail($this->schoolA->ids['mona_user']);
        ParentPolicyService::acceptCurrent($mona);
        $this->api($mona)->getJson($url)->assertOk()->assertJsonPath('data.credited_cents', 0)->assertJsonPath('data.credit_notes.total', 0);
        GuardianLink::where('guardian_user_id', $farid->id)->where('learner_id', $this->schoolA->ids['asha'])->update(['can_see_fees' => false]);
        $this->api($farid)->getJson($url)->assertOk()->assertJsonPath('data.credited_cents', 0)->assertJsonPath('data.credit_notes.total', 0);
    }

    public function test_panel_review_confirmation_private_errors_and_retained_history(): void
    {
        $this->bill();
        $input = $this->credit();
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $this->actingAs($this->admin)->get('/fee-credit-notes')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/fee-credit-notes/new?account_id='.$this->account->id)->assertOk()->assertSee('Credit this share');
        $this->get('/fee-credit-notes/new?invoice_line_id='.$input['invoice_line_id'].'&holder_user_id='.$input['holder_user_id'])->assertOk()->assertSee('Private Finance explanation');
        $this->post('/fee-credit-notes/review', $input + $bind)->assertOk()->assertSee('Post credit note once')->assertSee('ZAR 2.00');
        $preview = app(FeeCreditNotes::class)->preview($this->admin, $input);
        $post = $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true] + $bind;
        $this->post('/fee-credit-notes/new', [...$post, 'confirmed' => false])->assertStatus(422)->assertSessionMissing('_old_input')->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/fee-credit-notes/new', $post)->assertStatus(303);
        $this->post('/fee-credit-notes/new', $post)->assertStatus(303);
        $note = FeeCreditNote::sole();
        $this->get('/fee-credit-notes/'.$note->id)->assertOk()->assertSee('Synthetic private correction');
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->get('/fee-credit-notes')->assertOk()->assertSee('Credit history is read-only while fees are disabled.');
        $this->get('/fee-credit-notes/'.$note->id)->assertOk()->assertSee('Synthetic private correction');
        $this->post('/fee-credit-notes/new', $post)->assertForbidden();
    }

    public function test_panel_actor_school_binding_roles_and_actual_foreign_records_are_refused(): void
    {
        $this->bill();
        $input = $this->credit();
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $this->actingAs($this->admin)->post('/fee-credit-notes/review', $input + [...$bind, 'expected_school_id' => 2])->assertForbidden();
        $this->post('/fee-credit-notes/review', $input + [...$bind, 'expected_account_id' => $this->admin->id + 999])->assertForbidden();
        $this->post('/fee-credit-notes/review?unexpected=1', $input + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->actingAs($teacher)->get('/fee-credit-notes')->assertForbidden();
        $this->post('/fee-credit-notes/review', $input + ['expected_school_id' => 1, 'expected_account_id' => $teacher->id])->assertForbidden();
        SchoolMembership::create(['school_id' => 2, 'user_id' => $this->admin->id, 'role' => 'Super Admin', 'status' => 'active', 'starts_on' => '2026-01-01']);
        app(CurrentSchool::class)->run(2, function () use ($input): void {
            $actor = $this->admin->fresh();
            $actor->assignRole('Super Admin');
            $this->refuses(fn () => app(FeeCreditNotes::class)->preview($actor, $input), 404);
        });
        $this->assertSame(0, FeeCreditNote::count());
    }

    public function test_unlinked_income_posting_blocks_rebuild_even_when_entries_balance(): void
    {
        $this->bill();
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt());
        $income = (int) DB::table('ledger_accounts')->where('school_id', 1)->where('type', 'fee_income')->value('id');
        app(LedgerService::class)->post('synthetic-unmapped-income', 'synthetic-unmapped-income', 'Synthetic income drift', [
            Posting::debit($payment->debit_account_id, Money::ofCents(1, 'ZAR')), Posting::credit($income, Money::ofCents(1, 'ZAR')),
        ]);
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        $this->assertCount(1, app(FeePaymentReconciliation::class)->inspect()['fee_income_differences']);
        DB::table('ledger_accounts')->where('id', $payment->debit_account_id)->increment('balance_cents', 7);
        $before = DB::table('ledger_accounts')->where('id', $payment->debit_account_id)->value('balance_cents');
        $this->artisan('ledger:reconcile --school=1 --rebuild --json')->assertExitCode(1);
        $this->assertSame($before, DB::table('ledger_accounts')->where('id', $payment->debit_account_id)->value('balance_cents'));
    }

    public function test_an_incomplete_credit_is_reported_and_blocks_reads_and_projection_repair(): void
    {
        $this->bill();
        $note = $this->postCredit();
        $draft = $note->replicate(['sealed_at']);
        $draft->ledger_entry_id = Invoice::findOrFail($this->line()->invoice_id)->ledger_entry_id;
        $draft->sealed_at = null;
        $draft->save();
        $this->assertSame(1, app(FeePaymentReconciliation::class)->inspect()['incomplete_credit_notes']);
        $this->refuses(fn () => app(FeeStatements::class)->finance($this->account), 409);
        $this->refuses(fn () => app(FeeCreditNotes::class)->preview($this->admin, $this->credit()), 409);
        $this->artisan('ledger:reconcile --school=1 --rebuild --json')->assertExitCode(1);
    }

    public function test_fresh_credit_permission_revocation_blocks_replay_and_private_history(): void
    {
        $this->bill();
        $input = $this->credit();
        $preview = app(FeeCreditNotes::class)->preview($this->admin, $input);
        $post = $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true];
        $note = app(FeeCreditNotes::class)->post($this->admin, $post);
        $permission = DB::table('permissions')->where('name', 'fee-credits-manage')->where('guard_name', 'web')->value('id');
        DB::table('role_has_permissions')->where('permission_id', $permission)->whereIn('role_id', DB::table('roles')->where('school_id', 1)->select('id'))->delete();
        DB::table('model_has_permissions')->where('school_id', 1)->where('model_id', $this->admin->id)->where('permission_id', $permission)->delete();
        $this->refuses(fn () => app(FeeCreditNotes::class)->post($this->admin, $post), 403);
        $this->actingAs($this->admin)->get('/fee-credit-notes/'.$note->id)->assertForbidden()->assertDontSee('Synthetic private correction');
        $this->get('/fee-credit-notes')->assertForbidden();
        $this->get('/fee-payments/accounts/'.$this->account->id.'/statement')->assertOk()->assertDontSee('Synthetic private correction');
        $this->assertSame(1, FeeCreditNote::count());
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
