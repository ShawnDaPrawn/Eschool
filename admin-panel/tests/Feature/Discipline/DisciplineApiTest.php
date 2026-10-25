<?php

declare(strict_types=1);

namespace Tests\Feature\Discipline;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Discipline\DisciplineAcknowledgement;
use App\Models\Discipline\DisciplineOperation;
use App\Models\Discipline\DisciplineRecord;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Services\Discipline\DisciplineFrameworks;
use App\Services\Discipline\DisciplineOperations;
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

class DisciplineApiTest extends TestCase
{
    use UsesMasteryScenario, ValidatesOpenApiResponses;

    private string $prefix = '/api/v2/discipline';

    private int $grade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        Bus::fake();
        config(['cache.default' => 'array']);
        $this->grade = (int) TeachingGroup::findOrFail($this->group)->grade_level_id;
        SchoolModule::updateOrCreate(['module' => 'discipline'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
    }

    private function definition(bool $detention = false): array
    {
        return ['operation_id' => (string) Str::uuid(), 'session_year_id' => $this->year, 'title' => 'School discipline rules', 'expected_revision' => 0,
            'counter_scope' => 'same_infraction', 'reset' => 'school_year', 'rolling_days' => null,
            'levels' => [['code' => 'warning', 'name' => 'School notice', 'threshold' => 1, 'kind' => 'sanction', 'sanctions' => [$detention ? 'detention' : 'notice']],
                ['code' => 'review', 'name' => 'Grade review', 'threshold' => 2, 'kind' => 'sanction', 'sanctions' => ['detention']],
                ['code' => 'hearing', 'name' => 'Principal hearing', 'threshold' => 3, 'kind' => 'hearing', 'sanctions' => []]],
            'sanctions' => [['code' => 'notice', 'name' => 'School notice', 'kind' => 'notice'], ['code' => 'detention', 'name' => 'Supervised detention', 'kind' => 'detention']],
            'infractions' => [['code' => 'late', 'name' => 'Late arrival', 'base_level' => 'warning']]];
    }

    private function configured(bool $detention = false): void
    {
        app(DisciplineFrameworks::class)->save($this->superAdmin(), $this->year, $this->definition($detention));
    }

    private function input(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'session_year_id' => $this->year, 'occurred_on' => '2026-02-10', 'infraction_code' => 'late',
            'description' => 'Family-facing factual observation', 'staff_note' => 'Restricted staff follow-up'];
    }

    private function createUrl(?int $learner = null): string
    {
        return $this->prefix.'/learners/'.($learner ?? $this->schoolA->ids['asha']).'/records';
    }

