<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Jobs\Growth\EvaluateRisk;
use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\AssessmentPlan;
use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\AttendanceRegister;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\ClassSchool;
use App\Models\Growth\Intervention;
use App\Models\Growth\InterventionVersion;
use App\Models\Growth\RiskCaseload;
use App\Models\Growth\RiskObservation;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Services\Academics\TeachingGroups;
use App\Services\Growth\Interventions;
use App\Services\Growth\LearnerNotes;
use App\Services\Growth\RiskAuthority;
use App\Services\Growth\RiskMetrics;
use App\Services\Growth\RiskRecompute;
use App\Services\Growth\RiskSettingsService;
use App\Services\Growth\RiskViews;
use App\Services\Leadership\LeadershipSignals;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

class RiskInterventionsTest extends TestCase
{
    use UsesMasteryScenario;

    private int $grade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        Bus::fake();
        $this->grade = (int) TeachingGroup::findOrFail($this->group)->grade_level_id;
        SchoolModule::updateOrCreate(['module' => 'early_warning'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
    }

    private function appointGradeHead(): void
    {
        AcademicLeadership::create(['user_id' => $this->actor()->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade,
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic grade leadership']);
    }

    private function principal(): void
    {
        AcademicLeadership::create(['user_id' => $this->superAdmin()->id, 'kind' => 'principal', 'grade_level_id' => null,
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic principal']);
    }

    private function denied(callable $call, int $expected): void
    {
        try {
            $call();
            $this->fail('Private access must be refused.');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($expected, $e->getStatusCode());
        } catch (ModelNotFoundException) {
            $this->assertSame($expected, 404);
        }
    }

    private function input(string $action = 'Private school intervention'): array
    {
        return ['owner_id' => $this->actor()->id, 'action' => $action, 'started_on' => '2026-02-10', 'review_on' => '2026-02-20'];
    }

    public function test_observations_are_dated_immutable_and_unknown_sources_are_not_zero(): void
    {
        $this->capture('4.00', '4.00');
        $risk = app(RiskRecompute::class);
        $this->assertGreaterThan(0, $risk->grade($this->year, $this->grade));
        $risk->grade($this->year, $this->grade);
        $rows = RiskObservation::where('learner_id', $this->schoolA->ids['asha'])->get()->keyBy('source');
        $this->assertCount(4, $rows);
        $this->assertSame(25, $rows['academic']->score);
        $this->assertSame(1, $rows['academic']->signal_count);
        $this->assertSame($this->grade, (int) $rows['academic']->grade_level_id);
        $this->assertSame('2026-02-10', $rows['academic']->as_of->toDateString());
        foreach (['attendance', 'discipline', 'wellbeing'] as $source) {
            $this->assertNull($rows[$source]->score);
        }
        $this->assertArrayNotHasKey('evidence', $rows['academic']->toArray());
        $this->capture('8.00', '8.00', 1);
        $risk->grade($this->year, $this->grade);
        $this->assertSame(2, RiskObservation::where('learner_id', $this->schoolA->ids['asha'])->where('source', 'academic')->count());
        $latest = app(RiskViews::class)->classroom($this->actor(), $this->group);
        $this->assertSame(0, $latest['learners'][0]['observed_score']);
        $this->assertSame(['academic', 'attendance'], collect($latest['learners'][0]['signals'])->pluck('source')->sort()->values()->all());
        $this->expectException(\LogicException::class);
        $rows['academic']->update(['score' => 0]);
    }

    public function test_missing_published_or_current_subject_evidence_never_becomes_a_risk_zero(): void
    {
        $risk = app(RiskRecompute::class);
        $risk->grade($this->year, $this->grade);
        $this->assertNull(RiskObservation::where('source', 'academic')->latest('id')->firstOrFail()->score);
        $this->capture('0.00', '0.00');
        AssessmentPlan::query()->update(['state' => 'draft']);
        $risk->grade($this->year, $this->grade);
        $this->assertNull(RiskObservation::where('source', 'academic')->latest('id')->firstOrFail()->score);
    }

    public function test_attendance_uses_dated_canonical_homeroom_rows_and_excludes_holidays_and_wrong_rosters(): void
    {
        $learner = $this->schoolA->ids['asha'];
        for ($i = 0; $i < 13; $i++) {
            $register = AttendanceRegister::create(['session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'],
                'timetable_lesson_id' => null, 'grade_level_id' => $this->grade, 'on_date' => now()->subDays($i)->toDateString(),
                'scope_key' => 'homeroom:'.$this->schoolA->ids['section_a'], 'label' => 'Synthetic daily register']);
            AttendanceEntry::create(['attendance_register_id' => $register->id, 'learner_id' => $learner,
                'state' => $i === 12 ? 'holiday' : ($i < 2 ? 'absent' : 'present'), 'source' => 'manual', 'captured_by' => $this->actor()->id]);
        }
        $wrong = AttendanceRegister::create(['session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_c'],
            'timetable_lesson_id' => null, 'grade_level_id' => $this->grade, 'on_date' => '2026-02-10',
            'scope_key' => 'homeroom:'.$this->schoolA->ids['section_c'], 'label' => 'Wrong roster']);
        AttendanceEntry::create(['attendance_register_id' => $wrong->id, 'learner_id' => $learner, 'state' => 'absent', 'source' => 'manual', 'captured_by' => $this->actor()->id]);
        app(RiskRecompute::class)->grade($this->year, $this->grade);
        $row = RiskObservation::where('learner_id', $learner)->where('source', 'attendance')->sole();
        $this->assertSame(4, $row->signal_count);
        $this->assertSame(12, $row->evidence['recorded_days']);
        $this->assertSame(16, $row->score);
    }

    public function test_grade_head_and_explicit_professional_lists_exclude_family_and_ordinary_teacher(): void
    {
        $this->capture('4.00', '4.00');
        app(RiskRecompute::class)->grade($this->year, $this->grade);
        $views = app(RiskViews::class);
        $this->denied(fn () => $views->list($this->actor('farid'), $this->year), 403);
        $this->denied(fn () => $views->list($this->actor(), $this->year), 403);
        $this->appointGradeHead();
        $this->assertNotEmpty($views->list($this->actor(), $this->year)['learners']);
        AcademicLeadership::query()->where('kind', 'grade_head')->update(['ends_on' => '2026-02-09']);
        $this->denied(fn () => $views->list($this->actor(), $this->year), 403);
        $this->denied(fn () => $views->classroom($this->actor('theo'), $this->group), 403);
    }

    public function test_owned_interventions_encrypt_history_preserve_scope_and_complete_with_an_outcome(): void
    {
        $service = app(Interventions::class);
        $body = str_repeat('é', 20000);
        $created = $service->create($this->actor(), $this->schoolA->ids['asha'], $this->year, $this->input($body));
        $this->assertArrayNotHasKey('action', $created);
        $this->assertSame('academic', $created['owner_authority']);
        $this->assertSame($body, $service->read($this->actor(), $created['id'])['action']);
        $this->assertStringNotContainsString($body, DB::table('interventions')->value('action'));
        $this->assertGreaterThan(65535, strlen(DB::table('intervention_versions')->value('payload')));
        $this->assertArrayNotHasKey('action', Intervention::sole()->toArray());
        $this->assertArrayNotHasKey('payload', InterventionVersion::sole()->toArray());
        $service->update($this->actor(), $created['id'], ['expected_revision' => 1, 'action' => 'Revised school action', 'review_on' => '2026-02-25',
            'state' => 'completed', 'outcome' => 'School action reviewed and completed']);
        $this->assertSame(2, $service->read($this->actor(), $created['id'])['revision']);
        $this->assertCount(2, $service->history($this->actor(), $created['id']));
        $this->denied(fn () => $service->update($this->actor(), $created['id'], ['expected_revision' => 2, 'action' => 'Attempt to reopen',
            'review_on' => '2026-02-25', 'state' => 'active', 'outcome' => null]), 409);
    }

    public function test_wrong_owner_school_role_and_stale_revision_are_refused_without_loading_private_body(): void
    {
        $service = app(Interventions::class);
        $row = $service->create($this->actor(), $this->schoolA->ids['asha'], $this->year, $this->input());
        DB::enableQueryLog();
        $this->denied(fn () => $service->read($this->actor('theo'), $row['id']), 404);
        $this->denied(fn () => $service->read($this->actor('farid'), $row['id']), 403);
        $this->principal();
        $this->denied(fn () => $service->read($this->superAdmin(), $row['id']), 404);
        $queries = collect(DB::getQueryLog())->pluck('query')->filter(fn ($q) => str_contains($q, 'interventions'));
        $this->assertFalse($queries->contains(fn ($q) => str_contains($q, '`action`') || str_contains($q, '`outcome`')));
        DB::disableQueryLog();
        $this->denied(fn () => $service->create($this->actor(), $this->schoolB->ids['asha'], $this->year, $this->input()), 404);
        $this->denied(fn () => $service->update($this->actor(), $row['id'], ['expected_revision' => 3, 'action' => 'Stale private draft',
            'review_on' => '2026-02-25', 'state' => 'active', 'outcome' => null]), 409);
        $this->actor()->syncRoles([SchoolRoles::FACILITATOR]);
        $this->denied(fn () => $service->read($this->actor(), $row['id']), 404);
        $this->denied(fn () => app(RiskViews::class)->interventions($this->actor(), $this->year), 403);
    }

    public function test_risk_caseload_is_separate_and_revocation_cannot_fall_back_to_a_retained_teacher_role(): void
    {
        $this->principal();
        $this->actor()->assignRole(SchoolRoles::COUNSELLOR);
        app(LearnerNotes::class)->assign($this->superAdmin(), $this->schoolA->ids['asha'], $this->actor()->id,
            ['starts_on' => '2026-02-10', 'ends_on' => '2026-12-31', 'expected_revision' => 0]);
        $this->denied(fn () => app(RiskAuthority::class)->scope($this->actor(), $this->schoolA->ids['asha'], $this->year, $this->grade), 404);
        $service = app(Interventions::class);
        $grant = $service->assign($this->superAdmin(), $this->schoolA->ids['asha'], $this->year, $this->actor()->id,
            ['starts_on' => '2026-02-10', 'ends_on' => '2026-12-31', 'expected_revision' => 0]);
        $row = $service->create($this->actor(), $this->schoolA->ids['asha'], $this->year, $this->input());
        $this->assertSame('professional', $row['owner_authority']);
        $this->assertSame('Private school intervention', $service->read($this->actor(), $row['id'])['action']);
        $ended = $service->revoke($this->superAdmin(), $this->schoolA->ids['asha'], $this->year, $this->actor()->id, $grant['revision']);
        $this->assertSame('2026-02-10', $ended['starts_on']);
        $this->assertTrue($ended['revoked']);
        $this->denied(fn () => $service->read($this->actor(), $row['id']), 404);
        $this->assertSame([], app(RiskViews::class)->interventions($this->actor(), $this->year)['interventions']);
        $this->assertNotNull(RiskCaseload::sole()->revoked_at);
        $this->appointGradeHead();
        $queue = app(RiskViews::class)->interventions($this->actor(), $this->year);
        $this->assertSame($row['id'], $queue['interventions'][0]['id']);
        $this->assertFalse($queue['interventions'][0]['can_read_body']);
    }

    public function test_professional_handoff_requires_a_separate_current_grant_and_never_loads_body_for_principal(): void
    {
        $this->principal();
        $this->actor()->assignRole(SchoolRoles::COUNSELLOR);
        $this->actor('theo')->assignRole(SchoolRoles::COUNSELLOR);
        $service = app(Interventions::class);
        $dates = ['starts_on' => '2026-02-10', 'ends_on' => '2026-12-31', 'expected_revision' => 0];
        $service->assign($this->superAdmin(), $this->schoolA->ids['asha'], $this->year, $this->actor()->id, $dates);
        $row = $service->create($this->actor(), $this->schoolA->ids['asha'], $this->year, $this->input());
        $this->denied(fn () => $service->transferOwner($this->superAdmin(), $row['id'], $this->actor('theo')->id, 1, 'Explicit care handoff'), 404);
        $service->assign($this->superAdmin(), $this->schoolA->ids['asha'], $this->year, $this->actor('theo')->id, $dates);
        DB::enableQueryLog();
        $changed = $service->transferOwner($this->superAdmin(), $row['id'], $this->actor('theo')->id, 1, 'Explicit care handoff');
        $selects = collect(DB::getQueryLog())->pluck('query')->filter(fn ($q) => str_starts_with(strtolower($q), 'select') && str_contains($q, 'interventions'));
        $this->assertFalse($selects->contains(fn ($q) => str_contains($q, '`action`') || str_contains($q, '`outcome`')));
        DB::disableQueryLog();
        $this->assertSame(2, $changed['revision']);
        $this->assertArrayNotHasKey('action', $changed);
        $this->assertSame('Private school intervention', $service->read($this->actor('theo'), $row['id'])['action']);
        $this->denied(fn () => $service->read($this->actor(), $row['id']), 404);
        $this->assertSame('owner_change', $service->history($this->actor('theo'), $row['id'])[0]['record']['kind']);
    }

    public function test_aggregate_counts_remain_grade_year_stamped_and_never_return_private_records(): void
    {
        $this->capture('4.00', '4.00');
        app(RiskRecompute::class)->grade($this->year, $this->grade);
        $metrics = app(RiskMetrics::class);
        $summary = $metrics->forPrincipal($this->superAdmin(), $this->year, '2026-02-01', '2026-02-10');
        $this->assertSame(1, $summary['grades'][$this->grade]['academic_risk']);
        $this->assertStringNotContainsString('learner_id', json_encode($summary));
        $this->assertStringNotContainsString('evidence', json_encode($summary));
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => GradeLevel::where('code', '8')->value('id')]);
        $this->assertSame($summary, $metrics->forPrincipal($this->superAdmin(), $this->year, '2026-02-01', '2026-02-10'));
        $adapter = app(LeadershipSignals::class)->counts($this->superAdmin(), $this->year, '2026-02-01', '2026-02-10', [$this->grade => [$this->schoolA->ids['asha']]]);
        $this->assertSame(['academic_risk' => 1, 'discipline' => null], $adapter[$this->grade]);
        $this->denied(fn () => $metrics->forPrincipal($this->actor('farid'), $this->year, '2026-02-01', '2026-02-10'), 403);
        PlatformStaffIdentity::create(['user_id' => $this->superAdmin()->id]);
        $this->denied(fn () => $metrics->forPrincipal($this->superAdmin(), $this->year, '2026-02-01', '2026-02-10'), 403);
    }

    public function test_disabled_module_and_queued_evaluation_do_not_invent_available_counts_or_cross_context(): void
    {
        SchoolModule::where('module', 'early_warning')->update(['enabled' => false]);
        app(ModuleGate::class)->forget();
        $this->assertFalse(app(RiskMetrics::class)->forPrincipal($this->superAdmin(), $this->year, '2026-02-01', '2026-02-10')['available']);
        (new EvaluateRisk(1, $this->year, $this->grade))->handle(app(CurrentSchool::class), app(RiskRecompute::class));
        $this->assertSame(0, RiskObservation::count());
        SchoolModule::where('module', 'early_warning')->update(['enabled' => true]);
        app(ModuleGate::class)->forget();
        app(CurrentSchool::class)->run(2, function () {
            (new EvaluateRisk(1, $this->year, $this->grade))->handle(app(CurrentSchool::class), app(RiskRecompute::class));
            $this->assertSame(2, app(CurrentSchool::class)->id());
        });
        $this->artisan('risk:evaluate', ['school' => 1])->assertSuccessful();
        Bus::assertDispatched(EvaluateRisk::class, fn ($job) => $job->schoolId === 1 && $job->yearId === $this->year);
        $this->artisan('risk:evaluate', ['school' => 999999])->assertFailed();
    }

    public function test_unmapped_other_learners_do_not_stop_independent_valid_observations(): void
    {
        Enrolment::create(['learner_id' => $this->schoolA->ids['cara'], 'session_year_id' => $this->year,
            'class_section_id' => $this->schoolA->ids['section_c'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic unmapped roster']);
        ClassSchool::whereKey($this->schoolA->ids['class_c'])->update(['grade_level_id' => null]);
        $this->capture('4.00', '4.00');
        app(RiskRecompute::class)->grade($this->year, $this->grade);
        $this->assertSame(25, RiskObservation::where('learner_id', $this->schoolA->ids['asha'])->where('source', 'academic')->sole()->score);
        $this->assertSame(0, RiskObservation::where('learner_id', $this->schoolA->ids['cara'])->count());
    }

    public function test_active_owned_care_actions_continue_across_year_rollover_without_rewriting_creation_year(): void
    {
        $service = app(Interventions::class);
        $row = $service->create($this->actor(), $this->schoolA->ids['asha'], $this->year, $this->input());
        $next = SessionYear::create(['name' => 'Next care year', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31']);
        Settings::where('type', 'session_year')->update(['message' => (string) $next->id]);
        $this->travelTo(now()->setDate(2027, 2, 10));
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $next->id,
            'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'reason' => 'Synthetic continued care']);
        $groups = app(TeachingGroups::class);
        $group = $groups->save($this->superAdmin(), ['name' => 'Next-year care group', 'subject_id' => $this->subject,
            'grade_level_id' => $this->grade, 'session_year_id' => $next->id, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        $dates = ['starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'reason' => 'Synthetic continued care'];
        $groups->dated($this->superAdmin(), 'teachers', $dates + ['teacher_id' => $this->schoolA->ids['tara_teacher']], $group->id);
        $groups->dated($this->superAdmin(), 'memberships', $dates + ['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'catch_up'], $group->id);
        $updated = $service->update($this->actor(), $row['id'], ['expected_revision' => 1, 'action' => 'Care continued into the next school year',
            'review_on' => '2027-02-25', 'state' => 'completed', 'outcome' => 'Care completed by the current authorized owner']);
        $this->assertSame($this->year, $updated['session_year_id']);
        $this->assertSame('completed', $updated['state']);
        $this->assertSame('Care continued into the next school year', $service->read($this->actor(), $row['id'])['action']);
        $this->denied(fn () => $service->create($this->actor(), $this->schoolA->ids['asha'], $this->year, $this->input()), 423);
    }

    public function test_real_mysql_mutex_is_held_before_evidence_and_released_after_evaluation(): void
    {
        config(['database.connections.risk_probe' => config('database.connections.mysql')]);
        DB::purge('risk_probe');
        $probe = DB::connection('risk_probe');
        $name = RiskRecompute::mutexName(1, $this->year, $this->grade);
        $entered = false;
        $callback = function () use ($probe, $name, &$entered) {
            $entered = true;
            $lock = $probe->selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$name], false);
            $this->assertSame(0, (int) $lock->acquired);
        };
        app()->instance(RiskSettingsService::class, new class($callback) extends RiskSettingsService
        {
            public function __construct(private readonly \Closure $callback) {}

            public function values(): array
            {
                ($this->callback)();

                return parent::values();
            }
        });
        try {
            app(RiskRecompute::class)->grade($this->year, $this->grade);
            $this->assertTrue($entered);
            $this->assertSame(1, (int) $probe->selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$name], false)->acquired);
        } finally {
            $probe->selectOne('SELECT RELEASE_LOCK(?) AS released', [$name], false);
            DB::purge('risk_probe');
        }
    }

    public function test_context_replacement_during_source_resolution_is_refused_and_original_school_restored(): void
    {
        app()->instance(RiskSettingsService::class, new class extends RiskSettingsService
        {
            public function values(): array
            {
                app(CurrentSchool::class)->set(2);

                return parent::values();
            }
        });
        $this->denied(fn () => app(RiskRecompute::class)->grade($this->year, $this->grade), 403);
        $this->assertSame(1, app(CurrentSchool::class)->id());
        $this->assertSame(0, RiskObservation::count());
        app(CurrentSchool::class)->run(2, fn () => $this->assertSame(0, RiskObservation::count()));
    }

    public function test_grade_change_after_source_read_cannot_append_a_stale_old_grade_observation(): void
    {
        $this->capture('4.00', '4.00');
        $room = $this->schoolA->ids['class_a'];
        $nextGrade = (int) GradeLevel::where('code', '8')->value('id');
        app()->instance(RiskAuthority::class, new class($room, $nextGrade) extends RiskAuthority
        {
            private int $calls = 0;

            public function __construct(private readonly int $room, private readonly int $nextGrade) {}

            public function currentGrade(int $learner, int $year): int
            {
                if (++$this->calls === 2) {
                    ClassSchool::whereKey($this->room)->update(['grade_level_id' => $this->nextGrade]);
                }

                return parent::currentGrade($learner, $year);
            }
        });
        app(RiskRecompute::class)->grade($this->year, $this->grade);
        $this->assertSame(0, RiskObservation::where('grade_level_id', $this->grade)->count());
    }

    public function test_retained_observations_and_interventions_block_destructive_rollback(): void
    {
        app(RiskRecompute::class)->grade($this->year, $this->grade);
        $migration = require database_path('migrations/2026_10_25_100000_create_risk_interventions.php');
        $this->expectException(\LogicException::class);
        $migration->down();
    }
}
