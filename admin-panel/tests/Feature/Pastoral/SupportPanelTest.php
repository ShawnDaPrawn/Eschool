<?php

declare(strict_types=1);

namespace Tests\Feature\Pastoral;

use App\Models\Pastoral\SupportAssignment;
use App\Models\Pastoral\SupportPlan;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Pastoral\SupportPlans;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class SupportPanelTest extends TestCase
{
    use UsesTwoSchools;

    private User $professional;

    private int $learner;

    private SupportAssignment $appointment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10 10:00:00', 'Africa/Johannesburg'));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        config(['modules.support_plans' => ['always_on' => true]]);
        $this->learner = $this->schoolA->ids['asha'];
        Enrolment::create(['learner_id' => $this->learner, 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->schoolA->ids['session_year'], 'starts_on' => '2026-01-01', 'ends_on' => null, 'legacy_dates' => false]);
        $this->professional = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->professional->syncRoles(['Counsellor']);
        $year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'leadership', ['user_id' => $this->superAdmin()->id, 'session_year_id' => $year, 'kind' => 'principal', 'grade_level_id' => null, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic support principal']);
        $this->appointment = app(SupportPlans::class)->assign($this->superAdmin(), ['operation_id' => (string) Str::uuid(), 'learner_id' => $this->learner, 'user_id' => $this->professional->id, 'starts_on' => '2026-02-10', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic hidden appointment details']);
    }

    private function body(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'learner_id' => $this->learner, 'started_on' => '2026-02-01', 'review_due_on' => '2026-03-01', 'sias_stage' => 'SNA1', 'needs' => 'Private assessed support needs', 'accommodations' => 'Private full accommodation plan'];
    }

    private function plan(): SupportPlan
    {
        return app(SupportPlans::class)->save($this->professional, $this->body());
    }

    private function fields(array $data = []): array
    {
        return ['expected_account_id' => $this->professional->id, 'expected_school_id' => 1] + $data;
    }

    public function test_professional_panel_reviews_versions_and_teacher_projection_remain_separate(): void
    {
        $this->actingAs($this->professional)->get('/support-plans')->assertOk()->assertSee('Start a support plan')->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/support-plans/plans', $this->fields($this->body()))->assertRedirect()->assertHeader('Cache-Control', 'no-store, private');
        $plan = SupportPlan::sole();
        $this->get('/support-plans?audience=professional&plan='.$plan->id)->assertOk()->assertSee('Private assessed support needs')->assertSee('Approve teacher instructions');
        $this->post('/support-plans/plans/'.$plan->id.'/disclosures', $this->fields(['operation_id' => (string) Str::uuid(), 'revision' => 1, 'user_id' => $this->schoolA->ids['theo_user'], 'expires_on' => '2026-03-01', 'accommodations' => 'Approved quiet seating', 'reason' => 'Limited instructions', 'confirmation' => true]))->assertRedirect();
        $this->get('/support-plans?audience=professional&plan='.$plan->id.'&history=versions')->assertOk()->assertSee('Plan versions')->assertSee('Private assessed support needs');
        $this->actingAs(User::findOrFail($this->schoolA->ids['theo_user']))->get('/support-plans?audience=teacher')->assertOk()->assertSee('Approved quiet seating')->assertDontSee('Private assessed')->assertDontSee('SNA1')->assertDontSee('Synthetic hidden appointment details');
        $this->get('/support-plans?audience=professional&plan='.$plan->id)->assertForbidden();
        $this->actingAs($this->superAdmin())->get('/support-plans?audience=principal')->assertOk()->assertSee('Support assignments')->assertDontSee('Private assessed')->assertDontSee('SNA1')->assertDontSee('Synthetic hidden appointment details');
    }

    public function test_private_validation_conflict_and_selected_year_refusals_never_flash_drafts(): void
    {
        $input = $this->fields($this->body());
        $input['review_due_on'] = 'not-a-date';
        $this->actingAs($this->professional)->post('/support-plans/plans', $input)->assertUnprocessable()->assertSee('Private assessed support needs')->assertHeader('Cache-Control', 'no-store, private')->assertSessionMissing('_old_input.needs');
        $plan = $this->plan();
        $input = $this->fields($this->body());
        $input['revision'] = 99;
        $input['needs'] = 'Private unsaved changed draft';
        $this->post('/support-plans/plans/'.$plan->id.'/save', $input)->assertConflict()->assertSee('Private unsaved changed draft')->assertSee('Private assessed support needs')->assertSee('I compared my draft')->assertSessionMissing('_old_input.needs');
        $this->assertSame(1, $plan->fresh()->revision);
        $past = SessionYear::create(['name' => 'Synthetic old year', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        $this->withSession(['session_year' => $past->id])->post('/support-plans/plans', $input)->assertStatus(423)->assertSee('Private unsaved changed draft')->assertHeader('Cache-Control', 'no-store, private')->assertSessionMissing('_old_input.needs');
        $this->assertSame(1, SupportPlan::count());
    }

    public function test_replaced_account_wrong_role_school_and_revoked_caseload_cannot_submit_private_drafts(): void
    {
        $plan = $this->plan();
        $other = User::findOrFail($this->schoolA->ids['theo_user']);
        $other->syncRoles(['Counsellor']);
        app(SupportPlans::class)->assign($this->superAdmin(), ['operation_id' => (string) Str::uuid(), 'learner_id' => $this->learner, 'user_id' => $other->id, 'starts_on' => '2026-02-10', 'ends_on' => '2026-03-01', 'reason' => 'Another named professional']);
        $input = $this->fields($this->body());
        $input['revision'] = 1;
        $this->actingAs($other)->post('/support-plans/plans/'.$plan->id.'/save', $input)->assertForbidden()->assertDontSee('Private assessed');
        $this->assertSame(1, $plan->fresh()->revision);
        $this->actingAs($this->professional)->get('/support-plans?audience=professional&plan='.$plan->id)->assertOk();
        $this->appointment->update(['active' => false]);
        $this->get('/support-plans?audience=professional&plan='.$plan->id)->assertNotFound()->assertDontSee('Private assessed');
        $this->post('/support-plans/plans/'.$plan->id.'/save', $input)->assertNotFound();
    }

    public function test_early_historical_refusal_never_reflects_a_draft_after_account_or_role_revocation(): void
    {
        $plan = $this->plan();
        $input = $this->fields($this->body());
        $input['revision'] = 1;
        $input['needs'] = 'Private draft from former authority';
        $past = SessionYear::create(['name' => 'Synthetic historical header', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        $other = User::findOrFail($this->schoolA->ids['theo_user']);
        $other->syncRoles(['Counsellor']);
        $this->actingAs($other)->withSession(['session_year' => $past->id])->post('/support-plans/plans/'.$plan->id.'/save', $input)->assertForbidden()->assertHeader('Cache-Control', 'no-store, private')->assertDontSee('Private draft from former authority');
        $this->professional->syncRoles(['Teacher']);
        $this->actingAs($this->professional)->post('/support-plans/plans/'.$plan->id.'/save', $input)->assertForbidden()->assertHeader('Cache-Control', 'no-store, private')->assertDontSee('Private draft from former authority');
        $this->assertSame(1, $plan->fresh()->revision);
    }
}