    private function appointHead(): void
    {
        AcademicLeadership::create(['user_id' => $this->actor()->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade, 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic grade leadership']);
    }

    public function test_framework_configuration_and_private_teacher_record_round_trip_replay_and_scoped_queries(): void
    {
        $definition = $this->definition();
        $saved = $this->actingAs($this->superAdmin())->postJson($this->prefix.'/framework', $definition)->assertOk();
        $this->postJson($this->prefix.'/framework', $definition)->assertOk()->assertExactJson($saved->json());
        $this->getJson($this->prefix.'/configuration')->assertOk()->assertJsonPath('data.can_manage', true)->assertJsonPath('data.today', '2026-02-10');
        $this->getJson($this->prefix.'/framework?session_year_id='.$this->year)->assertOk()->assertJsonPath('data.framework.title', 'School discipline rules');
        $input = $this->input();
        $created = $this->actingAs($this->actor())->postJson($this->createUrl(), $input)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $id = (int) $created->json('data.id');
        $created->assertJsonMissingPath('data.staff_note');
        $this->postJson($this->createUrl(), $input)->assertOk()->assertExactJson($created->json());
        $this->assertSame(1, DisciplineRecord::count());
        $this->assertSame(0, DB::table('idempotency_keys')->count());
        $this->assertStringNotContainsString($input['staff_note'], DB::table('discipline_records')->value('staff_note'));
        $this->assertStringNotContainsString($input['description'], DB::table('discipline_operations')->orderByDesc('id')->value('receipt'));
        $this->getJson($this->prefix.'/learners?session_year_id='.$this->year)->assertOk()->assertJsonPath('data.learners.0.learner_id', $this->schoolA->ids['asha']);
        $this->getJson($this->prefix.'/records?session_year_id='.$this->year)->assertOk()->assertJsonPath('data.records.0.id', $id)->assertJsonMissingPath('data.records.0.staff_note');
        $this->getJson($this->prefix.'/records/'.$id)->assertOk()->assertJsonPath('data.description', $input['description'])->assertJsonPath('data.staff_note', $input['staff_note']);
        $this->getJson($this->prefix.'/records/'.$id.'/history')->assertOk()->assertJsonCount(1, 'data.versions');
        $input['description'] = 'Changed UUID intent';
        $this->postJson($this->createUrl(), $input)->assertConflict();
    }

    public function test_grade_review_and_principal_hearing_are_explicit_and_replay_rechecks_dated_authority(): void
    {
        $this->configured();
        $this->appointHead();
        $this->actingAs($this->actor())->postJson($this->createUrl(), $this->input())->assertOk();
        $second = $this->postJson($this->createUrl(), $this->input())->assertOk()->json('data.id');
        $decision = ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1, 'decision' => 'confirm', 'sanction_code' => 'detention', 'reason' => 'Grade head reviewed the facts'];
        $saved = $this->postJson($this->prefix.'/records/'.$second.'/review', $decision)->assertOk()->assertJsonPath('data.status', 'confirmed');
        $this->postJson($this->prefix.'/records/'.$second.'/review', $decision)->assertOk()->assertExactJson($saved->json());
        $third = $this->postJson($this->createUrl(), $this->input())->assertOk()->json('data.id');
        $decision['operation_id'] = (string) Str::uuid();
        $this->postJson($this->prefix.'/records/'.$third.'/review', $decision)->assertOk()->assertJsonPath('data.status', 'hearing')->assertJsonPath('data.sanction_code', null);
        $hearing = ['operation_id' => (string) Str::uuid(), 'expected_revision' => 2, 'decision' => 'no_action', 'reason' => 'Principal completed a fair hearing'];
        $this->postJson($this->prefix.'/records/'.$third.'/hearing', $hearing)->assertForbidden();
        AcademicLeadership::create(['user_id' => $this->superAdmin()->id, 'kind' => 'principal', 'grade_level_id' => null, 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Appointed principal']);
        $this->actingAs($this->superAdmin())->postJson($this->prefix.'/records/'.$third.'/hearing', $hearing)->assertOk()->assertJsonPath('data.hearing_decision', 'no_action');
        AcademicLeadership::where('user_id', $this->actor()->id)->update(['ends_on' => '2026-02-09']);
        $this->actingAs($this->actor())->postJson($this->prefix.'/records/'.$second.'/review', $decision)->assertForbidden();
    }

    public function test_family_reads_only_published_owned_projection_and_acknowledgement_never_grants_staff_access(): void
    {
        $this->configured();
        $one = $this->actingAs($this->actor())->postJson($this->createUrl(), $this->input())->assertOk()->json('data.id');
        $this->postJson($this->createUrl(), $this->input())->assertOk();
        $guardian = $this->actor('farid');
        $guardian->forceFill(['email_verified_at' => now()])->save();
        $family = $this->prefix.'/family/learners/'.$this->schoolA->ids['asha'].'?session_year_id='.$this->year;
        $this->actingAs($guardian)->getJson($family)->assertOk()->assertJsonCount(1, 'data.records')->assertJsonMissingPath('data.records.0.staff_note');
        $input = ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1];
        $ack = $this->postJson($this->prefix.'/family/records/'.$one.'/acknowledge', $input)->assertOk();
        $this->postJson($this->prefix.'/family/records/'.$one.'/acknowledge', $input)->assertOk()->assertExactJson($ack->json());
        $this->assertSame(1, DisciplineAcknowledgement::count());
        $this->getJson($this->prefix.'/records/'.$one)->assertForbidden();
        $this->getJson($this->prefix.'/family/learners/'.$this->schoolA->ids['ben'].'?session_year_id='.$this->year)->assertNotFound();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $guardian->id], ['status' => 'ended']);
        $this->postJson($this->prefix.'/family/records/'.$one.'/acknowledge', $input)->assertNotFound();
    }

