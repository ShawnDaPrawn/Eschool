<?php

declare(strict_types=1);

namespace Tests\Feature\Consultations;

use App\Models\Consultations\Participation;
use App\Models\Consultations\Response;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Consultations\Consultations;
use App\Services\ParentPolicyService;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class ConsultationStaffAudienceTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        Queue::fake();
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        app(ModuleGate::class)->change('consultations', true, $this->superAdmin());
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->schoolA->ids['session_year'],
            'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic electorate']);
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->schoolA->ids['farid_user']], ['status' => 'active', 'relationship' => 'father']);
    }

    private function consultation(bool $staff, bool $parents = false): int
    {
        $service = app(Consultations::class);
        $receipt = $service->save($this->superAdmin(), null, ['operation_id' => (string) Str::uuid(), 'revision' => 0,
            'session_year_id' => $this->schoolA->ids['session_year'], 'type' => 'election', 'title' => 'Synthetic bounded electorate', 'instructions' => '',
            'anonymous' => true, 'results_visibility' => 'public_live', 'opens_at' => now()->subHour()->toISOString(), 'closes_at' => now()->addDay()->toISOString(),
            'audience' => ['kind' => 'school', 'parents' => $parents, 'include_staff' => $staff],
            'questions' => [['kind' => 'single', 'label' => 'Synthetic candidate choice', 'required' => true, 'options' => [['label' => 'Candidate A'], ['label' => 'Candidate B']]]]]);
        $service->publish($this->superAdmin(), $receipt['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 1]);

        return $receipt['id'];
    }

    private function api(User $actor): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($actor->createToken('synthetic-consultation-audience')->plainTextToken)->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $actor->id]);
    }

    private function answer(int $id): array
    {
        $question = app(Consultations::class)->configuration($this->superAdmin(), $id)['questions'][0];

        return ['answers' => [['question_id' => $question['id'], 'option_ids' => [$question['options'][0]['id']]]]];
    }

    public function test_unchecked_school_staff_cannot_read_cast_or_change_election_totals(): void
    {
        $id = $this->consultation(false);
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $answer = $this->answer($id);
        $read = $this->api($teacher)->getJson("/api/v2/consultations/$id");
        $posted = $this->api($teacher)->postJson("/api/v2/consultations/$id/vote", $answer);
        $this->assertSame(404, $posted->status(), 'Unchecked staff recorded '.Participation::count().' ballot(s).');
        $read->assertNotFound();
        $this->api($this->superAdmin())->postJson("/api/v2/consultations/$id/vote", $answer)->assertNotFound();
        $this->api($teacher)->getJson("/api/v2/consultations/$id/results")->assertNotFound();
        $this->api($teacher)->getJson('/api/v2/consultations')->assertOk()->assertJsonPath('data.items', []);
        $this->assertSame(0, Participation::count());
        $this->assertSame(0, Response::count());
    }

    public function test_explicit_school_staff_optin_remains_a_valid_voter(): void
    {
        $id = $this->consultation(true);
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->api($teacher)->getJson("/api/v2/consultations/$id")->assertOk();
        $this->api($teacher)->postJson("/api/v2/consultations/$id/vote", $this->answer($id))->assertOk();
        $this->assertSame(1, Participation::count());
    }

    public function test_parent_audience_staff_optin_adds_staff_without_adding_unrelated_learner_votes(): void
    {
        $id = $this->consultation(true, true);
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        ParentPolicyService::acceptCurrent($parent);
        $this->api($teacher)->postJson("/api/v2/consultations/$id/vote", $this->answer($id))->assertOk();
        $this->api($parent)->postJson("/api/v2/consultations/$id/vote", $this->answer($id))->assertOk();
        $learner = User::findOrFail($this->schoolA->ids['asha_user']);
        $this->api($learner)->postJson("/api/v2/consultations/$id/vote", $this->answer($id))->assertNotFound();
        $this->assertSame(2, Participation::count());
    }

    public function test_a_dual_role_teacher_retains_own_student_electorate_without_staff_optin(): void
    {
        $learner = User::findOrFail($this->schoolA->ids['asha_user']);
        $learner->assignRole('Teacher');
        ParentPolicyService::acceptCurrent($learner);
        $id = $this->consultation(false);
        $this->api($learner)->postJson("/api/v2/consultations/$id/vote", $this->answer($id))->assertOk();
        $this->assertSame((int) $learner->id, (int) Participation::sole()->user_id);
    }

    public function test_a_dual_role_teacher_retains_fresh_guardian_electorate_without_staff_optin(): void
    {
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $parent->assignRole('Teacher');
        ParentPolicyService::acceptCurrent($parent);
        $id = $this->consultation(false, true);
        $this->api($parent)->getJson("/api/v2/consultations/$id")->assertOk();
        GuardianLink::where('guardian_user_id', $parent->id)->update(['status' => 'ended']);
        $this->api($parent)->postJson("/api/v2/consultations/$id/vote", $this->answer($id))->assertNotFound();
        $this->assertSame(0, Participation::count());
    }
}
