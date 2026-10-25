<?php

declare(strict_types=1);

namespace Tests\Feature\Discipline;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\TeachingGroup;
use App\Models\Discipline\DetentionEntry;
use App\Models\Discipline\DetentionSession;
use App\Models\Discipline\DisciplineRecord;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Services\Discipline\DisciplineFrameworks;
use App\Services\Discipline\DisciplineRecords;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

class DisciplinePanelTest extends TestCase
{
    use UsesMasteryScenario;

    private int $grade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        Bus::fake();
        $this->grade = (int) TeachingGroup::findOrFail($this->group)->grade_level_id;
        SchoolModule::updateOrCreate(['module' => 'discipline'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
    }

    private function definition(): array
    {
        return ['title' => 'Factual school framework', 'expected_revision' => 0, 'counter_scope' => 'same_infraction', 'reset' => 'school_year', 'rolling_days' => null,
            'levels' => [['code' => 'warning', 'name' => 'School notice', 'threshold' => 1, 'kind' => 'sanction', 'sanctions' => ['notice']],
                ['code' => 'hearing', 'name' => 'Principal hearing', 'threshold' => 2, 'kind' => 'hearing', 'sanctions' => []]],
            'sanctions' => [['code' => 'notice', 'name' => 'School notice', 'kind' => 'notice']],
            'infractions' => [['code' => 'late', 'name' => 'Late arrival', 'base_level' => 'warning']]];
    }

    private function identity(string $mode = 'records'): array
    {
        return ['expected_account_id' => $this->actor()->id, 'expected_school_id' => 1, 'operation_id' => (string) Str::uuid(), 'session_year_id' => $this->year, 'mode' => $mode];
    }

    private function occurrence(): array
    {
        return $this->identity() + ['occurred_on' => '2026-02-10', 'infraction_code' => 'late', 'description' => '<script>window.disciplinePrivate=1</script>', 'staff_note' => 'Private staff follow-up'];
    }

    public function test_teacher_records_facts_and_private_notes_are_escaped_retained_and_not_session_flashed(): void
    {
        app(DisciplineFrameworks::class)->save($this->superAdmin(), $this->year, $this->definition());
        $url = '/school-discipline?session_year_id='.$this->year.'&learner_id='.$this->schoolA->ids['asha'];
        $this->actingAs($this->actor())->get($url)->assertOk()->assertSee('Record a factual occurrence')->assertHeader('Cache-Control', 'no-store, private');
        $response = $this->post('/school-discipline/learners/'.$this->schoolA->ids['asha'].'/records', $this->occurrence())->assertRedirect();
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('&lt;script&gt;window.disciplinePrivate=1&lt;/script&gt;', false)->assertDontSee('<script>window.disciplinePrivate=1</script>', false)->assertSee('Private staff follow-up')->assertSee('Immutable entry history');
        $this->assertFalse(session()->has('_old_input'));
        $id = (int) DisciplineRecord::sole()->id;
        $this->actingAs($this->actor('theo'))->get('/school-discipline?record='.$id)->assertNotFound()->assertDontSee('Private staff follow-up');
    }

    public function test_published_framework_form_and_invalid_nested_input_preserve_only_direct_draft(): void
    {
        $input = $this->identity('framework') + $this->definition();
        $input['expected_account_id'] = $this->superAdmin()->id;
        $this->actingAs($this->superAdmin())->get('/school-discipline?mode=framework')->assertOk()->assertSee('Publish school framework')->assertSee('data-disc-template', false);
        $this->post('/school-discipline/framework', $input)->assertRedirect();
        $input['operation_id'] = (string) Str::uuid();
        $input['expected_revision'] = 1;
        $input['levels'][0]['name'] = ['bad'];
        $this->post('/school-discipline/framework', $input)->assertUnprocessable()->assertSee('Configure the school framework');
        $this->assertFalse(session()->has('_old_input'));
    }

    public function test_grade_review_conflict_requires_explicit_current_comparison_and_principal_hearing_is_separate(): void
    {
        app(DisciplineFrameworks::class)->save($this->superAdmin(), $this->year, $this->definition());
        AcademicLeadership::create(['user_id' => $this->actor()->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade, 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic grade head']);
        $records = app(DisciplineRecords::class);
        $source = ['occurred_on' => '2026-02-10', 'infraction_code' => 'late', 'description' => 'Observed school fact'];
        $one = $records->create($this->actor(), $this->schoolA->ids['asha'], $this->year, $source);
        $second = $records->create($this->actor(), $this->schoolA->ids['asha'], $this->year, $source);
        $records->review($this->actor(), $one['id'], ['expected_revision' => 1, 'decision' => 'overturn', 'reason' => 'Corrected current source']);
        $input = $this->identity() + ['expected_revision' => 1, 'decision' => 'overturn', 'reason' => 'My retained unsaved private reason'];
        $this->actingAs($this->actor())->post('/school-discipline/records/'.$one['id'].'/review', $input)->assertConflict()->assertSee('My retained unsaved private reason')->assertSee('Current discipline entry');
        $this->assertFalse(session()->has('_old_input'));
        $input['operation_id'] = (string) Str::uuid();
        $input['decision'] = 'confirm';
        $this->post('/school-discipline/records/'.$second['id'].'/review', $input)->assertRedirect();
        $this->get('/school-discipline?record='.$second['id'])->assertOk()->assertSee('Principal hearing')->assertDontSee('aria-label="Principal hearing decision"', false);
        AcademicLeadership::create(['user_id' => $this->superAdmin()->id, 'kind' => 'principal', 'grade_level_id' => null, 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic appointed principal']);
        $this->actingAs($this->superAdmin())->get('/school-discipline?record='.$second['id'])->assertOk()->assertSee('aria-label="Principal hearing decision"', false);
    }

    public function test_private_body_queries_replaced_cookie_and_historical_header_do_not_flash_or_disclose_draft(): void
    {
        app(DisciplineFrameworks::class)->save($this->superAdmin(), $this->year, $this->definition());
        $url = '/school-discipline/learners/'.$this->schoolA->ids['asha'].'/records';
        $input = $this->occurrence();
        $input['expected_account_id'] = $this->actor('theo')->id;
        $this->actingAs($this->actor())->post($url, $input)->assertForbidden()->assertDontSee('Private staff follow-up');
        $this->post($url.'?staff_note=URL_SECRET', $this->occurrence())->assertUnprocessable()->assertDontSee('URL_SECRET');
        $old = SessionYear::create(['name' => 'Retained year', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        $this->withSession(['session_year' => $old->id])->post($url, $this->occurrence())->assertStatus(423)->assertSee('Private staff follow-up');
        $this->assertFalse(session()->has('_old_input'));
        $this->assertSame(0, DisciplineRecord::count());
    }

    public function test_detention_is_planned_rostered_captured_corrected_and_retained_through_panel(): void
    {
        $definition = $this->definition();
        $definition['sanctions'][0]['kind'] = 'detention';
        app(DisciplineFrameworks::class)->save($this->superAdmin(), $this->year, $definition);
        $record = app(DisciplineRecords::class)->create($this->actor(), $this->schoolA->ids['asha'], $this->year,
            ['occurred_on' => '2026-02-10', 'infraction_code' => 'late', 'description' => 'Observed detention source']);
        $identity = $this->identity('detentions');
        $identity['expected_account_id'] = $this->superAdmin()->id;
        $this->actingAs($this->superAdmin())->get('/school-discipline?mode=detentions&grade_level_id='.$this->grade.'&on_date=2026-02-10')
            ->assertOk()->assertSee('Refresh eligible supervisors')->assertSee('name="on_date" type="date" value="2026-02-10" readonly', false);
        $input = $identity + ['grade_level_id' => $this->grade, 'supervisor_id' => $this->actor()->id, 'on_date' => '2026-02-10', 'starts_at' => '14:00', 'ends_at' => '15:00', 'location' => 'Supervised school room', 'capacity' => 10];
        $this->post('/school-discipline/detentions', $input)->assertRedirect();
        $session = DetentionSession::sole();
        $this->post('/school-discipline/detentions/'.$session->id.'/entries', array_replace($identity, ['operation_id' => (string) Str::uuid(), 'record_id' => $record['id'], 'expected_revision' => 1]))->assertRedirect();
        $entry = DetentionEntry::sole();
        $this->actingAs($this->actor())->get('/school-discipline?mode=detentions&detention='.$session->id)->assertOk()->assertSee('Not captured');
        $attendance = $this->identity('detentions') + ['expected_revision' => 1, 'expected_session_revision' => 2, 'attendance' => 'present', 'reason' => 'Observed the learner in the session'];
        $this->post('/school-discipline/detentions/'.$session->id.'/entries/'.$entry->id.'/attendance', $attendance)->assertRedirect();
        $this->get('/school-discipline?mode=detentions&detention='.$session->id)->assertOk()->assertSee('Present')->assertDontSee('aria-label="Capture detention attendance"', false);
        $this->actingAs($this->superAdmin())->get('/school-discipline?mode=detentions&detention='.$session->id)->assertOk()->assertSee('aria-label="Capture detention attendance"', false);
        $this->post('/school-discipline/detentions/'.$session->id.'/cancel', array_replace($identity, ['operation_id' => (string) Str::uuid(), 'expected_revision' => 3, 'reason' => 'Session cancelled with retained attendance']))->assertRedirect();
        $this->get('/school-discipline?mode=detentions&detention='.$session->id)->assertOk()->assertSee('Present')->assertDontSee('aria-label="Capture detention attendance"', false);
        $this->assertFalse(session()->has('_old_input'));
    }
}