    public function test_detention_roster_capture_correction_cancel_and_unknown_attendance_are_freshly_scoped(): void
    {
        $this->configured(true);
        $this->appointHead();
        $record = $this->actingAs($this->actor())->postJson($this->createUrl(), $this->input())->assertOk()->json('data.id');
        $input = ['operation_id' => (string) Str::uuid(), 'session_year_id' => $this->year, 'grade_level_id' => $this->grade, 'supervisor_id' => $this->actor('theo')->id,
            'on_date' => '2026-02-10', 'starts_at' => '15:00', 'ends_at' => '16:00', 'capacity' => 1, 'location' => 'Synthetic supervised room'];
        $this->getJson($this->prefix.'/supervisors?session_year_id='.$this->year.'&grade_level_id='.$this->grade.'&on_date=2026-02-10')->assertOk();
        $created = $this->postJson($this->prefix.'/detentions', $input)->assertOk();
        $id = (int) $created->json('data.id');
        $this->postJson($this->prefix.'/detentions', $input)->assertOk()->assertExactJson($created->json());
        $entry = ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1, 'record_id' => $record];
        $e = $this->postJson($this->prefix.'/detentions/'.$id.'/entries', $entry)->assertOk()->json('data.entry_id');
        $this->getJson($this->prefix.'/detentions?session_year_id='.$this->year)->assertOk()->assertJsonPath('data.sessions.0.id', $id);
        $this->actingAs($this->actor('theo'))->getJson($this->prefix.'/detentions/'.$id)->assertOk()->assertJsonPath('data.entries.0.attendance', null);
        $attendance = ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1, 'expected_session_revision' => 2, 'attendance' => 'absent', 'reason' => 'First supervisor capture'];
        $this->postJson($this->prefix.'/detentions/'.$id.'/entries/'.$e.'/attendance', $attendance)->assertOk();
        $this->actingAs($this->actor('mona'))->getJson($this->prefix.'/detentions/'.$id)->assertForbidden();
        $this->actingAs($this->actor())->postJson($this->prefix.'/detentions/'.$id.'/cancel', ['operation_id' => (string) Str::uuid(), 'expected_revision' => 3, 'reason' => 'School rescheduled supervision'])->assertOk()->assertJsonPath('data.status', 'cancelled');
    }

    public function test_private_writes_refuse_query_text_account_school_module_password_and_platform_changes(): void
    {
        $this->configured();
        $input = $this->input();
        $this->actingAs($this->actor())->postJson($this->createUrl().'?description=URL_SECRET', $input)->assertUnprocessable()->assertDontSee('URL_SECRET');
        $this->postJson($this->createUrl(), $input, ['X-Account-Id' => (string) $this->actor('theo')->id])->assertForbidden();
        $this->postJson($this->createUrl($this->schoolB->ids['asha']), $input)->assertNotFound();
        $id = $this->postJson($this->createUrl(), $input)->assertOk()->json('data.id');
        $this->actingAs($this->actor('theo'))->getJson($this->prefix.'/records/'.$id)->assertNotFound();
        $this->actingAs($this->actor())->postJson($this->createUrl(), $input, ['X-School-Id' => (string) self::SCHOOL_B_ID])->assertForbidden();
        $this->actor()->forceFill(['password_change_required' => true])->save();
        $this->postJson($this->createUrl(), $input)->assertForbidden();
        $this->actor()->forceFill(['password_change_required' => false])->save();
        PlatformStaffIdentity::create(['user_id' => $this->actor()->id]);
        $this->postJson($this->createUrl(), $input)->assertForbidden();
        PlatformStaffIdentity::query()->delete();
        SchoolModule::where('module', 'discipline')->update(['enabled' => false]);
        app(ModuleGate::class)->forget();
        $this->postJson($this->createUrl(), $input)->assertNotFound();
        $this->assertSame(1, DisciplineRecord::count());
    }

    public function test_private_receipt_rolls_back_when_callback_replaces_current_school(): void
    {
        $before = DisciplineOperation::count();
        try {
            app(DisciplineOperations::class)->once($this->actor(), 'context-test', ['operation_id' => (string) Str::uuid()], fn () => null,
                function () {
                    app(CurrentSchool::class)->set(self::SCHOOL_B_ID);

                    return ['id' => 999];
                });
            $this->fail('Replaced school context must refuse receipt settlement.');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertSame($before, DisciplineOperation::count());
        $this->assertSame(1, app(CurrentSchool::class)->id());
    }

    public function test_record_queue_pagination_does_not_count_cases_after_teaching_access_ends(): void
    {
        $this->configured();
        $id = $this->actingAs($this->actor())->postJson($this->createUrl(), $this->input())->assertOk()->json('data.id');
        $seed = DisciplineRecord::findOrFail($id);
        for ($i = 0; $i < 50; $i++) {
            $seed->replicate()->save();
        }
        $url = $this->prefix.'/records?session_year_id='.$this->year;
        $this->getJson($url)->assertOk()->assertJsonPath('data.last_page', 2);
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        $this->getJson($url)->assertOk()->assertJsonCount(0, 'data.records')->assertJsonPath('data.last_page', 1);
        $this->actor()->syncRoles([SchoolRoles::COUNSELLOR]);
        $this->getJson($this->prefix.'/configuration')->assertForbidden();
    }

    public function test_readonly_configuration_explicitly_retains_reads_and_withholds_write_capability(): void
    {
        $this->configured();
        SchoolModule::where('module', 'discipline')->update(['read_only' => true]);
        app(ModuleGate::class)->forget();
        $this->actingAs($this->actor())->getJson($this->prefix.'/configuration')->assertOk()->assertJsonPath('data.writable', false);
        $this->postJson($this->createUrl(), $this->input())->assertForbidden();
        $this->assertSame(0, DisciplineRecord::count());
    }

    public function test_family_bootstrap_discovers_only_owned_published_years_and_current_year(): void
    {
        $this->configured();
        $one = $this->actingAs($this->actor())->postJson($this->createUrl(), $this->input())->assertOk()->json('data.id');
        $old = SessionYear::create(['name' => 'Owned published history', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        $privateYear = SessionYear::create(['name' => 'Unpublished private history', 'start_date' => '2024-01-01', 'end_date' => '2024-12-31']);
        $otherYear = SessionYear::create(['name' => 'Another learner history', 'start_date' => '2023-01-01', 'end_date' => '2023-12-31']);
        $source = DisciplineRecord::findOrFail($one);
        $source->replicate()->fill(['session_year_id' => $old->id])->save();
        $source->replicate()->fill(['session_year_id' => $privateYear->id, 'published_at' => null])->save();
        $source->replicate()->fill(['session_year_id' => $otherYear->id, 'learner_id' => $this->schoolA->ids['ben']])->save();
        $url = $this->prefix.'/family/learners/'.$this->schoolA->ids['asha'];
        $this->actingAs($this->actor('farid'))->getJson($url)->assertOk()->assertJsonPath('data.session_year_id', $this->year)
            ->assertJsonCount(2, 'data.years')->assertJsonFragment(['name' => 'Owned published history'])->assertDontSee('Unpublished private history')->assertDontSee('Another learner history');
        $this->getJson($url.'?session_year_id='.$old->id)->assertOk()->assertJsonCount(1, 'data.records');
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->actor('farid')->id], ['status' => 'ended']);
        $this->getJson($url)->assertNotFound()->assertDontSee('Owned published history');
    }
}
