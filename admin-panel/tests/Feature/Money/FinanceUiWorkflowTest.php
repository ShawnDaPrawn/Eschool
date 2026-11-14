<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\FeeDiscount;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformInvoiceLine;
use App\Models\Platform\PlatformPayment;
use App\Models\Platform\PlatformPaymentAllocation;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\Platform\Subscriptions\LearnerSubscription;
use App\Models\SchoolBusiness\BursaryApplication;
use App\Models\SchoolBusiness\GivingDonation;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\FeePlans;
use App\Services\Money\SchoolSubscriptionInvoices;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\SchoolBusiness\Giving;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class FinanceUiWorkflowTest extends TestCase
{
    use UsesTwoSchools;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDateTime(2026, 6, 15, 12, 0, 0));
        $this->setUpTwoSchools();
        SchoolMembership::where('school_id', 1)->update(['starts_on' => '2026-01-01']);
        $this->admin = $this->superAdmin();
        foreach (['giving', 'bursaries'] as $module) {
            SchoolModule::updateOrCreate(['school_id' => 1, 'module' => $module], ['enabled' => true, 'read_only' => false, 'in_package' => true]);
        }
    }

    private function binding(): array
    {
        return ['expected_school_id' => 1, 'expected_account_id' => $this->admin->id, 'operation_id' => (string) Str::uuid()];
    }

    public function test_giving_browser_workflow_keeps_pledges_separate_and_posts_exactly_once(): void
    {
        $this->actingAs($this->admin)->get('/finance/giving?section=create')->assertOk()->assertSee('Create giving campaign');
        $created = $this->postJson('/finance/giving', $this->binding() + ['name' => 'Science lab', 'currency' => 'ZAR', 'goal' => '10000.01', 'starts_on' => '2026-06-01', 'ends_on' => '2026-12-31', 'public_totals' => true])->assertOk();
        $id = $created->json('data.id');
        $this->get('/finance/giving/'.$id.'?section=pledge')->assertOk()->assertSee('Record pledge');
        $pledge = $this->postJson('/finance/giving/'.$id.'/pledge', $this->binding() + ['donor_name' => 'Private Alumna', 'amount' => '2000.01', 'pledged_on' => '2026-06-14'])->assertOk()->json('data.id');
        $this->assertSame(0, GivingDonation::count());
        $input = $this->binding() + ['donor_name' => 'Private Alumna', 'donor_email' => 'private@example.test', 'amount' => '1234.57', 'received_on' => '2026-06-14', 'method' => 'eft', 'source_reference' => ' ui-gift-001 ', 'pledge_id' => $pledge, 'confirmed' => true];
        $response = $this->postJson('/finance/giving/'.$id.'/donate', $input)->assertOk();
        $this->postJson('/finance/giving/'.$id.'/donate', $input)->assertOk()->assertJsonPath('data.id', $response->json('data.id'));
        $this->assertSame(1, GivingDonation::count());
        $this->assertSame(123457, GivingDonation::firstOrFail()->amount_cents);
        $this->get($response->json('redirect'))->assertOk()->assertSee('Private Alumna')->assertSee('UI-GIFT-001')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/finance/giving/'.$id.'?section=donors')->assertOk()->assertSee('Private Alumna');
        $this->postJson('/finance/giving/'.$id.'/close', $this->binding() + ['revision' => 1])->assertOk()->assertJsonPath('data.state', 'closed');
        $this->postJson('/finance/giving/'.$id.'/donate', array_replace($input, ['operation_id' => (string) Str::uuid(), 'source_reference' => 'UI-GIFT-002']))->assertStatus(409);
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
    }

    public function test_giving_adapter_enforces_donor_authority_context_and_private_validation(): void
    {
        $campaign = app(Giving::class)->create($this->admin, ['operation_id' => (string) Str::uuid(), 'name' => 'Private campaign', 'currency' => 'ZAR', 'starts_on' => '2026-06-01', 'ends_on' => '2026-12-31', 'public_totals' => false]);
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->actingAs($teacher)->get('/finance/giving')->assertForbidden();
        $teacher->givePermissionTo(Giving::CAMPAIGNS);
        $this->get('/finance/giving/'.$campaign['id'])->assertOk()->assertDontSee('name="donor_name"', false);
        $this->get('/finance/giving/'.$campaign['id'].'?section=donors')->assertForbidden();
        $this->actingAs($this->admin)->postJson('/finance/giving/'.$campaign['id'].'/pledge', array_replace($this->binding(), ['expected_school_id' => 2]) + ['donor_name' => 'Private Alumna', 'amount' => '1.00', 'pledged_on' => '2026-06-14'])->assertForbidden();
        $this->postJson('/finance/giving/'.$campaign['id'].'/pledge', $this->binding() + ['donor_name' => 'Private Alumna', 'amount' => '1.001', 'pledged_on' => '2026-06-14'])->assertUnprocessable()->assertSessionMissing('_old_input');
        $foreign = app(CurrentSchool::class)->run(2, fn () => DB::table('giving_campaigns')->insertGetId(['school_id' => 2, 'name' => 'Baobab private', 'currency' => 'ZAR', 'starts_on' => '2026-06-01', 'ends_on' => '2026-12-31', 'public_totals' => false, 'state' => 'open', 'income_account_id' => app(LedgerService::class)->openAccount(AccountType::GivingIncome, 'giving:foreign:'.Str::uuid(), 'ZAR'), 'revision' => 1, 'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]));
        $this->get('/finance/giving/'.$foreign)->assertNotFound();
    }

    public function test_bursary_review_note_and_decline_have_focused_private_forms(): void
    {
        $application = BursaryApplication::create(['public_id' => (string) Str::uuid(), 'learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->schoolA->ids['farid_user'], 'session_year_id' => $this->schoolA->ids['session_year'], 'state' => 'submitted', 'household' => ['household_size' => 4, 'dependants' => 2, 'monthly_income_cents' => 200001, 'monthly_expenses_cents' => 199999, 'explanation' => 'Private household detail'], 'revision' => 1, 'submitted_at' => now()]);
        $url = '/finance/bursaries/'.$application->public_id;
        $this->actingAs($this->admin)->get('/finance/bursaries')->assertOk()->assertDontSee('Private household detail');
        $this->get($url)->assertOk()->assertSee('Private household detail')->assertDontSee('name="award_amount"', false);
        $this->get($url.'?section=award')->assertOk()->assertSee('name="award_amount"', false);
        $this->get($url.'?section=decline')->assertOk()->assertDontSee('name="award_amount"', false);
        $this->postJson($url.'/note', $this->binding() + ['reasons' => 'Private committee consideration'])->assertOk();
        $this->postJson($url.'/decide', $this->binding() + ['revision' => $application->fresh()->revision, 'outcome' => 'decline', 'reasons' => 'Documented committee decision after reviewing the evidence.'])->assertOk();
        $this->assertSame('declined', $application->fresh()->state);
        $this->get($url.'?section=history')->assertOk()->assertSee('Private committee consideration')->assertSee('Documented committee decision');
        $guardian = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->actingAs($guardian)->get($url)->assertRedirect(route('login'))->assertDontSee('Private household detail');
    }

    public function test_bursary_award_keeps_exact_percentage_in_a_separate_unapproved_discount(): void
    {
        $year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Enrolment::where('session_year_id', $year)->where('learner_id', $this->schoolA->ids['asha'])->delete();
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $year, 'starts_on' => '2026-01-01', 'legacy_dates' => false, 'reason' => 'Synthetic UI award enrolment']);
        $application = BursaryApplication::create(['public_id' => (string) Str::uuid(), 'learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->schoolA->ids['farid_user'], 'session_year_id' => $year, 'state' => 'submitted', 'household' => ['household_size' => 4, 'explanation' => 'Private household'], 'revision' => 1, 'submitted_at' => now()]);
        $url = '/finance/bursaries/'.$application->public_id;
        $input = $this->binding() + ['revision' => 1, 'outcome' => 'award', 'award_kind' => 'percent', 'award_amount' => '25.01', 'currency' => 'ZAR', 'reasons' => 'Committee reviewed the evidence and approved this percentage.'];
        $this->actingAs($this->admin)->postJson($url.'/decide', $input)->assertOk()->assertJsonPath('data.state', 'awarded');
        $this->postJson($url.'/decide', $input)->assertOk();
        $discount = FeeDiscount::findOrFail($application->fresh()->fee_discount_id);
        $this->assertSame(2501, $discount->amount_value);
        $this->assertSame('draft', $discount->state);
        $this->assertTrue($discount->independent_approval);
        $this->assertNull($discount->approved_by);
        $this->assertSame(1, FeeDiscount::where('learner_id', $application->learner_id)->count());
        $this->get($url.'?section=history')->assertOk()->assertSee('25.01%')->assertSee('Review fee discount');
        $foreign = app(CurrentSchool::class)->run(2, fn () => BursaryApplication::create(['public_id' => (string) Str::uuid(), 'learner_id' => $this->schoolB->ids['asha'], 'guardian_user_id' => $this->schoolB->ids['farid_user'], 'session_year_id' => $this->schoolB->ids['session_year'], 'state' => 'submitted', 'household' => ['explanation' => 'Baobab private'], 'revision' => 1, 'submitted_at' => now()]));
        $this->get('/finance/bursaries/'.$foreign->public_id)->assertNotFound();
        $this->postJson('/finance/bursaries/'.$foreign->public_id.'/decide', array_replace($input, ['operation_id' => (string) Str::uuid()]))->assertNotFound();
        $this->postJson($url.'/note', array_replace($this->binding(), ['expected_account_id' => $this->schoolA->ids['farid_user']]) + ['reasons' => 'Wrong account binding'])->assertForbidden();
    }

    public function test_sponsored_place_assignment_is_school_bound_capacity_limited_and_explicitly_released(): void
    {
        $sponsor = LearnerSubscription::create(['reference' => 'UI-000001', 'plan' => LearnerSubscription::SPONSORED, 'sponsor_name' => 'Community sponsor', 'places' => 1, 'status' => LearnerSubscription::REQUESTED, 'price_cents' => 10000, 'currency' => 'ZAR', 'period_months' => 1, 'provider' => 'manual', 'requested_by' => $this->admin->id]);
        $url = '/finance/sponsored-places/'.$sponsor->id;
        $this->actingAs($this->admin)->get('/finance/sponsored-places')->assertOk()->assertSee('Community sponsor');
        $this->get($url.'?section=assign')->assertOk()->assertDontSee('Baobab');
        $this->postJson($url.'/learners', $this->binding() + ['learner_id' => $this->schoolB->ids['asha']])->assertNotFound();
        $this->postJson($url.'/learners', $this->binding() + ['learner_id' => $this->schoolA->ids['asha']])->assertOk();
        $this->postJson($url.'/learners', $this->binding() + ['learner_id' => $this->schoolA->ids['ben']])->assertStatus(409);
        $release = $url.'/learners/'.$this->schoolA->ids['asha'];
        $this->get($release.'/release')->assertOk()->assertSee('Community sponsor');
        $this->postJson($release.'/remove', $this->binding())->assertUnprocessable();
        $this->postJson($release.'/remove', $this->binding() + ['confirmed' => true])->assertOk();
        $this->assertSame(0, DB::table('learner_subscription_members')->where('subscription_id', $sponsor->id)->whereNull('removed_at')->count());
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']))->get($url)->assertForbidden();
    }

    public function test_school_subscription_invoice_read_excludes_drafts_foreign_schools_private_notes_and_writes(): void
    {
        $invoice = $this->invoice('NE-2026-0001');
        $draft = $this->invoice(null);
        $foreign = app(CurrentSchool::class)->run(2, fn () => $this->invoice('NE-2026-0002'));
        $payment = PlatformPayment::create(['received_on' => '2026-06-14', 'amount_cents' => 5000, 'currency' => 'ZAR', 'bank_reference' => 'SCHOOL-EFT-001', 'note' => 'PRIVATE HQ NOTE', 'recorded_by' => $this->admin->id]);
        PlatformPaymentAllocation::create(['invoice_id' => $invoice->id, 'payment_id' => $payment->id, 'amount_cents' => 5000, 'created_by' => $this->admin->id]);
        $this->actingAs($this->admin)->get('/finance/subscription-invoices')->assertOk()->assertSee('NE-2026-0001')->assertDontSee('NE-2026-0002')->assertDontSee('PRIVATE HQ DIAGNOSTIC');
        $url = '/finance/subscription-invoices/'.$invoice->id;
        $this->get($url)->assertOk()->assertSee('Published package line')->assertSee('SCHOOL-EFT-001')->assertSee('Part paid')->assertDontSee('PRIVATE HQ NOTE')->assertDontSee('PRIVATE HQ DIAGNOSTIC')->assertDontSee('/saas/')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(6500, app(SchoolSubscriptionInvoices::class)->show($this->admin, $invoice->id)['outstanding_cents']);
        $this->get('/finance/subscription-invoices/'.$foreign->id)->assertNotFound();
        $this->get('/finance/subscription-invoices/'.$draft->id)->assertNotFound();
        $this->get($url.'?school_id=2')->assertUnprocessable();
        $this->post($url, $this->binding())->assertStatus(405);
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']))->get($url)->assertForbidden();
        $this->actingAs(User::findOrFail($this->schoolA->ids['farid_user']))->get('/finance/subscription-invoices')->assertRedirect(route('login'))->assertDontSee('Private billing note');
        PlatformStaffIdentity::create(['user_id' => $this->admin->id, 'active' => true]);
        $this->actingAs($this->admin)->get($url)->assertForbidden();
    }

    public function test_panel_structured_installment_grid_preserves_exact_basis_points_and_can_clear_rules(): void
    {
        SessionYear::whereKey($this->schoolA->ids['session_year'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $base = ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Structured school tuition', 'session_year_id' => $this->schoolA->ids['session_year'], 'scope' => 'all', 'currency' => 'ZAR', 'amount' => '10.01', 'state' => 'active'];
        $plan = app(FeePlans::class)->save($this->admin, $base + ['applies_to' => [['field' => 'phase', 'values' => ['Foundation']]]]);
        $input = array_replace($base, ['operation_id' => (string) Str::uuid(), 'revision' => 1]) + ['rules_present' => '1', 'options_present' => '1',
            'option_rows' => [['key' => 'terms', 'label' => 'Three installments', 'installments' => [
                ['month' => '1', 'day' => '15', 'percentage' => '33.33'], ['month' => '4', 'day' => '15', 'percentage' => '33.33'], ['month' => '7', 'day' => '15', 'percentage' => '33.34']]]],
            'payment_default_index' => '0', 'expected_school_id' => 1, 'expected_account_id' => $this->admin->id];
        $this->actingAs($this->admin)->get('/fee-plans/'.$plan->id.'/edit')->assertOk()->assertSee('data-payment-options', false)->assertSee('data-fee-rules', false);
        $this->post('/fee-plans/'.$plan->id, $input)->assertStatus(303);
        $saved = $plan->fresh();
        $this->assertSame([], $saved->applies_to);
        $this->assertSame([3333, 3333, 3334], array_column($saved->payment_options[0]['installments'], 'weight_bp'));
        $this->assertSame('terms', $saved->default_payment_option);
        $bad = array_replace($input, ['operation_id' => (string) Str::uuid(), 'revision' => 2]);
        $bad['option_rows'][0]['installments'][0]['percentage'] = '33.333';
        $this->post('/fee-plans/'.$plan->id, $bad)->assertStatus(422)->assertSessionMissing('_old_input');
        $this->assertSame(2, $plan->fresh()->revision);
    }

    private function invoice(?string $reference): PlatformInvoice
    {
        $invoice = PlatformInvoice::create(['kind' => 'invoice', 'status' => 'draft', 'period_start' => '2026-06-01', 'period_end' => '2026-06-30', 'package_key' => 'legacy', 'currency' => 'ZAR', 'vat_rate_bp' => 1500, 'subtotal_cents' => 10000, 'vat_cents' => 1500, 'total_cents' => 11500, 'blocked_reason' => 'PRIVATE HQ DIAGNOSTIC']);
        PlatformInvoiceLine::create(['invoice_id' => $invoice->id, 'position' => 1, 'kind' => 'base', 'description' => 'Published package line', 'learners' => 1, 'unit_cents' => 10000, 'amount_cents' => 10000, 'vat_cents' => 1500]);
        if ($reference !== null) {
            $invoice->update(['status' => 'issued', 'reference' => $reference, 'issued_on' => '2026-06-01', 'due_on' => '2026-06-30', 'issued_at' => now(), 'issued_by' => $this->admin->id]);
        }

        return $invoice;
    }
}
