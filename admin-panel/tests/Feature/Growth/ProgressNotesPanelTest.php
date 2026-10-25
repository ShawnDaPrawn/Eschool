<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\AssessmentPlan;
use App\Models\Growth\LearnerNote;
use App\Models\Growth\LearnerNoteAssignment;
use App\Models\Growth\ProgressBandSettings;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\SessionYear;
use App\Models\Term;
use App\Services\Growth\LearnerNotes;
use App\Services\Panel\Navigation;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Support\Str;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

class ProgressNotesPanelTest extends TestCase
{
    use UsesMasteryScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
    }

    private function input(array $extra = []): array
    {
        return $extra + ['operation_id' => (string) Str::uuid(), 'expected_account_id' => $this->actor()->id, 'expected_school_id' => 1,
            'mode' => 'academic', 'group_id' => $this->group, 'subject_id' => $this->subject, 'session_year_id' => $this->year,
            'visibility' => 'staff', 'body' => 'Private panel teaching observation'];
    }

    private function url(array $extra = []): string
    {
        return '/learner-notes?'.http_build_query($extra + ['mode' => 'academic', 'group_id' => $this->group, 'learner_id' => $this->schoolA->ids['asha'],
            'subject_id' => $this->subject, 'session_year_id' => $this->year, 'visibility' => 'staff']);
    }

    private function clinical(): array
    {
        AcademicLeadership::create(['user_id' => $this->superAdmin()->id, 'kind' => 'principal', 'grade_level_id' => null, 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic principal']);
        $this->actor()->assignRole(SchoolRoles::COUNSELLOR);
        app(LearnerNotes::class)->assign($this->superAdmin(), $this->schoolA->ids['asha'], $this->actor()->id,
            ['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'expected_revision' => 0]);

        return app(LearnerNotes::class)->create($this->actor(), $this->schoolA->ids['asha'], null, $this->year, 'counselling', 'Private clinical panel body');
    }

    public function test_allocated_teacher_creates_and_reads_escaped_notes_with_current_progress(): void
    {
        $this->capture('8.00', '8.00');
        $this->actingAs($this->actor())->get($this->url())->assertOk()->assertSee('Current progress band')->assertSee('80.00%')->assertHeader('Cache-Control', 'no-store, private');
        $input = $this->input(['body' => '<script>window.privateDraft = 1</script>']);
        $saved = $this->post('/learner-notes/learners/'.$this->schoolA->ids['asha'].'/notes', $input)->assertRedirect();
        $this->assertSame(1, LearnerNote::count());
        $this->get($saved->headers->get('Location'))->assertOk()->assertSee('&lt;script&gt;window.privateDraft = 1&lt;/script&gt;', false)
            ->assertDontSee('<script>window.privateDraft = 1</script>', false);
        $this->assertFalse(session()->has('_old_input'));
    }

    public function test_missing_published_assessment_does_not_block_independent_note_authoring(): void
    {
        AssessmentPlan::query()->update(['state' => 'draft']);
        $this->actingAs($this->actor())->get($this->url())
            ->assertOk()->assertSee('Create learner note')->assertDontSee('Current progress band');
        $saved = $this->post('/learner-notes/learners/'.$this->schoolA->ids['asha'].'/notes', $this->input())
            ->assertRedirect();
        $this->get($saved->headers->get('Location'))->assertOk()->assertSee('Private panel teaching observation');
    }

    public function test_private_forms_refuse_query_inputs_including_historical_draft_reflection(): void
    {
        $url = '/learner-notes/learners/'.$this->schoolA->ids['asha'].'/notes?body=URL_PRIVATE_NOTE_VALUE';
        $this->actingAs($this->actor())->post($url, $this->input())
            ->assertStatus(422)->assertDontSee('URL_PRIVATE_NOTE_VALUE');
        $past = SessionYear::create(['name' => 'Older privacy year', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        $this->withSession(['session_year' => $past->id])->post($url, $this->input())
            ->assertStatus(422)->assertDontSee('URL_PRIVATE_NOTE_VALUE')->assertDontSee('Private panel teaching observation');
        $this->assertSame(0, LearnerNote::count());
        $this->assertFalse(session()->has('_old_input'));
    }

    public function test_private_validation_and_conflicts_retain_only_author_response_not_session_flash(): void
    {
        $note = app(LearnerNotes::class)->create($this->actor(), $this->schoolA->ids['asha'], $this->subject, $this->year, 'staff', 'Original own note');
        $base = ['expected_account_id' => $this->actor()->id, 'expected_school_id' => 1, 'mode' => 'academic', 'group_id' => $this->group, 'operation_id' => (string) Str::uuid()];
        $this->actingAs($this->actor())->post('/learner-notes/notes/'.$note['id'], $base + ['expected_revision' => 'invalid', 'body' => 'Unflashed private validation draft'])
            ->assertStatus(422)->assertSee('Unflashed private validation draft')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertFalse(session()->has('_old_input'));
        app(LearnerNotes::class)->update($this->actor(), $note['id'], 1, 'A newer saved revision');
        $this->post('/learner-notes/notes/'.$note['id'], $base + ['expected_revision' => 1, 'body' => 'My stale private draft'])
            ->assertStatus(409)->assertSee('A newer saved revision')->assertSee('My stale private draft')->assertSee('confirm_current');
        $this->assertFalse(session()->has('_old_input'));
        $this->assertSame('A newer saved revision', app(LearnerNotes::class)->read($this->actor(), $note['id'])['body']);
    }

    public function test_counselling_caseload_and_principal_metadata_are_separate_and_end_access_immediately(): void
    {
        $note = $this->clinical();
        $this->actingAs($this->actor())->get('/learner-notes?mode=counselling&learner_id='.$this->schoolA->ids['asha'].'&note='.$note['id'])
            ->assertOk()->assertSee('Private clinical panel body');
        $this->actingAs($this->superAdmin())->get('/learner-notes?mode=assignments&learner_id='.$this->schoolA->ids['asha'])
            ->assertOk()->assertDontSee('Private clinical panel body')->assertSee('End note access now');
        $this->post('/learner-notes/learners/'.$this->schoolA->ids['asha'].'/assignments/revoke', ['mode' => 'assignments',
            'expected_account_id' => $this->superAdmin()->id, 'expected_school_id' => 1, 'operation_id' => (string) Str::uuid(),
            'staff_user_id' => $this->actor()->id, 'expected_revision' => 1])->assertRedirect();
        $this->assertNotNull(LearnerNoteAssignment::query()->sole()->revoked_at);
        $this->actingAs($this->actor())->get('/learner-notes?mode=counselling&learner_id='.$this->schoolA->ids['asha'].'&note='.$note['id'])->assertNotFound();
    }

    public function test_historical_header_preserves_own_escaped_draft_without_old_input_but_revoked_role_gets_no_reflection(): void
    {
        $note = $this->clinical();
        $past = SessionYear::create(['name' => 'Earlier notes year', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        $this->actingAs($this->actor())->withSession(['session_year' => $past->id]);
        $input = $this->input(['mode' => 'counselling', 'visibility' => 'counselling', 'body' => '<b>Historical private draft</b>']);
        unset($input['subject_id'], $input['group_id']);
        $this->post('/learner-notes/learners/'.$this->schoolA->ids['asha'].'/notes', $input)->assertStatus(423)
            ->assertSee('&lt;b&gt;Historical private draft&lt;/b&gt;', false)->assertDontSee('<b>Historical private draft</b>', false);
        $this->assertFalse(session()->has('_old_input'));
        $this->actor()->syncRoles([SchoolRoles::TEACHER]);
        $this->post('/learner-notes/learners/'.$this->schoolA->ids['asha'].'/notes', $input)->assertForbidden()->assertDontSee('Historical private draft');
    }

    public function test_cookie_account_replacement_wrong_school_owner_and_family_cannot_load_or_write_notes(): void
    {
        $this->actingAs($this->actor('farid'))->get('/learner-notes')->assertForbidden();
        $this->actingAs($this->actor('theo'))->get($this->url())->assertNotFound();
        $this->actingAs($this->actor())->get($this->url(['learner_id' => $this->schoolB->ids['asha']]))->assertNotFound();
        $input = $this->input();
        $this->actingAs($this->actor('theo'))->post('/learner-notes/learners/'.$this->schoolA->ids['asha'].'/notes', $input)->assertForbidden();
        $this->assertSame(0, LearnerNote::count());
    }

    public function test_percent_settings_are_human_units_and_metadata_menu_rechecks_fresh_roles(): void
    {
        $this->actingAs($this->superAdmin())->post('/learner-notes/settings', ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0,
            'expected_account_id' => $this->superAdmin()->id, 'expected_school_id' => 1, 'high_pct' => '82.25', 'secure_pct' => '66.50', 'minimum_coverage_pct' => '20.00'])->assertRedirect();
        $this->assertSame(8225, ProgressBandSettings::query()->sole()->high_bp);
        $item = collect(app(Navigation::class)->definitions())->firstWhere('key', 'learner-notes');
        $this->assertTrue(app(Navigation::class)->visible($item, $this->actor()));
        $this->actor()->syncRoles([SchoolRoles::FACILITATOR]);
        $this->assertFalse(app(Navigation::class)->visible($item, $this->actor()));
        PlatformStaffIdentity::create(['user_id' => $this->superAdmin()->id]);
        $this->assertSame(0, LearnerNote::count());
    }

    public function test_panel_chooses_a_grade_valid_term_without_blocking_independent_notes(): void
    {
        Term::whereKey($this->term)->update(['start_date' => '2026-01-02', 'grade_from' => 7, 'grade_to' => 7]);
        Term::create(['session_year_id' => $this->year, 'name' => 'Other grade first term', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'grade_from' => 8, 'grade_to' => 8]);
        $this->capture('8.00', '8.00');
        $this->actingAs($this->actor())->get($this->url())->assertOk()->assertSee('Current progress band')->assertSee('80.00%')->assertDontSee('Other grade first term');
    }
}
