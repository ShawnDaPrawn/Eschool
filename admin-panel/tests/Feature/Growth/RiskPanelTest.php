<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Models\Academics\AcademicLeadership;
use App\Models\Growth\Intervention;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Services\Growth\Interventions;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

class RiskPanelTest extends TestCase
{
    use UsesMasteryScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        Bus::fake();
        SchoolModule::updateOrCreate(['module' => 'early_warning'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
    }

    private function input(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'expected_account_id' => $this->actor()->id, 'expected_school_id' => 1, 'mode' => 'queue', 'session_year_id' => $this->year,
            'owner_choice' => $this->actor()->id.':academic', 'action' => 'Private school follow-up', 'started_on' => '2026-02-10', 'review_on' => '2026-02-20'];
    }

    private function url(): string
    {
        return '/risk-interventions?session_year_id='.$this->year.'&learner_id='.$this->schoolA->ids['asha'];
    }

    private function principal(): void
    {
        AcademicLeadership::create(['user_id' => $this->superAdmin()->id, 'kind' => 'principal', 'grade_level_id' => null, 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic principal']);
    }

    public function test_teacher_authors_escaped_owned_action_and_reviews_it_without_session_flash(): void
    {
        $input = $this->input();
        $input['action'] = '<script>window.privateIntervention=1</script>';
        $this->actingAs($this->actor())->get($this->url())->assertOk()->assertSee('Plan an owned follow-up')->assertHeader('Cache-Control', 'no-store, private');
        $saved = $this->post('/risk-interventions/learners/'.$this->schoolA->ids['asha'].'/interventions', $input)->assertRedirect();
        $this->get($saved->headers->get('Location'))->assertOk()->assertSee('&lt;script&gt;window.privateIntervention=1&lt;/script&gt;', false)->assertDontSee('<script>window.privateIntervention=1</script>', false);
        $id = (int) Intervention::sole()->id;
        $review = ['operation_id' => (string) Str::uuid(), 'expected_account_id' => $this->actor()->id, 'expected_school_id' => 1, 'mode' => 'queue', 'session_year_id' => $this->year,
            'expected_revision' => 1, 'action' => 'Updated owner action', 'outcome' => 'Reviewed outcome', 'state' => 'completed', 'review_on' => '2026-02-20'];
        $saved = $this->post('/risk-interventions/interventions/'.$id, $review)->assertRedirect();
        $this->get($saved->headers->get('Location'))->assertOk()->assertSee('Reviewed outcome')->assertSee('read-only history');
        $this->assertFalse(session()->has('_old_input'));
    }

    public function test_conflicts_preserve_only_direct_draft_and_require_current_revision_confirmation(): void
    {
        $r = app(Interventions::class)->create($this->actor(), $this->schoolA->ids['asha'], $this->year, ['owner_id' => $this->actor()->id, 'action' => 'Current saved action', 'started_on' => '2026-02-10', 'review_on' => '2026-02-20']);
        app(Interventions::class)->update($this->actor(), $r['id'], ['expected_revision' => 1, 'action' => 'A newer saved action', 'review_on' => '2026-02-25', 'state' => 'active']);
        $data = $this->input();
        unset($data['owner_choice'],$data['started_on']);
        $data += ['expected_revision' => 1, 'state' => 'active', 'outcome' => null];
        $data['action'] = 'My unflashed stale draft';
        $this->actingAs($this->actor())->post('/risk-interventions/interventions/'.$r['id'], $data)->assertConflict()->assertSee('My unflashed stale draft')->assertSee('A newer saved action')->assertSee('confirm_current');
        $this->assertFalse(session()->has('_old_input'));
        $this->assertSame(2, Intervention::sole()->revision);
    }

    public function test_principal_sees_professional_handoff_metadata_and_names_without_selecting_body(): void
    {
        $this->principal();
        $this->actor()->assignRole(SchoolRoles::COUNSELLOR);
        $s = app(Interventions::class);
        $s->assign($this->superAdmin(), $this->schoolA->ids['asha'], $this->year, $this->actor()->id, ['starts_on' => '2026-02-10', 'ends_on' => '2026-12-31', 'expected_revision' => 0]);
        $row = $s->create($this->actor(), $this->schoolA->ids['asha'], $this->year, ['owner_id' => $this->actor()->id, 'action' => 'Professional owner text must remain hidden', 'started_on' => '2026-02-10', 'review_on' => '2026-02-20']);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->superAdmin())->get('/risk-interventions?session_year_id='.$this->year)->assertOk()->assertSee('Change the assigned owner')->assertDontSee('Professional owner text must remain hidden');
        foreach (DB::getQueryLog() as $q) {
            if (str_starts_with(strtolower($q['query']), 'select') && str_contains($q['query'], '`interventions`')) {
                $this->assertStringNotContainsString('`action`', $q['query']);
                $this->assertStringNotContainsString('`outcome`', $q['query']);
            }
        }
        DB::disableQueryLog();
        $this->get('/risk-interventions?mode=assignments&intervention='.$row['id'].'&session_year_id='.$this->year)->assertOk()->assertSee('Confirm owner handoff')->assertDontSee('Professional owner text must remain hidden');
        $this->get('/risk-interventions?intervention='.$row['id'].'&session_year_id='.$this->year)->assertForbidden();
    }

    public function test_wrong_school_role_cookie_account_and_private_query_cannot_save_or_reflect_body(): void
    {
        $data = $this->input();
        $url = '/risk-interventions/learners/'.$this->schoolA->ids['asha'].'/interventions';
        $this->actingAs($this->actor('farid'))->get('/risk-interventions')->assertForbidden();
        $data['expected_account_id'] = $this->actor('theo')->id;
        $this->actingAs($this->actor())->post($url, $data)->assertForbidden()->assertDontSee('Private school follow-up');
        $data = $this->input();
        $this->post($url.'?action=URL_SECRET', $data)->assertUnprocessable()->assertDontSee('URL_SECRET');
        $this->post('/risk-interventions/learners/'.$this->schoolB->ids['asha'].'/interventions', $data)->assertNotFound();
        $this->assertSame(0, Intervention::count());
        $this->assertFalse(session()->has('_old_input'));
    }

    public function test_historical_refusal_checks_role_before_private_draft_reflection(): void
    {
        $old = SessionYear::create(['name' => 'Retained year', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        $data = $this->input();
        $data['action'] = 'Own historical refusal draft';
        $url = '/risk-interventions/learners/'.$this->schoolA->ids['asha'].'/interventions';
        $this->actingAs($this->actor())->withSession(['session_year' => $old->id])->post($url, $data)->assertStatus(423)->assertSee('Own historical refusal draft')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertFalse(session()->has('_old_input'));
        $this->actor()->syncRoles([SchoolRoles::FACILITATOR]);
        $this->post($url, $data)->assertForbidden()->assertDontSee('Own historical refusal draft');
    }
}
