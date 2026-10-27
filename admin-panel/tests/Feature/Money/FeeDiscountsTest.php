<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeCharge;
use App\Models\Money\FeeCreditNote;
use App\Models\Money\FeeDiscount;
use App\Models\Money\FeeDiscountRun;
use App\Models\Money\FeeItem;
use App\Models\Money\FeeTaxSetting;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\FeeAccounts;
use App\Services\Money\FeeAllocations;
use App\Services\Money\FeeCharges;
use App\Services\Money\FeeCreditNotes;
use App\Services\Money\FeeDiscountRuns;
use App\Services\Money\FeeDiscounts;
use App\Services\Money\FeeItems;
use App\Services\Money\FeePaymentReconciliation;
use App\Services\Money\FeePayments;
use App\Services\Money\FeePaymentSettings;
use App\Services\Money\FeeStatements;
use App\Services\Money\FeeTaxSettings;
use App\Services\Money\ParentFeeStatements;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class FeeDiscountsTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

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

    public function test_bursary_reviews_approves_posts_exact_original_holder_credits_once(): void
    {
        $this->policy();
        $item = $this->item(scope: 'learner');
        $charge = $this->postItem($item, $this->schoolA->ids['asha']);
        $rule = $this->rule($item, ['kind' => 'bursary', 'learner_id' => $this->schoolA->ids['asha'], 'amount_kind' => 'percent', 'amount_value' => 3333]);
        $before = Invoice::firstOrFail()->toArray();
        $run = $this->prepare($rule);
        $this->assertSame(334, $run->amount_cents);
        $this->assertSame(1001, $run->snapshot['basis_cents']);
        $this->refuses(fn () => app(FeeDiscountRuns::class)->post($this->admin, $run->id, $this->operation($run->revision)), 409);
        $run = app(FeeDiscountRuns::class)->approve($this->admin, $run->id, $this->operation($run->revision));
        $post = $this->operation($run->revision);
        $posted = app(FeeDiscountRuns::class)->post($this->admin, $run->id, $post);
        $this->assertSame($posted->id, app(FeeDiscountRuns::class)->post($this->admin, $run->id, $post)->id);
        $this->assertSame(2, FeeCreditNote::count());
        $this->assertSame(334, (int) FeeCreditNote::sum('amount_cents'));
        $this->assertSame($before, Invoice::firstOrFail()->toArray());
        $this->assertSame(667, app(FeeStatements::class)->finance($this->account)['outstanding_cents']);
        $this->refuses(fn () => $this->prepare($rule), 409);
        $this->healthy();
    }

    public function test_exemption_returns_paid_share_to_original_restricted_receipts_and_tax(): void
    {
        $this->policy();
        $item = $this->item(scope: 'learner');
        $this->postItem($item, $this->schoolA->ids['asha']);
        app(FeePaymentSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'allocation_order' => 'oldest_first']);
        $payment = app(FeePayments::class)->record($this->admin, ['operation_id' => (string) Str::uuid(), 'fee_account_id' => $this->account->id, 'holder_user_id' => $this->parent()->id, 'pinned_learner_id' => $this->schoolA->ids['asha'], 'amount' => '5.01', 'method' => 'cash', 'received_on' => '2026-06-15', 'source_reference' => 'Synthetic discount receipt', 'confirmed' => true]);
        $allocate = ['operation_id' => (string) Str::uuid()];
        $preview = app(FeeAllocations::class)->preview($this->admin, $payment->id, $allocate);
        app(FeeAllocations::class)->post($this->admin, $payment->id, $allocate + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true]);
        $run = $this->apply($this->rule($item, ['kind' => 'exemption', 'learner_id' => $this->schoolA->ids['asha'], 'amount_kind' => 'percent', 'amount_value' => 10000]));
        $this->assertSame(1001, $run->amount_cents);
        $this->assertSame(131, (int) FeeCreditNote::sum('vat_cents'));
        $this->assertSame(501, (int) FeeCreditNote::sum('released_cents'));
        $this->assertSame(0, app(FeeStatements::class)->finance($this->account)['outstanding_cents']);
        $this->assertSame(501, app(FeeStatements::class)->finance($this->account)['credit_cents']);
        $this->healthy();
    }

    public function test_sibling_position_uses_current_dated_family_and_staff_requires_current_staff_guardian(): void
    {
        $item = $this->item(mode: 'no_vat', rate: '0', scope: 'learner');
        $this->postItem($item, $this->schoolA->ids['asha']);
        $this->postItem($item, $this->schoolA->ids['cara']);
        $sibling = $this->rule($item, ['kind' => 'sibling', 'learner_id' => null, 'sibling_position' => 2]);
        $this->refuses(fn () => $this->prepare($sibling), 422);
        $second = $this->apply($sibling, $this->schoolA->ids['cara']);
        $this->assertSame(100, $second->amount_cents);
        $staff = $this->rule($item, ['kind' => 'staff', 'staff_roles' => ['Teacher']]);
        $this->refuses(fn () => $this->prepare($staff), 422);
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        GuardianLink::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $teacher->id, 'status' => 'active', 'relationship' => 'guardian', 'can_see_fees' => true]);
        $run = $this->prepare($staff);
        SchoolMembership::where('user_id', $teacher->id)->update(['ends_on' => '2026-06-14']);
        $this->refuses(fn () => app(FeeDiscountRuns::class)->approve($this->admin, $run->id, $this->operation(1)), 422);
    }

    public function test_explicit_overlap_only_reduces_remaining_charges_and_fixed_overcredit_is_refused(): void
    {
        $item = $this->item(mode: 'no_vat', rate: '0', scope: 'learner');
        $this->postItem($item, $this->schoolA->ids['asha']);
        $this->apply($this->rule($item, ['amount_kind' => 'percent', 'amount_value' => 5000]));
        $next = $this->apply($this->rule($item, ['amount_kind' => 'percent', 'amount_value' => 5000]));
        $this->assertSame(250, $next->amount_cents);
        $this->refuses(fn () => $this->prepare($this->rule($item, ['overlap' => 'exclusive'])), 422);
        $this->refuses(fn () => $this->prepare($this->rule($item, ['amount_kind' => 'fixed', 'amount_value' => 1000])), 422);
        $this->healthy();
    }

    public function test_independent_approval_cannot_be_self_approved_or_bypassed_at_sql(): void
    {
        $item = $this->item(mode: 'no_vat', rate: '0', scope: 'learner');
        $this->postItem($item, $this->schoolA->ids['asha']);
        $rule = app(FeeDiscounts::class)->save($this->admin, $this->ruleInput($item, ['independent_approval' => true]));
        $this->refuses(fn () => app(FeeDiscounts::class)->transition($this->admin, $rule->id, $this->operation($rule->revision) + ['action' => 'approve']), 403);
        try {
            $rule->update(['state' => 'active', 'revision' => 2, 'approved_by' => $this->admin->id]);
            $this->fail('SQL must refuse self approval');
        } catch (QueryException) {
            $this->assertSame('draft', $rule->fresh()->state);
        }
    }

    public function test_rule_change_and_price_allocation_change_invalidates_pending_application(): void
    {
        $item = $this->item(mode: 'no_vat', rate: '0', scope: 'learner');
        $this->postItem($item, $this->schoolA->ids['asha']);
        $rule = $this->rule($item);
        $run = $this->prepare($rule);
        $line = InvoiceLine::firstOrFail();
        $this->credit($line, '0.01');
        $this->refuses(fn () => app(FeeDiscountRuns::class)->approve($this->admin, $run->id, $this->operation(1)), 409);
        $run = $this->prepare($rule);
        app(FeeDiscounts::class)->transition($this->admin, $rule->id, $this->operation($rule->revision) + ['action' => 'archive']);
        $this->refuses(fn () => app(FeeDiscountRuns::class)->approve($this->admin, $run->id, $this->operation(1)), 422);
    }

    public function test_cross_school_and_wrong_role_and_foreign_learner_are_refused_without_posting(): void
    {
        $item = $this->item(mode: 'no_vat', rate: '0', scope: 'learner');
        $this->postItem($item, $this->schoolA->ids['asha']);
        $rule = $this->rule($item);
        $this->refuses(fn () => app(FeeDiscountRuns::class)->prepare($this->parent(), $this->prepareInput($rule)), 403);
        $this->refuses(fn () => $this->prepare($rule, $this->schoolB->ids['asha']), 404);
        app(CurrentSchool::class)->run(2, function () use ($rule) {
            $this->refuses(fn () => app(FeeDiscountRuns::class)->prepare($this->admin, $this->prepareInput($rule)), 403);
        });
        $this->assertSame(0, FeeCreditNote::count());
    }

    public function test_late_failure_rolls_back_all_child_credits_audit_and_retries_safely(): void
    {
        $this->policy();
        $item = $this->item(scope: 'learner');
        $this->postItem($item, $this->schoolA->ids['asha']);
        $run = $this->prepare($this->rule($item));
        $run = app(FeeDiscountRuns::class)->approve($this->admin, $run->id, $this->operation(1));
        FeeDiscountRun::updating(function (FeeDiscountRun $row): void {
            if ($row->state === 'posted') {
                throw new \RuntimeException('Synthetic late discount seal failure');
            }
        });
        try {
            app(FeeDiscountRuns::class)->post($this->admin, $run->id, $this->operation(2));
            $this->fail('Expected synthetic failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Synthetic late discount seal failure', $e->getMessage());
        } finally {
            FeeDiscountRun::flushEventListeners();
        }
        $this->assertSame(0, FeeCreditNote::count());
        $this->assertSame('approved', $run->fresh()->state);
        $this->assertSame(1, DB::table('ledger_entries')->count());
        $this->applyPrepared($run);
        $this->healthy();
    }

    public function test_posted_rule_credits_are_private_immutable_and_disabled_history_survives(): void
    {
        $item = $this->item(mode: 'no_vat', rate: '0', scope: 'learner');
        $this->postItem($item, $this->schoolA->ids['asha']);
        $run = $this->apply($this->rule($item));
        $statement = app(ParentFeeStatements::class)->show($this->parent(), $this->account->id, perPage: 50);
        $this->assertStringNotContainsString('PRIVATE-BURSARY-EVIDENCE', json_encode($statement, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('PRIVATE-BURSARY-EVIDENCE', DB::table('fee_discount_runs')->value('private_explanation'));
        try {
            DB::table('fee_discount_runs')->where('id', $run->id)->update(['amount_cents' => 1]);
            $this->fail('Posted discounts must be immutable');
        } catch (QueryException) {
            $this->assertSame(100, $run->fresh()->amount_cents);
        }
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->refuses(fn () => $this->prepare(FeeDiscount::firstOrFail()), 403);
        $this->assertSame('posted', $run->fresh()->state);
        $this->healthy();
    }

    public function test_early_settlement_counts_only_receipts_allocated_and_received_by_deadline(): void
    {
        $item = $this->item(mode: 'no_vat', rate: '0', scope: 'learner');
        $this->postItem($item, $this->schoolA->ids['asha']);
        app(FeePaymentSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'allocation_order' => 'oldest_first']);
        $rule = $this->rule($item, ['kind' => 'early_settlement', 'minimum_paid_bp' => 5000, 'payment_deadline' => '2026-06-10']);
        $this->refuses(fn () => $this->prepare($rule), 422);
        $payment = app(FeePayments::class)->record($this->admin, ['operation_id' => (string) Str::uuid(), 'fee_account_id' => $this->account->id, 'holder_user_id' => $this->parent()->id, 'pinned_learner_id' => $this->schoolA->ids['asha'], 'amount' => '5.01', 'method' => 'cash', 'received_on' => '2026-06-09', 'source_reference' => 'SYNTHETIC-EARLY', 'confirmed' => true]);
        $this->refuses(fn () => $this->prepare($rule), 422);
        $preview = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        app(FeeAllocations::class)->post($this->admin, $payment->id, ['operation_id' => (string) Str::uuid(), 'fingerprint' => $preview['fingerprint'], 'confirmed' => true]);
        $run = $this->prepare($rule);
        $this->assertSame(501, $run->snapshot['evidence']['paid_cents']);
        $late = $this->rule($item, ['kind' => 'early_settlement', 'minimum_paid_bp' => 5000, 'payment_deadline' => '2026-06-08']);
        $this->refuses(fn () => $this->prepare($late), 422);
        $this->healthy();
    }

    public function test_another_explicit_finance_approver_can_approve_and_revoked_posting_authority_is_refused(): void
    {
        $item = $this->item(mode: 'no_vat', rate: '0', scope: 'learner');
        $this->postItem($item, $this->schoolA->ids['asha']);
        $second = User::findOrFail($this->schoolA->ids['tara_user']);
        $second->givePermissionTo('fee-credits-manage');
        $rule = app(FeeDiscounts::class)->save($this->admin, $this->ruleInput($item, ['independent_approval' => true]));
        $rule = app(FeeDiscounts::class)->transition($second, $rule->id, $this->operation(1) + ['action' => 'approve']);
        $run = $this->prepare($rule);
        $this->refuses(fn () => app(FeeDiscountRuns::class)->approve($this->admin, $run->id, $this->operation(1)), 403);
        $run = app(FeeDiscountRuns::class)->approve($second, $run->id, $this->operation(1));
        $second->revokePermissionTo('fee-credits-manage');
        $this->refuses(fn () => $this->applyPrepared($run), 403);
        $this->assertSame(0, FeeCreditNote::count());
        $second->givePermissionTo('fee-credits-manage');
        $this->assertSame('posted', $this->applyPrepared($run)->state);
        $this->healthy();
    }

    public function test_rule_and_application_panel_binding_validation_and_private_history(): void
    {
        $item = $this->item(mode: 'no_vat', rate: '0', scope: 'learner');
        $this->postItem($item, $this->schoolA->ids['asha']);
        $input = $this->ruleInput($item);
        unset($input['amount_value']);
        $input += ['discount_value' => '1.00', 'expected_school_id' => 1, 'expected_account_id' => $this->admin->id];
        $this->actingAs($this->admin)->get('/fee-discounts/new')->assertOk()->assertSee('Separate Finance approval')->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/fee-discounts/new', $input)->assertStatus(303);
        $rule = FeeDiscount::firstOrFail();
        $this->assertSame(100, $rule->amount_value);
        $this->post('/fee-discounts/'.$rule->id.'/save', array_replace($input, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'expected_school_id' => 2]))->assertForbidden();
        $this->post('/fee-discounts/new', array_replace($input, ['operation_id' => (string) Str::uuid(), 'discount_value' => 'invalid PRIVATE-BURSARY-EVIDENCE', 'amount_value' => 500]))->assertStatus(422)->assertSessionMissing('_old_input');
        $this->actingAs($this->parent())->get('/fee-discounts')->assertRedirect()->assertSessionHasErrors();
    }

    public function test_real_other_school_rule_is_not_readable_or_applicable(): void
    {
        $foreign = app(CurrentSchool::class)->run(2, function (): FeeDiscount {
            return FeeDiscount::create(['session_year_id' => $this->schoolB->ids['session_year'], 'name' => 'Baobab private benefit', 'kind' => 'bursary', 'source_kind' => 'annual_tuition', 'learner_id' => $this->schoolB->ids['asha'], 'fee_plan_id' => null, 'fee_item_id' => null, 'currency' => 'ZAR', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'amount_kind' => 'fixed', 'amount_value' => 100, 'sibling_position' => null, 'minimum_paid_bp' => null, 'payment_deadline' => null, 'staff_roles' => null, 'overlap' => 'remaining', 'independent_approval' => false, 'state' => 'active', 'revision' => 1, 'prepared_by' => $this->schoolB->ids['tara_user'], 'approved_by' => $this->schoolB->ids['tara_user']]);
        });
        $this->refuses(fn () => $this->prepare($foreign), 404);
        $this->actingAs($this->admin)->get('/fee-discounts/'.$foreign->id.'/edit')->assertNotFound()->assertDontSee('Baobab private benefit');
    }

    public function test_raw_sql_cannot_seal_a_review_with_other_learners_credits_or_delete_its_rule(): void
    {
        $item = $this->item(mode: 'no_vat', rate: '0', scope: 'learner');
        $this->postItem($item, $this->schoolA->ids['asha']);
        $this->postItem($item, $this->schoolA->ids['cara']);
        $rule = $this->rule($item);
        $run = $this->prepare($rule);
        $run = app(FeeDiscountRuns::class)->approve($this->admin, $run->id, $this->operation(1));
        $line = InvoiceLine::where('learner_id', $this->schoolA->ids['cara'])->firstOrFail();
        $note = $this->credit($line, '1.00');
        try {
            DB::table('fee_discount_runs')->where('id', $run->id)->update(['state' => 'posted', 'revision' => 3, 'sealed_at' => now('UTC'), 'credit_note_ids' => json_encode([$note->id], JSON_THROW_ON_ERROR)]);
            $this->fail('Other learner credits cannot seal this review');
        } catch (QueryException) {
            $this->assertSame('approved', $run->fresh()->state);
        }
        try {
            DB::table('fee_discounts')->where('id', $rule->id)->delete();
            $this->fail('Retain benefit rules');
        } catch (QueryException) {
            $this->assertNotNull($rule->fresh());
        }
        $this->healthy();
    }

    public function test_required_school_choices_and_current_staff_roles_are_not_guessed(): void
    {
        $item = $this->item(mode: 'no_vat', rate: '0');
        $input = $this->ruleInput($item);
        unset($input['independent_approval']);
        $this->refuses(fn () => app(FeeDiscounts::class)->save($this->admin, $input), 422);
        $this->refuses(fn () => app(FeeDiscounts::class)->save($this->admin, $this->ruleInput($item, ['kind' => 'staff', 'staff_roles' => ['Parent']])), 422);
        $this->refuses(fn () => app(FeeDiscounts::class)->save($this->admin, $this->ruleInput($item, ['kind' => 'staff', 'staff_roles' => ['Baobab foreign role']])), 422);
        $this->refuses(fn () => app(FeeDiscounts::class)->save($this->admin, $this->ruleInput($item, ['kind' => 'staff'])), 422);
        $this->assertSame(0, FeeDiscount::count());
    }

    public function test_approved_rules_remain_readable_when_disabled_before_first_financial_post(): void
    {
        $item = $this->item(mode: 'no_vat', rate: '0');
        $this->rule($item);
        $this->assertSame(0, DB::table('ledger_entries')->count());
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->actingAs($this->admin)->get('/fee-discounts')->assertOk()->assertSee('Discount history is read-only while fees are disabled.');
        $this->refuses(fn () => app(FeeDiscounts::class)->save($this->admin, $this->ruleInput($item)), 403);
    }

    private function operation(int $revision): array
    {
        return ['operation_id' => (string) Str::uuid(), 'revision' => $revision, 'confirmed' => true];
    }

    private function ruleInput(FeeItem $item, array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'name' => 'Private Finance reduction', 'kind' => 'bursary', 'source_kind' => 'fee_item', 'learner_id' => $this->schoolA->ids['asha'], 'fee_plan_id' => null, 'fee_item_id' => $item->id, 'currency' => 'ZAR', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'amount_kind' => 'fixed', 'amount_value' => 100, 'sibling_position' => null, 'minimum_paid_bp' => null, 'payment_deadline' => null, 'overlap' => 'remaining', 'independent_approval' => false], $changes);
    }

    private function rule(FeeItem $item, array $changes = []): FeeDiscount
    {
        $rule = app(FeeDiscounts::class)->save($this->admin, $this->ruleInput($item, $changes));

        return app(FeeDiscounts::class)->transition($this->admin, $rule->id, $this->operation($rule->revision) + ['action' => 'approve']);
    }

    private function prepareInput(FeeDiscount $rule, ?int $learner = null): array
    {
        return ['operation_id' => (string) Str::uuid(), 'fee_discount_id' => $rule->id, 'fee_account_id' => $this->account->id, 'learner_id' => $learner ?? $this->schoolA->ids['asha'], 'private_explanation' => 'PRIVATE-BURSARY-EVIDENCE'];
    }

    private function prepare(FeeDiscount $rule, ?int $learner = null): FeeDiscountRun
    {
        return app(FeeDiscountRuns::class)->prepare($this->admin, $this->prepareInput($rule, $learner));
    }

    private function apply(FeeDiscount $rule, ?int $learner = null): FeeDiscountRun
    {
        $run = $this->prepare($rule, $learner);
        $run = app(FeeDiscountRuns::class)->approve($this->admin, $run->id, $this->operation(1));

        return $this->applyPrepared($run);
    }

    private function applyPrepared(FeeDiscountRun $run): FeeDiscountRun
    {
        return app(FeeDiscountRuns::class)->post($this->admin, $run->id, $this->operation($run->revision));
    }

    private function policyInput(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'enabled' => true, 'country_code' => 'ZA', 'tax_registration' => 'VAT-SYNTHETIC-01', 'seller_name' => 'Synthetic registered seller', 'seller_address' => '1 Synthetic School Road'], $changes);
    }

    private function policy(array $changes = []): FeeTaxSetting
    {
        return app(FeeTaxSettings::class)->save($this->admin, $this->policyInput($changes));
    }

    private function item(string $mode = 'vat_inclusive', string $rate = '15.00', string $price = '10.01', string $scope = 'family'): FeeItem
    {
        return app(FeeItems::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic taxed item', 'scope' => $scope, 'amount' => $price, 'currency' => 'ZAR', 'tax_treatment' => $mode, 'vat_rate' => $rate, 'state' => 'active']);
    }

    private function input(FeeItem $item, ?int $learner = null): array
    {
        return ['operation_id' => (string) Str::uuid(), 'fee_item_id' => $item->id, 'fee_account_id' => $this->account->id, 'session_year_id' => $this->year, 'learner_id' => $learner, 'charged_on' => '2026-06-15', 'due_on' => '2026-07-01', 'source_reference' => 'VAT-'.Str::uuid()];
    }

    private function postItem(FeeItem $item, ?int $learner = null): FeeCharge
    {
        $input = $this->input($item, $learner);
        $preview = app(FeeCharges::class)->preview($this->admin, $input);

        $result = app(FeeCharges::class)->post($this->admin, $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true]);
        $this->account->refresh();

        return $result;
    }

    private function credit(InvoiceLine $line, string $amount): FeeCreditNote
    {
        $input = ['operation_id' => (string) Str::uuid(), 'invoice_line_id' => $line->id, 'holder_user_id' => $this->parent()->id, 'amount' => $amount, 'reason_code' => 'billing_error', 'reason' => 'Synthetic VAT correction'];
        $review = app(FeeCreditNotes::class)->preview($this->admin, $input);

        return app(FeeCreditNotes::class)->post($this->admin, $input + ['fingerprint' => $review['fingerprint'], 'confirmed' => true]);
    }

    private function parent(): User
    {
        return User::findOrFail($this->schoolA->ids['farid_user']);
    }

    private function balance(string $type): int
    {
        return (int) DB::table('ledger_accounts')->where('school_id', 1)->where('type', $type)->sum('balance_cents');
    }

    private function healthy(): void
    {
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    private function family(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic split family', 'currency' => 'ZAR', 'session_year_id' => $this->year,
            'learner_ids' => [$this->schoolA->ids['asha'], $this->schoolA->ids['cara']], 'holders' => [['user_id' => $this->schoolA->ids['farid_user'], 'share_percent' => '50'], ['user_id' => $this->schoolA->ids['mona_user'], 'share_percent' => '50']]], $changes);
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
