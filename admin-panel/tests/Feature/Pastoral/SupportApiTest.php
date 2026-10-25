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
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class SupportApiTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

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
        $this->appointment = app(SupportPlans::class)->assign($this->superAdmin(), ['operation_id' => (string) Str::uuid(), 'learner_id' => $this->learner, 'user_id' => $this->professional->id, 'starts_on' => '2026-02-10', 'ends_on' => '2026-12-31', 'reason' => 'Private appointment rationale']);
    }

    private function body(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'learner_id' => $this->learner, 'started_on' => '2026-02-01', 'review_due_on' => '2026-03-01', 'sias_stage' => 'SNA1', 'needs' => 'Private assessed support needs', 'accommodations' => 'Private full accommodation plan'];
    }

    private function plan(): SupportPlan
    {
        return app(SupportPlans::class)->save($this->professional, $this->body());
    }

    private const BASE = '/api/v2/pastoral/support/';

    public function test_professional_principal_and_named_teacher_complete_the_private_workflow(): void
    {
        $u = self::BASE;
        $this->actingAs($this->professional)->getJson($u.'access')->assertOk()->assertJsonPath('data.audiences.0', 'professional')->assertJsonPath('data.can_write', true);
        $this->actingAs($this->superAdmin())->getJson($u.'configuration/principal')->assertOk()->assertJsonPath('data.audience', 'principal')->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson($u.'assignments')->assertOk()->assertDontSee('Private appointment');
        $this->actingAs($this->professional)->getJson($u.'configuration/professional')->assertOk()->assertJsonPath('data.learners.0.id', $this->learner);
        $this->getJson($u.'cases')->assertOk()->assertJsonCount(0, 'data.items');
        $input = $this->body();
        $id = $this->postJson($u.'plans', $input)->assertOk()->assertJsonPath('data.kind', 'plan')->assertJsonPath('data.revision', 1)->assertHeader('Cache-Control', 'no-store, private')->json('data.id');
        $this->postJson($u.'plans', $input)->assertOk()->assertJsonPath('data.id', $id);
        $this->getJson($u.'plans/'.$id)->assertOk()->assertJsonPath('data.needs', 'Private assessed support needs');
        $update = $this->body();
        $update['revision'] = 1;
        $update['sias_stage'] = 'SNA2';
        $this->putJson($u.'plans/'.$id, $update)->assertOk()->assertJsonPath('data.revision', 2);
        $this->postJson($u.'plans/'.$id.'/reviews', ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'reviewed_on' => '2026-02-10', 'next_review_on' => '2026-03-10', 'outcome' => 'Private actual review'])->assertOk()->assertJsonPath('data.revision', 3);
        $this->getJson($u.'plans/'.$id.'/reviews')->assertOk()->assertJsonPath('data.items.0.outcome', 'Private actual review');
        $this->getJson($u.'plans/'.$id.'/versions')->assertOk()->assertJsonCount(3, 'data.items');
        $disclosure = $this->postJson($u.'plans/'.$id.'/disclosures', ['operation_id' => (string) Str::uuid(), 'revision' => 3, 'user_id' => $this->schoolA->ids['theo_user'], 'expires_on' => '2026-03-10', 'accommodations' => 'Approved quiet seating', 'confirmation' => true, 'reason' => 'Limited instructions'])->assertOk()->json('data.id');
        $this->getJson($u.'plans/'.$id.'/disclosures')->assertOk()->assertJsonPath('data.items.0.id', $disclosure);
        $teacher = User::findOrFail($this->schoolA->ids['theo_user']);
        $this->actingAs($teacher)->getJson($u.'teacher')->assertOk()->assertJsonPath('data.items.0.accommodations', 'Approved quiet seating')->assertDontSee('Private');
        $this->getJson($u.'teacher/'.$disclosure)->assertOk()->assertJsonPath('data.learner_id', $this->learner)->assertDontSee('SNA2')->assertDontSee('Private');
        $this->getJson($u.'plans/'.$id)->assertForbidden()->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($this->professional)->postJson($u.'disclosures/'.$disclosure.'/revoke', ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'reason' => 'Instruction withdrawn'])->assertOk();
        $this->actingAs($teacher)->getJson($u.'teacher/'.$disclosure)->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($this->professional)->postJson($u.'plans/'.$id.'/close', ['operation_id' => (string) Str::uuid(), 'revision' => 3, 'reason' => 'Support concluded'])->assertOk()->assertJsonPath('data.revision', 4);
        $this->getJson($u.'plans/'.$id)->assertOk()->assertJsonPath('data.state', 'closed');
        $this->actingAs($this->superAdmin())->postJson($u.'assignments/'.$this->appointment->id.'/revoke', ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'reason' => 'Assignment concluded'])->assertOk();
        $this->actingAs($this->professional)->getJson($u.'plans/'.$id)->assertNotFound();
    }

    public function test_principal_appoints_named_professional_but_cannot_open_a_plan_body(): void
    {
        $other = User::findOrFail($this->schoolA->ids['theo_user']);
        $other->syncRoles(['Safeguarding']);
        $this->actingAs($this->superAdmin())->postJson(self::BASE.'assignments', ['operation_id' => (string) Str::uuid(), 'learner_id' => $this->learner, 'user_id' => $other->id, 'starts_on' => '2026-02-10', 'ends_on' => '2026-03-10', 'reason' => 'Private appointment rationale'])->assertOk()->assertJsonPath('data.kind', 'assignment')->assertDontSee('Private');
        $plan = $this->plan();
        $this->getJson(self::BASE.'plans/'.$plan->id)->assertForbidden();
        $this->actingAs($other)->getJson(self::BASE.'plans/'.$plan->id)->assertOk();
    }

    public function test_fresh_scope_precedes_invalid_inputs_wrong_school_and_completed_replay(): void
    {
        $input = $this->body();
        $id = $this->actingAs($this->professional)->postJson(self::BASE.'plans', $input)->assertOk()->json('data.id');
        $foreign = app(CurrentSchool::class)->run(2, function () {
            $actor = User::findOrFail($this->schoolB->ids['tara_user']);
            $actor->syncRoles(['Counsellor']);

            return $actor;
        });
        $this->actingAs($foreign)->withHeader('X-School-Id', '2')->getJson(self::BASE.'plans/'.$id)->assertNotFound()->assertDontSee('Private');
        $this->actingAs(User::findOrFail($this->schoolA->ids['theo_user']))->withHeader('X-School-Id', '1')->postJson(self::BASE.'plans', [])->assertForbidden();
        $this->actingAs($this->professional)->postJson(self::BASE.'plans', ['needs' => 'Private unsent draft'])->assertUnprocessable()->assertHeader('Cache-Control', 'no-store, private')->assertDontSee('Private unsent draft')->assertSessionMissing('_old_input.needs');
        $this->professional->syncRoles(['Teacher']);
        $this->postJson(self::BASE.'plans', $input)->assertForbidden();
        $this->professional->syncRoles(['Counsellor']);
        config(['modules.support_plans' => ['default' => false]]);
        $this->postJson(self::BASE.'plans', $input)->assertNotFound();
        $this->assertSame(1, SupportPlan::count());
    }

    public function test_empty_search_is_a_valid_unfiltered_configuration_request(): void
    {
        $this->actingAs($this->professional)->getJson(self::BASE.'configuration/professional?search=&page=1')->assertOk()->assertJsonPath('data.learners.0.id', $this->learner);
    }

    public function test_teacher_capabilities_are_read_only_even_when_the_module_is_writable(): void
    {
        $this->actingAs(User::findOrFail($this->schoolA->ids['theo_user']))->getJson(self::BASE.'access')->assertOk()->assertJsonPath('data.audiences', ['teacher'])->assertJsonPath('data.can_write', false);
    }

    public function test_selected_choices_are_revalidated_independently_of_search_and_paging(): void
    {
        $path = self::BASE.'configuration/principal';
        $this->actingAs($this->superAdmin())->getJson($path.'?search=NoSuchName&page=2')->assertOk()->assertJsonCount(0, 'data.learners')->assertJsonCount(0, 'data.staff');
        $selected = $path.'?learner_id='.$this->learner.'&user_id='.$this->professional->id;
        $this->getJson($selected)->assertOk()->assertJsonPath('data.learners.0.id', $this->learner)->assertJsonPath('data.staff.0.id', $this->professional->id);
        $this->getJson($path.'?learner_id='.$this->schoolB->ids['asha'].'&user_id='.$this->schoolB->ids['tara_user'])->assertOk()->assertJsonCount(0, 'data.learners')->assertJsonCount(0, 'data.staff');
        SchoolMembership::where('school_id', 1)->where('user_id', $this->professional->id)->update(['ends_on' => '2026-02-09']);
        $this->getJson($selected)->assertOk()->assertJsonCount(0, 'data.staff');
    }

    public function test_private_writes_refuse_query_parameters_even_with_an_otherwise_valid_body(): void
    {
        $this->actingAs($this->professional)->postJson(self::BASE.'plans?needs=PrivateQueryDraft', $this->body())->assertUnprocessable()->assertDontSee('PrivateQueryDraft');
        $this->assertSame(0, SupportPlan::count());
    }
}
