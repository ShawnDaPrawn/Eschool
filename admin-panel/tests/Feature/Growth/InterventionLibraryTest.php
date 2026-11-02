<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Growth\Intervention;
use App\Models\Growth\InterventionLibraryAction;
use App\Models\Growth\InterventionProgress;
use App\Models\Platform\SchoolModule;
use App\Services\Growth\InterventionLibrary;
use App\Services\Growth\InterventionPlans;
use App\Services\Growth\Interventions;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

/**
 * The intervention library and progress against a plan's steps (Action Plan
 * 4.11). engineering/specs/growth/check-ins-and-intervention-library.md.
 */
class InterventionLibraryTest extends TestCase
{
    use UsesMasteryScenario;

    private int $grade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        Bus::fake();
        SchoolModule::updateOrCreate(['module' => 'early_warning'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        $this->grade = (int) TeachingGroup::findOrFail($this->group)->grade_level_id;
        AcademicLeadership::create(['user_id' => $this->superAdmin()->id, 'kind' => 'principal', 'grade_level_id' => null,
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic principal']);
    }

    private function denied(callable $call, int $expected): void
    {
        try {
            $call();
            $this->fail('The change must be refused.');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($expected, $e->getStatusCode());
        } catch (ModelNotFoundException) {
            $this->assertSame($expected, 404);
        }
    }

    private function identity(string $name = 'tara'): array
    {
        $user = $name === 'principal' ? $this->superAdmin() : $this->actor($name);

        return ['expected_account_id' => $user->id, 'expected_school_id' => app(CurrentSchool::class)->id()];
    }

    private function action(): array
    {
        return ['name' => 'Reading buddy', 'concern_area' => 'learning', 'steps' => ['Pair with an older reader', 'Read together twice a week', 'Talk about the book'],
            'suggested_weeks' => 4, 'progress_measure' => 'Books finished and the learner\'s own view'];
    }

    private function fromLibrary(int $library, ?string $action = null): array
    {
        return app(Interventions::class)->create($this->actor(), $this->schoolA->ids['asha'], $this->year, ['owner_id' => $this->actor()->id,
            'library_action_id' => $library, 'action' => $action, 'started_on' => '2026-02-10', 'review_on' => '2026-03-10']);
    }

    public function test_a_principal_adopts_the_suggested_actions_once_and_changes_them(): void
    {
        $library = app(InterventionLibrary::class);
        $this->assertSame([], $library->list($this->superAdmin())['actions'], 'nothing is added until the school adopts it');
        $this->assertSame(count(InterventionLibrary::DEFAULTS), $library->adopt($this->superAdmin()));
        $this->assertSame(0, $library->adopt($this->superAdmin()), 'adopting again adds nothing');
        $this->assertSame(count(InterventionLibrary::DEFAULTS), InterventionLibraryAction::count());
        foreach (InterventionLibraryAction::all() as $row) {
            $this->assertContains($row->concern_area, InterventionLibrary::AREAS);
            $this->assertNotEmpty($row->steps);
        }

        $row = InterventionLibraryAction::where('default_key', 'peer_buddy')->sole();
        $library->archive($this->superAdmin(), (int) $row->id, 1, true);
        $this->assertSame(0, $library->adopt($this->superAdmin()), 'an archived suggestion stays archived');
        $this->assertNotContains((int) $row->id, array_column($library->choices(), 'id'));

        $saved = $library->save($this->superAdmin(), (int) $row->id, $this->action() + ['expected_revision' => 2]);
        $this->assertSame(['Reading buddy', 3], [$saved['name'], $saved['revision']]);
        $this->denied(fn () => $library->save($this->superAdmin(), (int) $row->id, $this->action() + ['expected_revision' => 2]), 409);
        try {
            $library->save($this->superAdmin(), null, ['steps' => array_fill(0, 9, 'Too many steps')] + $this->action());
            $this->fail('Nine steps were accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('steps', $e->errors());
        }

        $this->actingAs($this->superAdmin())->get(route('risk-interventions.library'))->assertOk()->assertSee('Reading buddy')
            ->assertSee('Add a library action')->assertHeader('Cache-Control', 'no-store, private');
        $this->post(route('risk-interventions.library.store'), $this->identity('principal') + ['name' => 'Lunchtime club', 'concern_area' => 'relationships',
            'steps_text' => "Invite the learner\n\nWelcome them on the first day\n", 'suggested_weeks' => 3, 'progress_measure' => 'Days attended'])
            ->assertRedirect(route('risk-interventions.library'))->assertSessionHas('success');
        $this->assertSame(['Invite the learner', 'Welcome them on the first day'], InterventionLibraryAction::where('name', 'Lunchtime club')->sole()->steps);
    }

    public function test_a_teacher_starts_an_intervention_from_the_library_and_records_progress_against_its_steps(): void
    {
        app(InterventionLibrary::class)->adopt($this->superAdmin());
        $library = InterventionLibraryAction::where('default_key', 'catch_up')->sole();
        $record = $this->fromLibrary((int) $library->id);

        $row = Intervention::findOrFail($record['id']);
        $this->assertSame((int) $library->id, (int) $row->library_action_id);
        $this->assertSame($library->steps, $row->plan['steps']);
        $this->assertStringContainsString('1. List the missed topics with the subject teacher', $row->action, 'a blank action takes the library steps');
        $this->assertStringNotContainsString('List the missed topics', (string) DB::table('interventions')->where('id', $row->id)->value('plan'), 'the plan is encrypted');
        $this->assertArrayNotHasKey('plan', app(Interventions::class)->read($this->actor(), $record['id']), 'the v2 detail response is unchanged');

        // Later library edits never rewrite a plan already made.
        app(InterventionLibrary::class)->save($this->superAdmin(), (int) $library->id, $this->action() + ['expected_revision' => 1]);
        $plans = app(InterventionPlans::class);
        $this->assertSame('Catch-up support for missed work', $plans->read($this->actor(), $record['id'])['plan']['name']);

        $entry = $plans->record($this->actor(), $record['id'], ['recorded_on' => '2026-02-10', 'step' => 2, 'status' => 'needs_review', 'note' => 'Missed one session']);
        $plans->record($this->actor(), $record['id'], ['recorded_on' => '2026-02-10', 'step' => null, 'status' => 'on_track']);
        $read = $plans->read($this->actor(), $record['id']);
        $this->assertSame([2, 'needs_review', 'Missed one session'], [$read['entries'][1]['step'], $read['entries'][1]['status'], $read['entries'][1]['note']]);
        $this->assertNull($read['entries'][0]['step']);
        $this->assertStringNotContainsString('Missed one session', (string) DB::table('intervention_progress_entries')->where('id', $entry['id'])->value('note'));
        $this->assertStringNotContainsString('Missed one session', (string) DB::table('audit_log')->where('action', 'growth.intervention_progress_recorded')->get()->toJson());

        foreach ([['step' => 5], ['recorded_on' => '2026-02-09'], ['recorded_on' => '2026-02-11'], ['status' => 'great']] as $bad) {
            try {
                $plans->record($this->actor(), $record['id'], $bad + ['recorded_on' => '2026-02-10', 'status' => 'on_track']);
                $this->fail('A wrong progress entry was accepted');
            } catch (ValidationException) {
                $this->assertSame(2, InterventionProgress::count());
            }
        }
        $this->expectException(\LogicException::class);
        InterventionProgress::firstOrFail()->update(['status' => 'completed']);
    }

    public function test_the_panel_offers_the_library_and_records_progress_without_flashing_the_note(): void
    {
        app(InterventionLibrary::class)->adopt($this->superAdmin());
        $library = InterventionLibraryAction::where('default_key', 'named_adult')->sole();
        $this->actingAs($this->actor())->get('/risk-interventions?session_year_id='.$this->year.'&learner_id='.$this->schoolA->ids['asha'])
            ->assertOk()->assertSee('Start from a library action')->assertSee('Daily check-in with a named adult');
        $saved = $this->post('/risk-interventions/learners/'.$this->schoolA->ids['asha'].'/interventions', $this->identity() + ['operation_id' => (string) Str::uuid(),
            'mode' => 'queue', 'session_year_id' => $this->year, 'owner_choice' => $this->actor()->id.':academic', 'library_action_id' => $library->id,
            'action' => '', 'started_on' => '2026-02-10', 'review_on' => '2026-03-03'])->assertRedirect();
        $id = (int) Intervention::sole()->id;
        $this->get($saved->headers->get('Location'))->assertOk()->assertSee('Plan from the library')->assertSee('Meet briefly at the start of each school day')
            ->assertSee('Record progress');

        $progress = $this->identity() + ['operation_id' => (string) Str::uuid(), 'mode' => 'queue', 'session_year_id' => $this->year,
            'recorded_on' => '2026-02-10', 'step' => 1, 'status' => 'on_track', 'note' => '<b>Came in smiling</b>'];
        $done = $this->post(route('risk-interventions.progress', ['intervention' => $id]), $progress)->assertRedirect();
        $this->get($done->headers->get('Location'))->assertOk()->assertSee('&lt;b&gt;Came in smiling&lt;/b&gt;', false)->assertSee('On track');
        $this->assertSame(1, InterventionProgress::count());
        // The same operation again is a replay, not a second entry; a refusal never flashes the note.
        $this->post(route('risk-interventions.progress', ['intervention' => $id]), $progress)->assertRedirect();
        $this->post(route('risk-interventions.progress', ['intervention' => $id]), ['status' => 'bad', 'operation_id' => (string) Str::uuid()] + $progress)->assertUnprocessable();
        $this->assertSame(1, InterventionProgress::count());
        $this->assertFalse(session()->has('_old_input'));
    }

    public function test_only_a_principal_changes_the_library(): void
    {
        app(InterventionLibrary::class)->adopt($this->superAdmin());
        $row = InterventionLibraryAction::firstOrFail();
        AcademicLeadership::create(['user_id' => $this->actor('theo')->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade,
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic grade head']);
        $before = InterventionLibraryAction::query()->orderBy('id')->get()->toJson();

        // An allocated teacher and a grade head read the library but cannot change it.
        foreach (['tara', 'theo'] as $name) {
            $this->denied(fn () => app(InterventionLibrary::class)->save($this->actor($name), null, $this->action()), 403);
            $this->denied(fn () => app(InterventionLibrary::class)->archive($this->actor($name), (int) $row->id, 1, true), 403);
            $this->denied(fn () => app(InterventionLibrary::class)->adopt($this->actor($name)), 403);
            $this->actingAs($this->actor($name))->get(route('risk-interventions.library'))->assertOk()->assertSee($row->name)->assertDontSee('Add a library action');
            $this->post(route('risk-interventions.library.store'), $this->identity($name) + $this->action() + ['steps_text' => 'One step'])->assertForbidden();
            $this->post(route('risk-interventions.library.update', ['action' => $row->id]), $this->identity($name) + $this->action() + ['steps_text' => 'One step', 'expected_revision' => 1])->assertForbidden();
            $this->post(route('risk-interventions.library.archive', ['action' => $row->id]), $this->identity($name) + ['expected_revision' => 1, 'archived' => 1])->assertForbidden();
            $this->post(route('risk-interventions.library.adopt'), $this->identity($name))->assertForbidden();
        }
        // A guardian has no workbench at all; the principal's form is bound to the principal.
        $this->actingAs($this->actor('farid'))->get(route('risk-interventions.library'))->assertForbidden();
        $this->actingAs($this->superAdmin())->post(route('risk-interventions.library.store'), $this->identity('tara') + $this->action() + ['steps_text' => 'One step'])->assertForbidden();
        $this->post(route('risk-interventions.library.store').'?name=x', $this->identity('principal') + $this->action() + ['steps_text' => 'One step'])->assertUnprocessable();
        $this->assertSame($before, InterventionLibraryAction::query()->orderBy('id')->get()->toJson());
    }

    public function test_someone_who_does_not_own_the_intervention_cannot_read_or_add_progress(): void
    {
        app(InterventionLibrary::class)->adopt($this->superAdmin());
        $record = $this->fromLibrary((int) InterventionLibraryAction::where('default_key', 'catch_up')->value('id'));
        app(InterventionPlans::class)->record($this->actor(), $record['id'], ['recorded_on' => '2026-02-10', 'status' => 'on_track', 'note' => 'Owner only note']);
        AcademicLeadership::create(['user_id' => $this->actor('theo')->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade,
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic grade head']);

        // The grade head and the principal see the queue metadata, never the plan or its progress.
        foreach (['theo' => $this->actor('theo'), 'principal' => $this->superAdmin()] as $name => $user) {
            $this->denied(fn () => app(InterventionPlans::class)->read($user, $record['id']), 404);
            $this->denied(fn () => app(InterventionPlans::class)->record($user, $record['id'], ['recorded_on' => '2026-02-10', 'status' => 'completed']), 404);
            $this->actingAs($user)->get('/risk-interventions?session_year_id='.$this->year)->assertOk()->assertDontSee('Owner only note')->assertDontSee('List the missed topics');
            $this->post(route('risk-interventions.progress', ['intervention' => $record['id']]), $this->identity($name) + ['operation_id' => (string) Str::uuid(),
                'mode' => 'queue', 'session_year_id' => $this->year, 'recorded_on' => '2026-02-10', 'status' => 'completed', 'note' => 'Not the owner'])->assertNotFound();
        }
        $this->assertSame(1, InterventionProgress::count());

        // Ownership alone is not enough: the owner who loses the teaching allocation loses the plan too.
        DB::table('teaching_allocations')->update(['ends_on' => '2026-02-01']);
        $this->denied(fn () => app(InterventionPlans::class)->read($this->actor(), $record['id']), 404);
        $this->assertSame(1, InterventionProgress::count());
    }

    public function test_another_schools_library_actions_and_interventions_are_refused(): void
    {
        $b = $this->schoolB->ids;
        [$foreignAction, $foreignIntervention] = app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function () use ($b) {
            $action = InterventionLibraryAction::create(['name' => 'Baobab action', 'concern_area' => 'learning', 'steps' => ['Baobab step'], 'suggested_weeks' => 2,
                'progress_measure' => 'Baobab measure', 'created_by' => $b['tara_user'], 'revision' => 1]);
            $intervention = Intervention::create(['learner_id' => $b['asha'], 'session_year_id' => $b['session_year'], 'grade_level_id' => (int) GradeLevel::where('code', '7')->value('id'),
                'owner_id' => $b['tara_user'], 'created_by' => $b['tara_user'], 'owner_authority' => 'academic', 'started_on' => '2026-02-01', 'review_on' => '2026-03-01',
                'state' => 'active', 'action' => 'Baobab private action', 'revision' => 1]);

            return [$action, $intervention];
        });
        $before = DB::table('intervention_library_actions')->where('id', $foreignAction->id)->first();

        $this->denied(fn () => $this->fromLibrary((int) $foreignAction->id), 404);
        $this->assertSame(0, Intervention::count(), 'School A gains no intervention');
        $this->denied(fn () => app(InterventionLibrary::class)->save($this->superAdmin(), (int) $foreignAction->id, $this->action() + ['expected_revision' => 1]), 404);
        $this->denied(fn () => app(InterventionLibrary::class)->archive($this->superAdmin(), (int) $foreignAction->id, 1, true), 404);
        $this->denied(fn () => app(InterventionPlans::class)->record($this->actor(), (int) $foreignIntervention->id, ['recorded_on' => '2026-02-10', 'status' => 'on_track']), 404);
        $this->assertNotContains((int) $foreignAction->id, array_column(app(InterventionLibrary::class)->choices(), 'id'));

        $this->actingAs($this->superAdmin())->get(route('risk-interventions.library'))->assertOk()->assertDontSee(self::SCHOOL_B_MARKER);
        $this->post(route('risk-interventions.library.archive', ['action' => $foreignAction->id]), $this->identity('principal') + ['expected_revision' => 1, 'archived' => 1])->assertNotFound();
        $this->actingAs($this->actor())->post(route('risk-interventions.progress', ['intervention' => $foreignIntervention->id]), $this->identity() + ['operation_id' => (string) Str::uuid(),
            'mode' => 'queue', 'session_year_id' => $this->year, 'recorded_on' => '2026-02-10', 'status' => 'on_track'])->assertNotFound();
        $this->assertEquals($before, DB::table('intervention_library_actions')->where('id', $foreignAction->id)->first(), 'School B\'s action is unchanged');
        $this->assertSame(0, DB::table('intervention_progress_entries')->count());
    }

    public function test_archived_actions_are_not_offered_and_completed_plans_take_no_more_progress(): void
    {
        app(InterventionLibrary::class)->adopt($this->superAdmin());
        $library = InterventionLibraryAction::where('default_key', 'study_routine')->sole();
        $record = $this->fromLibrary((int) $library->id, 'My own wording for the routine');
        $this->assertSame('My own wording for the routine', Intervention::findOrFail($record['id'])->action);
        app(InterventionLibrary::class)->archive($this->superAdmin(), (int) $library->id, 1, true);

        $this->denied(fn () => $this->fromLibrary((int) $library->id), 404);
        $this->assertSame(1, Intervention::count());
        $this->assertSame('Organisation and homework routine', app(InterventionPlans::class)->read($this->actor(), $record['id'])['plan']['name'], 'the plan keeps its copy');

        app(Interventions::class)->update($this->actor(), $record['id'], ['expected_revision' => 1, 'action' => 'Done', 'review_on' => '2026-03-10', 'state' => 'completed', 'outcome' => 'Routine in place']);
        $this->denied(fn () => app(InterventionPlans::class)->record($this->actor(), $record['id'], ['recorded_on' => '2026-02-10', 'status' => 'completed']), 409);
        $this->assertSame(0, InterventionProgress::count());
    }
}
