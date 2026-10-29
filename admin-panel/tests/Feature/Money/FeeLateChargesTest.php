<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeCharge;
use App\Models\Money\FeeLateCharge;
use App\Models\Money\FeeLatePolicyRevision;
use App\Models\Money\FeePayment;
use App\Models\Money\InvoiceLine;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\BillRuns;
use App\Services\Money\FeeAccounts;
use App\Services\Money\FeeAllocations;
use App\Services\Money\FeeCreditNotes;
use App\Services\Money\FeeLateChargePolicies;
use App\Services\Money\FeeLateCharges;
use App\Services\Money\FeePayerCorrections;
use App\Services\Money\FeePaymentReconciliation;
use App\Services\Money\FeePayments;
use App\Services\Money\FeePaymentSettings;
use App\Services\Money\FeePlans;
use App\Services\Money\FeeTaxSettings;
use App\Services\ParentPolicyService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class FeeLateChargesTest extends TestCase
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

    private function latePolicy(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'currency' => 'ZAR', 'enabled' => true, 'scope' => 'annual_only', 'basis' => 'flat', 'flat_amount' => '2.00', 'rate_percent' => null, 'maximum_gross' => '1.50', 'grace_days' => 3, 'due_days' => 14, 'tax_treatment' => 'no_vat', 'vat_rate' => '0.00', 'private_reason' => 'Explicit synthetic school one-off late charge decision'], $changes);
    }

    private function lateInput(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'original_invoice_line_id' => $this->line()->id, 'holder_user_id' => $this->schoolA->ids['farid_user'], 'reason' => 'Reviewed original overdue debt and held-credit position'], $changes);
    }

    private function overdue(): void
    {
        $this->bill();
        $this->travelTo(now()->setDateTime(2026, 7, 10, 12, 0, 0));
    }

    public function test_no_school_default_or_missing_rule_can_create_a_late_invoice(): void
    {
        $this->overdue();
        $s = app(FeeLateCharges::class);
        $this->refuses(fn () => $s->preview($this->admin, $this->lateInput()), 422);
        app(FeeLateChargePolicies::class)->save($this->admin, $this->latePolicy(['enabled' => false]));
        $this->refuses(fn () => $s->preview($this->admin, $this->lateInput()), 422);
        $this->assertSame(0, FeeLateCharge::count());
        $this->assertSame(1, FeeLatePolicyRevision::count());
    }

    public function test_flat_gross_cap_due_days_and_original_payer_are_applied_once(): void
    {
        $this->overdue();
        app(FeeLateChargePolicies::class)->save($this->admin, $this->latePolicy());
        $input = $this->lateInput();
        $s = app(FeeLateCharges::class);
        $p = $s->preview($this->admin, $input);
        $this->assertSame(501, $p['basis_cents']);
        $this->assertSame(200, $p['uncapped_gross_cents']);
        $this->assertSame(150, $p['amount_cents']);
        $this->assertSame('2026-07-24', $p['due_on']);
        $this->assertSame('2026-07-01', $p['original_due_on']);
        $op = $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true];
        $row = $s->post($this->admin, $op);
        $this->assertSame($row->id, $s->post($this->admin, $op)->id);
        $this->assertSame(1, FeeLateCharge::count());
        $this->assertSame(150, $row->amount_cents);
        $this->refuses(fn () => $s->preview($this->admin, $this->lateInput()), 409);
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    public function test_rate_rounding_and_policy_changes_invalidate_the_reviewed_charge(): void
    {
        $this->overdue();
        $rules = app(FeeLateChargePolicies::class);
        $rules->save($this->admin, $this->latePolicy(['basis' => 'rate', 'flat_amount' => null, 'rate_percent' => '10.00', 'maximum_gross' => '1.00']));
        $input = $this->lateInput();
        $s = app(FeeLateCharges::class);
        $p = $s->preview($this->admin, $input);
        $this->assertSame(50, $p['amount_cents']);
        $rules->save($this->admin, $this->latePolicy(['revision' => 1, 'basis' => 'rate', 'flat_amount' => null, 'rate_percent' => '20.00', 'maximum_gross' => '1.00']));
        $this->refuses(fn () => $s->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]), 409);
        $this->assertSame(0, FeeLateCharge::count());
        $p = $s->preview($this->admin, $input);
        $this->assertSame(100, $p['amount_cents']);
        $this->assertSame(2, FeeLatePolicyRevision::count());
    }

    public function test_allocation_changes_the_actual_overdue_basis_and_old_review_cannot_post(): void
    {
        $this->overdue();
        app(FeeLateChargePolicies::class)->save($this->admin, $this->latePolicy());
        $s = app(FeeLateCharges::class);
        $input = $this->lateInput();
        $p = $s->preview($this->admin, $input);
        $this->policy();
        $payment = app(FeePayments::class)->record($this->admin, $this->receipt(['received_on' => '2026-07-10', 'amount' => '1.00']));
        $this->apply($payment);
        $this->refuses(fn () => $s->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]), 409);
        $this->assertSame(0, FeeLateCharge::count());
        $this->assertSame(401, $s->preview($this->admin, $input)['basis_cents']);
    }

    public function test_corrected_descendants_are_one_original_debt_basis_and_cannot_repeat_late_charges(): void
    {
        $this->overdue();
        $corrections = app(FeePayerCorrections::class);
        $input = ['operation_id' => (string) Str::uuid(), 'invoice_line_id' => $this->line()->id, 'holder_user_id' => $this->schoolA->ids['farid_user'], 'new_holder_user_id' => $this->schoolA->ids['mona_user'], 'amount' => '2.00', 'reason' => 'Synthetic reviewed original unpaid payer correction'];
        $p = $corrections->preview($this->admin, $input);
        $correction = $corrections->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]);
        app(FeeLateChargePolicies::class)->save($this->admin, $this->latePolicy(['basis' => 'rate', 'flat_amount' => null, 'rate_percent' => '10.00', 'maximum_gross' => '10.00']));
        $s = app(FeeLateCharges::class);
        $input = $this->lateInput(['holder_user_id' => $this->schoolA->ids['mona_user']]);
        $p = $s->preview($this->admin, $input);
        $this->assertSame(700, $p['basis_cents']);
        $this->assertCount(2, $p['basis_rows']);
        $this->assertSame(70, $p['amount_cents']);
        $s->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]);
        $newLine = InvoiceLine::where('fee_charge_id', $correction->replacement_charge_id)->sole();
        $this->refuses(fn () => $s->preview($this->admin, $this->lateInput(['original_invoice_line_id' => $newLine->id, 'holder_user_id' => $this->schoolA->ids['mona_user']])), 409);
        $this->assertSame(1, FeeLateCharge::count());
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    public function test_original_late_fees_and_their_payer_corrections_cannot_compound(): void
    {
        $this->overdue();
        app(FeeLateChargePolicies::class)->save($this->admin, $this->latePolicy());
        $s = app(FeeLateCharges::class);
        $input = $this->lateInput();
        $p = $s->preview($this->admin, $input);
        $row = $s->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]);
        $line = InvoiceLine::where('fee_charge_id', $row->replacement_charge_id)->sole();
        $this->refuses(fn () => $s->preview($this->admin, $this->lateInput(['original_invoice_line_id' => $line->id])), 422);
        $c = app(FeePayerCorrections::class);
        $input = ['operation_id' => (string) Str::uuid(), 'invoice_line_id' => $line->id, 'holder_user_id' => $this->schoolA->ids['farid_user'], 'new_holder_user_id' => $this->schoolA->ids['mona_user'], 'amount' => '0.50', 'reason' => 'Synthetic reviewed correction of an unpaid late invoice'];
        $p = $c->preview($this->admin, $input);
        $new = $c->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]);
        $line = InvoiceLine::where('fee_charge_id', $new->replacement_charge_id)->sole();
        $this->refuses(fn () => $s->preview($this->admin, $this->lateInput(['original_invoice_line_id' => $line->id, 'holder_user_id' => $this->schoolA->ids['mona_user']])), 422);
        $this->assertSame(1, FeeLateCharge::count());
    }

    public function test_grace_wrong_role_wrong_school_and_unconfigured_payer_are_refused(): void
    {
        $this->bill();
        app(FeeLateChargePolicies::class)->save($this->admin, $this->latePolicy());
        $s = app(FeeLateCharges::class);
        $input = $this->lateInput();
        $this->refuses(fn () => $s->preview($this->admin, $input), 422);
        $this->travelTo(now()->setDateTime(2026, 7, 10, 12, 0, 0));
        $this->refuses(fn () => $s->preview(User::findOrFail($this->schoolA->ids['farid_user']), $input), 403);
        $this->refuses(fn () => $s->preview($this->admin, $this->lateInput(['holder_user_id' => $this->schoolA->ids['gina_user']])), 422);
        SchoolMembership::create(['school_id' => 2, 'user_id' => $this->admin->id, 'role' => 'Super Admin', 'status' => 'active', 'starts_on' => '2026-01-01']);
        app(CurrentSchool::class)->run(2, function () use ($s, $input): void {
            $actor = $this->admin->fresh();
            $actor->assignRole('Super Admin');
            $this->refuses(fn () => $s->preview($actor, $input), 404);
        });
        $this->assertSame(0, FeeLateCharge::count());
    }

    public function test_inclusive_vat_original_credit_and_late_policy_history_stay_frozen(): void
    {
        $this->overdue();
        app(FeeTaxSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'enabled' => true, 'country_code' => 'ZA', 'tax_registration' => 'SYNTHETIC-LATE-VAT', 'seller_name' => 'Synthetic registered school', 'seller_address' => 'Synthetic original address']);
        $rules = app(FeeLateChargePolicies::class);
        $rules->save($this->admin, $this->latePolicy(['maximum_gross' => '1.15', 'tax_treatment' => 'vat_inclusive', 'vat_rate' => '15.00']));
        $s = app(FeeLateCharges::class);
        $input = $this->lateInput();
        $p = $s->preview($this->admin, $input);
        $this->assertSame(115, $p['amount_cents']);
        $this->assertSame(15, $p['vat_cents']);
        $row = $s->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]);
        $rules->save($this->admin, $this->latePolicy(['revision' => 1, 'maximum_gross' => '1.15', 'tax_treatment' => 'vat_inclusive', 'vat_rate' => '20.00']));
        $line = InvoiceLine::where('fee_charge_id', $row->replacement_charge_id)->sole();
        $credit = ['operation_id' => (string) Str::uuid(), 'invoice_line_id' => $line->id, 'holder_user_id' => $this->schoolA->ids['farid_user'], 'amount' => '0.50', 'reason_code' => 'billing_error', 'reason' => 'Synthetic correction uses original late invoice VAT'];
        $credits = app(FeeCreditNotes::class);
        $review = $credits->preview($this->admin, $credit);
        $this->assertSame(7, $review['vat_cents']);
        $credits->post($this->admin, $credit + ['fingerprint' => $review['fingerprint'], 'confirmed' => true]);
        $this->assertSame(15, FeeCharge::findOrFail($row->replacement_charge_id)->vat_cents);
        $this->assertSame(15, $row->fresh()->vat_cents);
        $this->assertSame(2, FeeLatePolicyRevision::count());
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
        foreach (['UPDATE fee_late_charges SET amount_cents=1 WHERE id=?', 'DELETE FROM fee_late_charges WHERE id=?'] as $sql) {
            try {
                DB::statement($sql, [$row->id]);
                $this->fail('Original late history changed');
            } catch (QueryException) {
                $this->assertSame(115, $row->fresh()->amount_cents);
            }
        }
        $m = require database_path('migrations/2026_10_28_250000_add_explicit_one_off_late_charges.php');
        try {
            $m->down();
            $this->fail('Retained late history dropped');
        } catch (RuntimeException) {
            $this->assertSame(1, FeeLateCharge::count());
        }
    }

    public function test_private_policy_and_late_review_forms_bind_school_actor_and_retain_paused_history(): void
    {
        $this->overdue();
        $bind = ['expected_school_id' => 1, 'expected_account_id' => $this->admin->id];
        $input = $this->lateInput();
        $this->actingAs($this->admin)->get('/fee-late-charges/policy?year_id='.$this->year.'&currency=ZAR')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/fee-late-charges/policy', $this->latePolicy() + $bind)->assertStatus(303);
        $this->get('/fee-late-charges?account_id='.$this->account->id.'&original_invoice_line_id='.$input['original_invoice_line_id'].'&holder_user_id='.$input['holder_user_id'])->assertOk()->assertSee('Private Finance late-charge reason');
        $this->post('/fee-late-charges/review', $input + $bind)->assertOk()->assertSee('Post reviewed one-off late charge')->assertSee('ZAR 1.50');
        $this->post('/fee-late-charges/review', $input + ['expected_account_id' => $this->admin->id, 'expected_school_id' => 2])->assertForbidden();
        $this->post('/fee-late-charges/review?unexpected=1', $input + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $p = app(FeeLateCharges::class)->preview($this->admin, $input);
        $post = $input + $bind + ['fingerprint' => $p['fingerprint'], 'confirmed' => true];
        $this->post('/fee-late-charges/post', $post)->assertStatus(303);
        $this->post('/fee-late-charges/post', $post)->assertStatus(303);
        $this->assertSame(1, FeeLateCharge::count());
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->get('/fee-late-charges')->assertOk()->assertSee('Late charges are read-only');
        $this->post('/fee-late-charges/post', $post)->assertForbidden();
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']))->get('/fee-late-charges')->assertForbidden();
        $this->get('/fee-late-charges/policy')->assertForbidden();
    }

    public function test_parent_statement_contract_exposes_only_own_late_invoice_and_no_private_basis(): void
    {
        $this->overdue();
        app(FeeLateChargePolicies::class)->save($this->admin, $this->latePolicy());
        $s = app(FeeLateCharges::class);
        $input = $this->lateInput();
        $p = $s->preview($this->admin, $input);
        $s->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]);
        foreach (['farid', 'mona'] as $name) {
            $parent = User::findOrFail($this->schoolA->ids[$name.'_user']);
            ParentPolicyService::acceptCurrent($parent);
            $this->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $parent->id]);
            Sanctum::actingAs($parent, ['*']);
            $res = $this->getJson('/api/v2/parent/fees/accounts/'.$this->account->id.'/statement')->assertOk();
            $res->assertDontSee($input['reason'])->assertDontSee('basis_rows')->assertDontSee('policy_revision_id');
            $late = collect($res->json('data.charges.items'))->filter(fn ($r) => str_starts_with($r['invoice_number'], 'LATE-'));
            $this->assertCount($name === 'farid' ? 1 : 0, $late);
        }
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
