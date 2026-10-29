<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeCharge;
use App\Models\Money\FeeCreditNote;
use App\Models\Money\FeeCreditRelease;
use App\Models\Money\FeePayerCorrection;
use App\Models\Money\FeePayment;
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
use App\Services\Money\FeeItems;
use App\Services\Money\FeePayerCorrections;
use App\Services\Money\FeePaymentReconciliation;
use App\Services\Money\FeePayments;
use App\Services\Money\FeePaymentSettings;
use App\Services\Money\FeePlans;
use App\Services\Money\FeeStatements;
use App\Services\Money\FeeTaxSettings;
use App\Services\ParentPolicyService;
use App\Services\Platform\Ledger\CentsColumns;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class FeePayerCorrectionsTest extends TestCase
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

    public function test_unpaid_payer_correction_preserves_original_invoice_and_equal_school_totals(): void
    {
        $this->bill();
        $line = $this->line();
        $before = $line->getRawOriginal();
        $totals = DB::table('ledger_accounts')->where('school_id', 1)->pluck('balance_cents', 'id')->all();
        $input = $this->correction();
        $service = app(FeePayerCorrections::class);
        $preview = $service->preview($this->admin, $input);
        $row = $service->post($this->admin, $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true]);
        $again = $service->post($this->admin, $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true]);
        $this->assertSame($row->id, $again->id);
        $this->assertSame($before, $line->fresh()->getRawOriginal());
        $this->assertSame($totals, DB::table('ledger_accounts')->where('school_id', 1)->pluck('balance_cents', 'id')->all());
        $this->assertSame(0, FeeCreditRelease::count());
        $this->assertSame(1, FeePayerCorrection::count());
        $rows = app(FeeStatements::class)->charges($this->account);
        $new = collect($rows)->where('holder_user_id', $this->schoolA->ids['mona_user'])->firstWhere('invoice_number', 'CORR-'.FeeCharge::sole()->id);
        $this->assertSame(200, $new['outstanding_cents']);
        CentsColumns::backfill();
        $result = Artisan::call('ledger:reconcile', ['--school' => 1, '--json' => true]);
        $this->assertSame(0, $result, Artisan::output());
    }

    public function test_paid_allocations_and_held_credit_cannot_be_reassigned(): void
    {
        $this->bill();
        $this->policy();
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt(['amount' => '10.00']));
        $this->apply($payment);
        $beforePayment = $payment->getRawOriginal();
        $allocations = PaymentAllocation::orderBy('id')->get()->map->getRawOriginal()->all();
        $this->refuses(fn () => app(FeePayerCorrections::class)->preview($this->admin, $this->correction()), 422);
        $this->assertSame($beforePayment, $payment->fresh()->getRawOriginal());
        $this->assertSame($allocations, PaymentAllocation::orderBy('id')->get()->map->getRawOriginal()->all());
        $this->assertSame(0, FeePayerCorrection::count());
    }

    public function test_stale_payment_and_revoked_new_payer_refuse_posting_without_partial_credit(): void
    {
        $this->bill();
        $service = app(FeePayerCorrections::class);
        $input = $this->correction();
        $preview = $service->preview($this->admin, $input);
        $this->policy();
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt(['amount' => '1.00']));
        $this->apply($payment);
        $this->refuses(fn () => $service->post($this->admin, $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true]), 409);
        $preview = $service->preview($this->admin, $input);
        GuardianLink::where('guardian_user_id', $input['new_holder_user_id'])->update(['can_see_fees' => false]);
        $this->refuses(fn () => $service->post($this->admin, $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true]), 422);
        $this->assertSame(0, FeeCreditNote::count());
        $this->assertSame(0, FeePayerCorrection::count());
    }

    public function test_wrong_role_school_and_nonconfigured_payer_cannot_read_original_debt(): void
    {
        $this->bill();
        $service = app(FeePayerCorrections::class);
        $input = $this->correction();
        $this->refuses(fn () => $service->preview(User::findOrFail($this->schoolA->ids['farid_user']), $input), 403);
        $this->refuses(fn () => $service->preview($this->admin, [...$input, 'new_holder_user_id' => $this->schoolA->ids['gina_user']]), 422);
        SchoolMembership::create(['school_id' => 2, 'user_id' => $this->admin->id, 'role' => 'Super Admin', 'status' => 'active', 'starts_on' => '2026-01-01']);
        app(CurrentSchool::class)->run(2, function () use ($service, $input): void {
            $actor = $this->admin->fresh();
            $actor->assignRole('Super Admin');
            $this->refuses(fn () => $service->preview($actor, $input), 404);
        });
        $this->assertSame(0, FeePayerCorrection::count());
    }

    public function test_original_correction_link_is_sql_immutable_and_rollback_is_retained(): void
    {
        $this->bill();
        $input = $this->correction();
        $s = app(FeePayerCorrections::class);
        $p = $s->preview($this->admin, $input);
        $row = $s->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]);
        foreach (['UPDATE fee_payer_corrections SET amount_cents=1 WHERE id=?', 'DELETE FROM fee_payer_corrections WHERE id=?'] as $sql) {
            try {
                DB::statement($sql, [$row->id]);
                $this->fail('Correction history changed');
            } catch (QueryException) {
                $this->assertSame(200, $row->fresh()->amount_cents);
            }
        }
        $m = require database_path('migrations/2026_10_28_240000_add_unpaid_fee_payer_corrections.php');
        try {
            $m->down();
            $this->fail('Retained correction history dropped');
        } catch (RuntimeException) {
            $this->assertSame(1, FeePayerCorrection::count());
        }
    }

    public function test_private_finance_review_post_binding_and_paused_history(): void
    {
        $this->bill();
        $input = $this->correction();
        $bind = ['expected_school_id' => 1, 'expected_account_id' => $this->admin->id];
        $this->actingAs($this->admin)->get('/fee-payer-corrections?account_id='.$this->account->id)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/fee-payer-corrections?invoice_line_id='.$input['invoice_line_id'].'&holder_user_id='.$input['holder_user_id'])->assertOk()->assertSee('Intended configured payer');
        $this->post('/fee-payer-corrections/review', $input + $bind)->assertOk()->assertSee('Post original unpaid payer correction')->assertSee('ZAR 2.00');
        $this->post('/fee-payer-corrections/review', $input + ['expected_school_id' => 2, 'expected_account_id' => $this->admin->id])->assertForbidden();
        $this->post('/fee-payer-corrections/review?unexpected=1', $input + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $this->post('/fee-payer-corrections/review', [...$input, 'amount' => 'invalid'] + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $s = app(FeePayerCorrections::class);
        $p = $s->preview($this->admin, $input);
        $post = $input + $bind + ['fingerprint' => $p['fingerprint'], 'confirmed' => true];
        $this->post('/fee-payer-corrections/post', $post)->assertStatus(303);
        $this->post('/fee-payer-corrections/post', $post)->assertStatus(303);
        $this->assertSame(1, FeePayerCorrection::count());
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->get('/fee-payer-corrections')->assertOk()->assertSee('Payer corrections are read-only');
        $this->post('/fee-payer-corrections/post', $post)->assertForbidden();
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']))->get('/fee-payer-corrections')->assertForbidden();
    }

    public function test_replacement_preserves_frozen_vat_after_current_item_price_and_rate_change(): void
    {
        $tax = app(FeeTaxSettings::class);
        $tax->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'enabled' => true, 'country_code' => 'ZA', 'tax_registration' => 'SYNTHETIC-ORIGINAL-VAT', 'seller_name' => 'Synthetic original seller', 'seller_address' => 'Synthetic school address']);
        $items = app(FeeItems::class);
        $itemInput = ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic original taxable charge', 'scope' => 'learner', 'amount' => '10.01', 'currency' => 'ZAR', 'tax_treatment' => 'vat_inclusive', 'vat_rate' => '15.00', 'state' => 'active'];
        $item = $items->save($this->admin, $itemInput);
        $charges = app(FeeCharges::class);
        $cart = ['operation_id' => (string) Str::uuid(), 'fee_item_id' => $item->id, 'fee_account_id' => $this->account->id, 'session_year_id' => $this->year, 'learner_id' => $this->schoolA->ids['asha'], 'charged_on' => '2026-06-15', 'due_on' => '2026-07-01', 'source_reference' => 'SYNTHETIC-ORIGINAL-TAXED-PAYER'];
        $quote = $charges->preview($this->admin, $cart);
        $original = $charges->post($this->admin, $cart + ['fingerprint' => $quote['fingerprint'], 'confirmed' => true]);
        $items->save($this->admin, [...$itemInput, 'operation_id' => (string) Str::uuid(), 'revision' => 1, 'amount' => '100.00', 'vat_rate' => '20.00', 'tax_treatment' => 'vat_exclusive'], $item->id);
        $input = $this->correction();
        $service = app(FeePayerCorrections::class);
        $preview = $service->preview($this->admin, $input);
        $row = $service->post($this->admin, $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true]);
        $replacement = FeeCharge::findOrFail($row->replacement_charge_id);
        $credit = FeeCreditNote::findOrFail($row->credit_note_id);
        $this->assertSame(200, $replacement->amount_cents);
        $this->assertSame(26, $replacement->vat_cents);
        $this->assertSame($credit->vat_cents, $replacement->vat_cents);
        $this->assertSame('vat_inclusive', $replacement->snapshot['tax_treatment']);
        $this->assertSame($original->vat_account_id, $replacement->vat_account_id);
        $this->assertSame(131, $original->fresh()->vat_cents);
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    public function test_parent_contracts_show_only_each_original_payers_debt_and_no_private_correction_details(): void
    {
        $this->bill();
        $input = $this->correction();
        $s = app(FeePayerCorrections::class);
        $p = $s->preview($this->admin, $input);
        $s->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]);
        $url = '/api/v2/parent/fees/accounts/'.$this->account->id.'/statement';
        foreach (['farid', 'mona'] as $name) {
            $parent = User::findOrFail($this->schoolA->ids[$name.'_user']);
            ParentPolicyService::acceptCurrent($parent);
            $this->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $parent->id]);
            Sanctum::actingAs($parent, ['*']);
            $res = $this->getJson($url)->assertOk();
            $res->assertDontSee($input['reason'])->assertDontSee('original_holder_id')->assertDontSee('new_holder_id')->assertDontSee('credit_note_id');
            $replacement = collect($res->json('data.charges.items'))->filter(fn ($r) => str_starts_with($r['invoice_number'], 'CORR-'));
            $this->assertCount($name === 'mona' ? 1 : 0, $replacement);
        }
    }

    private function correction(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'invoice_line_id' => $this->line()->id, 'holder_user_id' => $this->schoolA->ids['farid_user'], 'new_holder_user_id' => $this->schoolA->ids['mona_user'], 'amount' => '2.00', 'reason' => 'Synthetic reviewed original unpaid payer debt correction'];
    }

    private function line(): InvoiceLine
    {
        return InvoiceLine::where('learner_id', $this->schoolA->ids['asha'])->firstOrFail();
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
