<?php

declare(strict_types=1);

namespace Tests\Feature\Wellbeing;

use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Models\Wellbeing\Appointment;
use App\Models\Wellbeing\CaseEntry;
use App\Models\Wellbeing\IncidentReport;
use App\Models\Wellbeing\IncidentThread;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Wellbeing\Casework;
use App\Services\Wellbeing\IncidentReporting;
use App\Services\Wellbeing\WellbeingQueries;
use App\Services\Wellbeing\WellbeingSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class WellbeingPanelTest extends TestCase
{
    use UsesTwoSchools;

    private User $professional;

    private array $case;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10)->setTime(12, 0));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        app(ModuleGate::class)->change('wellbeing', true, $this->superAdmin());
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->schoolA->ids['session_year'], 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic panel enrolment']);
        $this->professional = $this->professional('one');
        $settings = app(WellbeingSettings::class);
        $this->case = $settings->openCase($this->superAdmin(), ['operation_id' => (string) Str::uuid(), 'learner_id' => $this->schoolA->ids['asha']]);
        $settings->assignCase($this->superAdmin(), $this->case['id'], null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'user_id' => $this->professional->id, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Explicit clinical grant']);
    }

    private function professional(string $suffix): User
    {
        $user = User::create(['first_name' => 'Clinic', 'last_name' => 'Professional '.$suffix, 'email' => 'wellbeing-panel-'.$suffix.'@example.test', 'password' => 'SyntheticOnly2026', 'status' => 1, 'password_change_required' => false]);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $user->id, 'role' => 'Counsellor', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $user->assignRole('Counsellor');

        return $user;
    }

    private function web(User $user): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->actingAs($user, 'web');
    }

    private function input(User $user, array $extra): array
    {
        return $extra + ['operation_id' => (string) Str::uuid(), 'expected_account_id' => $user->id, 'expected_school_id' => 1];
    }

    private function entry(array $extra = []): array
    {
        return $this->input($this->professional, $extra + ['revision' => 1, 'kind' => 'note', 'body' => 'Synthetic private clinical narrative', 'happened_at' => '2026-02-10T10:00']);
    }

    public function test_configuration_has_named_choices_without_clinical_body_or_teacher_access(): void
    {
        app(Casework::class)->entry($this->professional, $this->case['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'kind' => 'note', 'body' => 'STRICTLY CLINICAL BODY', 'happened_at' => now()->subMinute()->toISOString()]);
        $this->web($this->superAdmin())->get('/wellbeing?mode=configuration&managed_case='.$this->case['id'])->assertOk()->assertSee('Clinic Professional one')->assertDontSee('STRICTLY CLINICAL BODY')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/wellbeing?mode=clinical&case='.$this->case['id'])->assertForbidden();
        $this->web(User::findOrFail($this->schoolA->ids['tara_user']))->get('/wellbeing')->assertForbidden();
        $this->web(User::findOrFail($this->schoolA->ids['farid_user']))->get('/wellbeing')->assertRedirect(route('login'))->assertDontSee('STRICTLY CLINICAL BODY')->assertSessionMissing('_old_input');
        $this->web($this->professional)->get('/wellbeing?case='.$this->case['id'])->assertOk()->assertSee('STRICTLY CLINICAL BODY')->assertSee('Clinic Professional one');
    }

    public function test_case_note_and_conflict_keep_private_draft_out_of_plain_session_and_use_school_timezone(): void
    {
        $path = '/wellbeing/cases/'.$this->case['id'].'/entries';
        $one = $this->entry();
        $this->web($this->professional)->post($path, $one)->assertRedirect();
        $this->assertSame(8, CaseEntry::firstOrFail()->happened_at->hour);
        $this->assertStringNotContainsString('Synthetic private clinical narrative', DB::table('support_case_entries')->value('body'));
        $conflict = $this->post($path, $this->entry(['body' => 'UNSAVED PRIVATE DRAFT']))->assertConflict()->assertSee('UNSAVED PRIVATE DRAFT')->assertSee($one['body'])->assertSessionMissing('_old_input');
        $this->assertSame(1, CaseEntry::count());
        $operation = $conflict->viewData('operationId');
        $retry = $this->entry(['operation_id' => $operation, 'revision' => 2, 'body' => 'UNSAVED PRIVATE DRAFT', 'review_required' => '1']);
        $this->post($path, $retry)->assertUnprocessable()->assertSessionMissing('_old_input');
        $this->post($path, $retry + ['confirm_current' => '1'])->assertRedirect();
        $this->assertSame(2, CaseEntry::count());
    }

    public function test_malformed_private_inputs_return_422_without_a_rendering_error_or_session_flash(): void
    {
        $path = '/wellbeing/cases/'.$this->case['id'].'/entries';
        $this->web($this->professional)->post($path, $this->entry(['body' => ['invalid'], 'happened_at' => 'not a date']))->assertUnprocessable()->assertSessionMissing('_old_input')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/wellbeing?case='.$this->case['id'].'&calendar_from[]=bad')->assertUnprocessable()->assertSessionMissing('_old_input');
        $this->assertSame(0, CaseEntry::count());
    }

    public function test_appointment_flow_checks_dated_role_and_preserves_private_professional_search_drafts(): void
    {
        $path = '/wellbeing/cases/'.$this->case['id'].'/appointments';
        $input = $this->input($this->professional, ['revision' => 0, 'professional_user_id' => $this->professional->id, 'starts_at' => '2026-02-10T10:00', 'ends_at' => '2026-02-10T10:30', 'location' => 'Private room', 'notes' => 'PRIVATE APPOINTMENT DRAFT', 'status' => 'scheduled']);
        $this->web($this->professional)->post('/wellbeing/cases/'.$this->case['id'].'/professional-choices', $input + ['professional_search' => 'no match'])->assertOk()->assertSee('PRIVATE APPOINTMENT DRAFT')->assertSee('Clinic Professional one')->assertSessionMissing('_old_input');
        $this->post($path, $input)->assertRedirect();
        $this->assertSame(8, Appointment::firstOrFail()->starts_at->hour);
        SchoolMembership::where('school_id', 1)->where('user_id', $this->professional->id)->update(['ends_on' => '2026-02-10']);
        SchoolMembership::create(['school_id' => 2, 'user_id' => $this->professional->id, 'role' => 'Counsellor', 'status' => 'active', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        $this->post($path, array_replace($input, ['operation_id' => (string) Str::uuid(), 'starts_at' => '2026-02-11T10:00', 'ends_at' => '2026-02-11T10:30']))->assertUnprocessable()->assertSee('PRIVATE APPOINTMENT DRAFT')->assertSessionMissing('_old_input');
        $this->assertSame(1, Appointment::count());
    }

    public function test_stale_browser_account_platform_operator_and_unassigned_case_do_not_reflect_private_drafts(): void
    {
        $other = $this->professional('two');
        $path = '/wellbeing/cases/'.$this->case['id'].'/entries';
        $body = $this->entry(['body' => 'STALE SECRET BODY']);
        $this->web($other)->get('/wellbeing?case='.$this->case['id'])->assertNotFound();
        app(WellbeingSettings::class)->assignCase($this->superAdmin(), $this->case['id'], null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'user_id' => $other->id, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Separate legitimate shared caseload']);
        $this->web($other)->post($path, $body)->assertForbidden()->assertDontSee('STALE SECRET BODY')->assertSessionMissing('_old_input');
        PlatformStaffIdentity::create(['user_id' => $this->professional->id, 'active' => true]);
        $this->web($this->professional)->post($path, $body)->assertForbidden()->assertDontSee('STALE SECRET BODY')->assertSessionMissing('_old_input');
        $this->assertSame(0, CaseEntry::count());
    }

    public function test_professional_choices_keep_a_valid_selection_across_search_and_drop_it_after_revocation(): void
    {
        $other = $this->professional('selected');
        app(WellbeingSettings::class)->assignCase($this->superAdmin(), $this->case['id'], null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'user_id' => $other->id, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Explicit second professional']);
        $query = app(WellbeingQueries::class);
        $choices = $query->caseProfessionals($this->professional, $this->case['id'], '2026-02-10', 'no matching name', 1, $other->id);
        $this->assertSame([$other->id], array_column($choices['items'], 'id'));
        $this->assertTrue($choices['selected_available']);
        SchoolMembership::where('school_id', 1)->where('user_id', $other->id)->update(['ends_on' => '2026-02-10']);
        SchoolMembership::create(['school_id' => 2, 'user_id' => $other->id, 'role' => 'Counsellor', 'status' => 'active', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        $choices = $query->caseProfessionals($this->professional, $this->case['id'], '2026-02-11', '', 1, $other->id);
        $this->assertFalse($choices['selected_available']);
        $this->assertNotContains($other->id, array_column($choices['items'], 'id'));
    }

    public function test_staff_can_read_retained_older_incident_threads_without_extending_the_old_api_shape(): void
    {
        $settings = app(WellbeingSettings::class);
        $category = $settings->saveCategory($this->superAdmin(), null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Team safety', 'emergency_guidance' => 'Ask a trusted adult now.', 'active' => true]);
        $settings->assignTeam($this->superAdmin(), $category['id'], null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'user_id' => $this->professional->id, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Explicit triage scope']);
        app(IncidentReporting::class)->submit(['secret' => bin2hex(random_bytes(32)), 'anonymous' => true, 'category_id' => $category['id'], 'body' => 'Original report', 'urgency' => 'routine']);
        $report = IncidentReport::firstOrFail();
        for ($index = 0; $index <= 100; $index++) {
            IncidentThread::create(['incident_report_id' => $report->id, 'staff_user_id' => $this->professional->id, 'author_type' => 'staff', 'internal' => true, 'body' => 'Private retained message '.$index,
                'operation_id' => (string) Str::uuid(), 'request_hash' => hash('sha256', 'synthetic-'.$index)]);
        }
        $response = $this->web($this->professional)->get('/wellbeing?report='.$report->id)->assertOk()->assertSee('Private retained message 100')->assertSee('Read older incident replies');
        $next = $response->viewData('report')['next_before'];
        $this->get('/wellbeing?report='.$report->id.'&before='.$next)->assertOk()->assertSee('Private retained message 0');
        $old = app(IncidentReporting::class)->read($this->professional, $report->id);
        $this->assertCount(100, $old['threads']);
        $this->assertArrayNotHasKey('has_older', $old);
    }

    public function test_historical_year_guard_authorizes_before_reflecting_any_private_draft(): void
    {
        $past = SessionYear::create(['name' => 'Historical synthetic year', 'start_date' => '2024-01-01', 'end_date' => '2024-12-31', 'default' => 0]);
        $path = '/wellbeing/cases/'.$this->case['id'].'/entries';
        $this->web($this->professional)->withSession(['session_year' => $past->id])->post($path, $this->entry(['body' => 'PRIVATE HISTORICAL DRAFT']))
            ->assertStatus(423)->assertSee('PRIVATE HISTORICAL DRAFT')->assertHeader('Cache-Control', 'no-store, private')->assertSessionMissing('_old_input');
        $other = $this->professional('historical');
        $this->web($other)->withSession(['session_year' => $past->id])->post($path, $this->input($other, ['revision' => 1, 'kind' => 'note', 'body' => 'HIDDEN UNAUTHORIZED DRAFT', 'happened_at' => '2026-02-10T10:00']))
            ->assertNotFound()->assertDontSee('HIDDEN UNAUTHORIZED DRAFT')->assertSessionMissing('_old_input');
        $this->assertSame(0, CaseEntry::count());
    }
}
