<?php

declare(strict_types=1);

namespace Tests\Feature\Consultations;

use App\Models\Consultations\Consultation;
use App\Models\Consultations\Option;
use App\Models\Consultations\Participation;
use App\Models\Consultations\Question;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class ConsultationPanelTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        Queue::fake();
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        app(ModuleGate::class)->change('consultations', true, $this->superAdmin());
    }

    private function data(array $extra = []): array
    {
        return $extra + ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'expected_account_id' => $this->superAdmin()->id, 'expected_school_id' => 1,
            'session_year_id' => $this->schoolA->ids['session_year'], 'type' => 'survey', 'title' => 'Synthetic panel consultation', 'instructions' => 'Synthetic plain instructions.',
            'anonymous' => '1', 'results_visibility' => 'after_close', 'opens_at_local' => now()->subHour()->format('Y-m-d\TH:i'), 'closes_at_local' => now()->addDay()->format('Y-m-d\TH:i'),
            'audience' => ['kind' => 'school', 'parents' => '0', 'include_staff' => '1'],
            'questions' => [['kind' => 'single', 'label' => 'Synthetic preference', 'required' => '1', 'options' => [['label' => 'Morning'], ['label' => 'Afternoon']]],
                ['kind' => 'text', 'label' => 'Synthetic suggestion', 'required' => '1']]];
    }

    private function create(): int
    {
        $this->actingAs($this->superAdmin())->post('/school-consultations/new', $this->data())->assertRedirect();

        return (int) Consultation::sole()->id;
    }

    private function action(int $id, string $action, array $extra = []): void
    {
        $this->actingAs($this->superAdmin())->post("/school-consultations/$id/$action", $extra + ['operation_id' => (string) Str::uuid(),
            'revision' => Consultation::findOrFail($id)->revision, 'expected_account_id' => $this->superAdmin()->id, 'expected_school_id' => 1, 'reason' => 'Synthetic retained close'])->assertRedirect();
    }

    public function test_real_authoring_frozen_ballot_results_and_configuration_history_pages(): void
    {
        $this->actingAs($this->superAdmin())->get('/school-consultations/new')->assertOk()->assertSee('data-consultation-editor', false)->assertSee('Save consultation draft')->assertHeader('Cache-Control', 'no-store, private');
        $id = $this->create();
        $this->get("/school-consultations/$id/edit")->assertOk()->assertSee('Synthetic panel consultation');
        $this->action($id, 'publish');
        $this->get("/school-consultations/$id/edit")->assertOk()->assertSee('Results and closed responses')->assertDontSee('Publish frozen consultation');
        $staff = User::findOrFail($this->schoolA->ids['tara_user']);
        $ballot = $this->actingAs($staff)->get("/school-consultations/$id")->assertOk()->assertSee('Cast your response');
        $questions = Question::where('consultation_id', $id)->orderBy('position')->get();
        $this->post("/school-consultations/$id/vote", ['expected_account_id' => $staff->id, 'expected_school_id' => 1, 'answers' => [
            ['question_id' => $questions[0]->id, 'option_ids' => [Option::where('question_id', $questions[0]->id)->firstOrFail()->id]],
            ['question_id' => $questions[1]->id, 'text' => 'Synthetic private survey answer.']]])->assertRedirect("/school-consultations/$id");
        $this->get("/school-consultations/$id")->assertOk()->assertSee('Your response is recorded. You can participate only once.')->assertDontSee('Synthetic private survey answer.');
        $this->get("/school-consultations/$id/results")->assertForbidden();
        $this->actingAs($this->superAdmin())->get("/school-consultations/$id/results?managed=1")->assertForbidden();
        $this->action($id, 'close');
        $this->get("/school-consultations/$id/results?managed=1")->assertOk()->assertSee('Synthetic private survey answer.')->assertSee('Anonymous response')->assertDontSee($staff->full_name);
        $this->actingAs($staff)->get("/school-consultations/$id/results")->assertOk()->assertSee('Responses recorded')->assertDontSee('Synthetic private survey answer.');
        $this->actingAs($this->superAdmin())->get("/school-consultations/$id/history?event=1")->assertOk()->assertSee('Synthetic panel consultation')->assertSee('Synthetic plain instructions.');
    }

    public function test_invalid_private_forms_reflect_only_to_current_author_without_session_flash(): void
    {
        $this->actingAs($this->superAdmin())->post('/school-consultations/new', $this->data(['title' => '<script>synthetic-private-title</script>', 'questions' => []]))
            ->assertUnprocessable()->assertSee('&lt;script&gt;synthetic-private-title&lt;/script&gt;', false)->assertDontSee('<script>synthetic-private-title</script>', false)
            ->assertSessionMissing('_old_input')->assertSessionMissing('errors')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(0, Consultation::count());
        $this->post('/school-consultations/new', $this->data(['expected_account_id' => $this->schoolA->ids['farid_user'], 'title' => 'Synthetic forbidden stale draft']))
            ->assertForbidden()->assertDontSee('Synthetic forbidden stale draft')->assertSessionMissing('_old_input');
        $this->post('/school-consultations/new?title=forbidden', $this->data())->assertUnprocessable()->assertSessionMissing('_old_input');
    }

    public function test_readonly_year_guard_never_flashes_or_reflects_anonymous_answers(): void
    {
        $id = $this->create();
        $this->action($id, 'publish');
        $old = SessionYear::findOrFail($this->schoolA->ids['session_year'])->replicate();
        $old->name = 'Synthetic read-only year';
        $old->default = 0;
        $old->save();
        $this->app->forgetInstance(SessionYearContext::class);
        $this->withSession(['session_year' => $old->id])->post("/school-consultations/$id/vote", ['expected_account_id' => $this->superAdmin()->id, 'expected_school_id' => 1,
            'answers' => [['question_id' => 1, 'text' => 'Synthetic anonymous draft must never flash.']]])
            ->assertStatus(423)->assertDontSee('Synthetic anonymous draft must never flash.')->assertSessionMissing('_old_input')->assertSessionMissing('errors')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(0, Participation::count());
    }

    public function test_a_legitimate_other_school_manager_cannot_open_or_change_this_school_consultation(): void
    {
        $id = $this->create();
        $other = app(CurrentSchool::class)->run(2, function () {
            $actor = User::findOrFail($this->schoolB->ids['tara_user']);
            SchoolMembership::create(['school_id' => 2, 'user_id' => $actor->id, 'role' => SchoolRoles::SUPER_ADMIN, 'status' => 'active', 'starts_on' => '2026-01-01']);
            $actor->assignRole(SchoolRoles::SUPER_ADMIN);
            app(ModuleGate::class)->change('consultations', true, $actor);

            return $actor;
        });
        $this->actingAs($other)->withHeaders(['X-School-Id' => '2']);
        foreach (["/$id", "/$id/edit", "/$id/history", "/$id/results?managed=1"] as $path) {
            $this->get('/school-consultations'.$path)->assertNotFound()->assertDontSee('Synthetic panel consultation');
        }
        $this->post("/school-consultations/$id/save", $this->data(['expected_account_id' => $other->id, 'expected_school_id' => 2]))->assertNotFound()->assertSessionMissing('_old_input');
    }

    public function test_fresh_wrong_role_and_wrong_owner_cannot_reach_configuration_or_reflect_drafts(): void
    {
        $id = $this->create();
        $staff = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->actingAs($staff)->get("/school-consultations/$id/edit")->assertForbidden();
        $this->post("/school-consultations/$id/save", $this->data(['expected_account_id' => $staff->id, 'title' => 'Synthetic denied manager draft']))->assertForbidden()->assertDontSee('Synthetic denied manager draft')->assertSessionMissing('_old_input');
        $this->get("/school-consultations/$id")->assertNotFound();
        $this->get("/school-consultations/$id/history")->assertForbidden();
        $this->actingAs($this->superAdmin());
        SchoolModule::where('module', 'consultations')->update(['enabled' => false]);
        $this->get('/school-consultations')->assertNotFound();
    }
}
