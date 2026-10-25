<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\TeachingGroup;
use App\Models\Growth\Intervention;
use App\Models\Growth\RiskOperation;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolModule;
use App\Services\Growth\Interventions;
use App\Services\Growth\RiskOperations;
use App\Services\Growth\RiskRecompute;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesMasteryScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class RiskApiTest extends TestCase
{
    use UsesMasteryScenario, ValidatesOpenApiResponses;

    private string $prefix = '/api/v2/growth/risk';

    private int $grade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        Bus::fake();
        config(['cache.default' => 'array']);
        $this->grade = (int) TeachingGroup::findOrFail($this->group)->grade_level_id;
        SchoolModule::updateOrCreate(['module' => 'early_warning'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
    }

    private function input(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'session_year_id' => $this->year, 'owner_id' => $this->actor()->id,
            'action' => 'Private current-owner intervention action', 'started_on' => '2026-02-10', 'review_on' => '2026-02-20'];
    }

    private function createUrl(?int $learner = null): string
    {
        return $this->prefix.'/learners/'.($learner ?? $this->schoolA->ids['asha']).'/interventions';
    }

    private function principal(): void
    {
        AcademicLeadership::create(['user_id' => $this->superAdmin()->id, 'kind' => 'principal', 'grade_level_id' => null, 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic principal']);
    }

    private function appointGradeHead(): void
    {
        AcademicLeadership::create(['user_id' => $this->actor()->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade, 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic grade leadership']);
    }

    public function test_teacher_owned_workflow_replays_private_changes_and_retains_immutable_history(): void
    {
        $input = $this->input();
        $input['action'] = str_repeat('é', 20000);
        $created = $this->actingAs($this->actor())->postJson($this->createUrl(), $input)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $id = $created->json('data.id');
        $created->assertJsonMissingPath('data.action');
        $this->postJson($this->createUrl(), $input)->assertOk()->assertExactJson($created->json());
        $this->assertSame(1, Intervention::count());
        $this->assertSame(1, RiskOperation::count());
        $this->assertSame(0, DB::table('idempotency_keys')->count());
        $this->getJson($this->prefix.'/interventions?session_year_id='.$this->year)->assertOk()->assertJsonPath('data.interventions.0.can_read_body', true)->assertJsonMissingPath('data.interventions.0.action');
        $this->getJson($this->prefix.'/learners/'.$this->schoolA->ids['asha'].'/owners?session_year_id='.$this->year)->assertOk()->assertJsonCount(1, 'data.owners')->assertJsonPath('data.owners.0.id', $this->actor()->id);
        $this->assertStringNotContainsString($input['action'], DB::table('interventions')->value('action'));
        $this->getJson($this->prefix.'/interventions/'.$id)->assertOk()->assertJsonPath('data.action', $input['action']);
        $savedInput = ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1, 'action' => 'Private follow-up',
            'review_on' => '2026-02-25', 'state' => 'completed', 'outcome' => 'Private owned outcome'];
        $saved = $this->putJson($this->prefix.'/interventions/'.$id, $savedInput)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->putJson($this->prefix.'/interventions/'.$id, $savedInput)->assertOk()->assertExactJson($saved->json());
        $this->getJson($this->prefix.'/interventions/'.$id.'/history')->assertOk()->assertJsonCount(2, 'data.versions')->assertJsonPath('data.versions.0.record.action', 'Private follow-up');
        $savedInput['operation_id'] = (string) Str::uuid();
        $this->putJson($this->prefix.'/interventions/'.$id, $savedInput)->assertConflict();
        $input['action'] = 'Changed intent';
        $this->postJson($this->createUrl(), $input)->assertConflict();
    }

    public function test_wrong_role_school_owner_and_cookie_account_cannot_read_or_replay(): void
    {
        $input = $this->input();
        $id = $this->actingAs($this->actor())->postJson($this->createUrl(), $input)->assertOk()->json('data.id');
        foreach (['asha', 'farid', 'theo'] as $name) {
            $response = $this->actingAs($this->actor($name))->getJson($this->prefix.'/interventions/'.$id);
            $this->assertContains($response->status(), [403, 404]);
            $response->assertJsonMissing(['action' => $input['action']])->assertHeader('Cache-Control', 'no-store, private');
        }
        $this->actingAs($this->actor())->postJson($this->createUrl($this->schoolB->ids['asha']), $this->input())->assertNotFound();
        $this->postJson($this->createUrl(), $input, ['X-Account-Id' => (string) $this->actor('theo')->id])->assertForbidden();
        $this->actor()->syncRoles([SchoolRoles::FACILITATOR]);
        $this->postJson($this->createUrl(), $input)->assertNotFound();
        $this->getJson($this->prefix.'/interventions/'.$id)->assertNotFound();
    }

    public function test_classroom_signals_are_scoped_and_professional_list_requires_a_separate_grant(): void
    {
        $this->capture('4.00', '4.00');
        app(RiskRecompute::class)->grade($this->year, $this->grade);
        $this->actingAs($this->actor())->getJson($this->prefix.'/configuration')->assertOk()->assertJsonPath('data.can_manage', false);
        $this->getJson($this->prefix.'/groups/'.$this->group)->assertOk()->assertJsonPath('data.learners.0.signals.0.source', 'attendance');
        $this->getJson($this->prefix.'/signals?session_year_id='.$this->year)->assertForbidden();
        $this->appointGradeHead();
        $this->getJson($this->prefix.'/signals?session_year_id='.$this->year)->assertOk()->assertJsonCount(4, 'data.learners.0.signals');
        AcademicLeadership::where('user_id', $this->actor()->id)->update(['ends_on' => '2026-02-09']);
        $this->getJson($this->prefix.'/signals?session_year_id='.$this->year)->assertForbidden();
        $this->getJson($this->prefix.'/groups/999999')->assertNotFound();
    }

    public function test_principal_metadata_grants_revoke_and_professional_replay_are_fresh_and_separate(): void
    {
        $this->principal();
        $this->actor()->assignRole(SchoolRoles::COUNSELLOR);
        $base = $this->prefix.'/learners/'.$this->schoolA->ids['asha'].'/assignments/'.$this->actor()->id;
        $grant = ['operation_id' => (string) Str::uuid(), 'session_year_id' => $this->year, 'expected_revision' => 0,
            'starts_on' => '2026-02-10', 'ends_on' => '2026-12-31'];
        $granted = $this->actingAs($this->superAdmin())->putJson($base, $grant)->assertOk();
        $this->putJson($base, $grant)->assertOk()->assertExactJson($granted->json());
        $this->getJson($this->prefix.'/learners?session_year_id='.$this->year)->assertOk();
        $this->getJson($this->prefix.'/learners/'.$this->schoolA->ids['asha'].'/professionals?session_year_id='.$this->year)->assertOk()->assertJsonPath('data.staff.0.id', $this->actor()->id);
        $this->getJson($this->prefix.'/learners/'.$this->schoolA->ids['asha'].'/assignments?session_year_id='.$this->year)->assertOk()->assertJsonPath('data.assignments.0.revoked', false);
        $input = $this->input();
        $id = $this->actingAs($this->actor())->postJson($this->createUrl(), $input)->assertOk()->json('data.id');
        $this->actingAs($this->superAdmin())->getJson($this->prefix.'/interventions/'.$id)->assertForbidden();
        $end = ['operation_id' => (string) Str::uuid(), 'session_year_id' => $this->year, 'expected_revision' => 1];
        $ended = $this->postJson($base.'/revoke', $end)->assertOk();
        $this->postJson($base.'/revoke', $end)->assertOk()->assertExactJson($ended->json());
        $this->actingAs($this->actor())->postJson($this->createUrl(), $input)->assertNotFound();
        // Original professional authority must survive before receipt replay.
        $this->getJson($this->prefix.'/interventions/'.$id)->assertNotFound();
        $this->getJson($this->prefix.'/interventions/'.$id.'/history')->assertNotFound();
    }

    public function test_private_query_parameters_are_rejected_without_body_mutation_or_session_flash(): void
    {
        $input = $this->input();
        $this->actingAs($this->actor())->postJson($this->createUrl().'?action=URL-secret', $input)->assertUnprocessable()->assertDontSee('URL-secret');
        $this->assertSame(0, Intervention::count());
        $this->assertSame(0, RiskOperation::count());
        $this->assertFalse(session()->has('_old_input'));
    }

    public function test_module_password_platform_and_context_replacement_fail_closed(): void
    {
        $input = $this->input();
        $this->actor()->forceFill(['password_change_required' => true])->save();
        $this->actingAs($this->actor())->postJson($this->createUrl(), $input)->assertForbidden();
        $this->actor()->forceFill(['password_change_required' => false])->save();
        PlatformStaffIdentity::create(['user_id' => $this->actor()->id]);
        $this->postJson($this->createUrl(), $input)->assertForbidden();
        PlatformStaffIdentity::query()->delete();
        SchoolModule::where('module', 'early_warning')->update(['enabled' => false]);
        app(ModuleGate::class)->forget();
        $this->getJson($this->prefix.'/configuration')->assertNotFound();
        SchoolModule::where('module', 'early_warning')->update(['enabled' => true]);
        app(ModuleGate::class)->forget();
        try {
            app(RiskOperations::class)->once($this->actor(), 'test.context', ['operation_id' => (string) Str::uuid()], fn () => null, function () {
                app(CurrentSchool::class)->set(2);

                return ['private' => 'must not persist'];
            });
            $this->fail('A replaced context must fail.');
        } catch (HttpExceptionInterface $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        $this->assertSame(1, app(CurrentSchool::class)->id());
        $this->assertSame(0, RiskOperation::count());
    }

    public function test_principal_owner_handoff_and_settings_are_replayable_without_private_body_access(): void
    {
        $this->principal();
        $this->actor()->assignRole(SchoolRoles::COUNSELLOR);
        $this->actor('theo')->assignRole(SchoolRoles::COUNSELLOR);
        $dates = ['starts_on' => '2026-02-10', 'ends_on' => '2026-12-31', 'expected_revision' => 0];
        foreach ([$this->actor(), $this->actor('theo')] as $person) {
            app(Interventions::class)->assign($this->superAdmin(), $this->schoolA->ids['asha'], $this->year, $person->id, $dates);
        }
        $input = $this->input();
        $id = $this->actingAs($this->actor())->postJson($this->createUrl(), $input)->assertOk()->json('data.id');
        $this->actingAs($this->superAdmin())->getJson($this->prefix.'/learners/'.$this->schoolA->ids['asha'].'/owners?session_year_id='.$this->year.'&intervention_id='.$id)
            ->assertOk()->assertJsonPath('data.owners.0.authority', 'professional');
        $change = ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1, 'owner_id' => $this->actor('theo')->id, 'reason' => 'Current dated professional handoff'];
        DB::flushQueryLog();
        DB::enableQueryLog();
        $handed = $this->postJson($this->prefix.'/interventions/'.$id.'/owner', $change)->assertOk()->assertJsonMissingPath('data.action');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        foreach ($queries as $query) {
            if (str_starts_with(strtolower($query['query']), 'select') && str_contains($query['query'], '`interventions`')) {
                $this->assertStringNotContainsString('`action`', $query['query']);
                $this->assertStringNotContainsString('`outcome`', $query['query']);
            }
        }
        $this->postJson($this->prefix.'/interventions/'.$id.'/owner', $change)->assertOk()->assertExactJson($handed->json());
        $this->actingAs($this->actor())->getJson($this->prefix.'/interventions/'.$id)->assertNotFound();
        $this->actingAs($this->actor('theo'))->getJson($this->prefix.'/interventions/'.$id)->assertOk()->assertJsonPath('data.action', $input['action']);
        $this->getJson($this->prefix.'/interventions/'.$id.'/history')->assertOk()->assertJsonPath('data.versions.0.record.kind', 'owner_change');
        $settings = ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0, 'academic_threshold_bp' => 6500,
            'minimum_coverage_bp' => 2500, 'absence_threshold_bp' => 1000, 'minimum_registers' => 10];
        $saved = $this->actingAs($this->superAdmin())->putJson($this->prefix.'/settings', $settings)->assertOk()->assertJsonPath('data.revision', 1);
        $this->putJson($this->prefix.'/settings', $settings)->assertOk()->assertExactJson($saved->json());
        $this->actingAs($this->actor())->putJson($this->prefix.'/settings', $settings)->assertForbidden();
    }

    public function test_non_leadership_staff_cannot_assign_another_owner_or_discover_professional_grants(): void
    {
        $this->principal();
        $this->actor('theo')->assignRole(SchoolRoles::COUNSELLOR);
        app(Interventions::class)->assign($this->superAdmin(), $this->schoolA->ids['asha'], $this->year, $this->actor('theo')->id,
            ['starts_on' => '2026-02-10', 'ends_on' => '2026-12-31', 'expected_revision' => 0]);
        $data = $this->input();
        $data['owner_id'] = $this->actor('theo')->id;
        $this->actingAs($this->actor())->postJson($this->createUrl(), $data)->assertForbidden();
        $this->getJson($this->prefix.'/learners/'.$this->schoolA->ids['asha'].'/owners?session_year_id='.$this->year)->assertOk()
            ->assertJsonCount(1, 'data.owners')->assertJsonPath('data.owners.0.id', $this->actor()->id);
        $this->getJson($this->prefix.'/learners/'.$this->schoolA->ids['asha'].'/assignments?session_year_id='.$this->year)->assertForbidden();
        $this->assertSame(0, Intervention::count());
    }
}
