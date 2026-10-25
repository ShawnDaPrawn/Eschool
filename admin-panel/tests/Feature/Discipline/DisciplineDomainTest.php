<?php

declare(strict_types=1);

namespace Tests\Feature\Discipline;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\TeachingGroup;
use App\Models\ClassSchool;
use App\Models\Discipline\DetentionEntry;
use App\Models\Discipline\DisciplineAcknowledgement;
use App\Models\Discipline\DisciplineFrameworkVersion;
use App\Models\Discipline\DisciplineRecord;
use App\Models\Growth\RiskObservation;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\Term;
use App\Services\Discipline\DisciplineDetentions;
use App\Services\Discipline\DisciplineFrameworks;
use App\Services\Discipline\DisciplineMetrics;
use App\Services\Discipline\DisciplineRecords;
use App\Services\Growth\RiskRecompute;
use App\Services\Leadership\LeadershipSignals;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

class DisciplineDomainTest extends TestCase
{
    use UsesMasteryScenario;

    private int $grade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        Bus::fake();
        $this->grade = (int) TeachingGroup::findOrFail($this->group)->grade_level_id;
        foreach (['discipline', 'early_warning'] as $module) {
            SchoolModule::updateOrCreate(['module' => $module], ['in_package' => true, 'enabled' => true]);
        }app(ModuleGate::class)->forget();
    }

    private function framework(string $reset = 'school_year', bool $detention = false): array
    {
        return ['title' => 'Synthetic school framework', 'expected_revision' => 0, 'counter_scope' => 'same_infraction', 'reset' => $reset, 'rolling_days' => $reset === 'rolling_days' ? 1 : null,
            'levels' => [['code' => 'warning', 'name' => 'School notice', 'threshold' => 1, 'kind' => 'sanction', 'sanctions' => [$detention ? 'detention' : 'notice']],
                ['code' => 'review', 'name' => 'Grade-head review', 'threshold' => 2, 'kind' => 'sanction', 'sanctions' => ['detention']],
                ['code' => 'hearing', 'name' => 'Principal hearing', 'threshold' => 3, 'kind' => 'hearing', 'sanctions' => []]],
            'sanctions' => [['code' => 'notice', 'name' => 'School notice', 'kind' => 'notice'], ['code' => 'detention', 'name' => 'Supervised detention', 'kind' => 'detention']],
            'infractions' => [['code' => 'late', 'name' => 'Late arrival', 'base_level' => 'warning']]];
    }

    private function configure(string $reset = 'school_year', bool $detention = false): void
    {
        app(DisciplineFrameworks::class)->save($this->superAdmin(), $this->year, $this->framework($reset, $detention));
    }

    private function entry(string $date = '2026-02-10'): array
    {
        return ['occurred_on' => $date, 'infraction_code' => 'late', 'description' => 'Factual family-facing school description', 'staff_note' => 'Private staff-only follow-up note'];
    }

    private function record(string $date = '2026-02-10'): array
    {
        return app(DisciplineRecords::class)->create($this->actor(), $this->schoolA->ids['asha'], $this->year, $this->entry($date));
    }

    private function appointGradeHead(): void
    {
        AcademicLeadership::create(['user_id' => $this->actor()->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade, 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic grade leadership']);
    }

    private function appointPrincipal(): void
    {
        AcademicLeadership::create(['user_id' => $this->superAdmin()->id, 'kind' => 'principal', 'grade_level_id' => null, 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic principal']);
    }

    private function denied(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Access must be refused.');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($status, $e->getStatusCode());
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        }
    }

    public function test_framework_is_explicit_versioned_and_never_defines_automatic_expulsion(): void
    {
        $this->denied(fn () => $this->record(), 422);
        $this->configure();
        $this->assertSame(1, DisciplineFrameworkVersion::count());
        $this->denied(fn () => app(DisciplineFrameworks::class)->save($this->actor(), $this->year, $this->framework()), 403);
        $bad = $this->framework();
        $bad['expected_revision'] = 1;
        $bad['levels'][2]['kind'] = 'sanction';
        $bad['levels'][2]['sanctions'] = ['notice'];
        $this->denied(fn () => app(DisciplineFrameworks::class)->save($this->superAdmin(), $this->year, $bad), 422);
        $bad = $this->framework();
        $bad['expected_revision'] = 1;
        $bad['sanctions'][0]['kind'] = 'expulsion';
        try {
            app(DisciplineFrameworks::class)->save($this->superAdmin(), $this->year, $bad);
            $this->fail('Unknown destructive sanctions must fail validation.');
        } catch (ValidationException) {
            $this->assertSame(1, DisciplineFrameworkVersion::count());
        }
        $this->expectException(\LogicException::class);
        DisciplineFrameworkVersion::sole()->update(['revision' => 5]);
    }

    public function test_allocated_record_is_encrypted_and_wrong_school_role_owner_never_selects_body(): void
    {
        $this->configure();
        $row = $this->record();
        $records = app(DisciplineRecords::class);
        $this->assertSame('confirmed', $row['status']);
        $this->assertTrue($row['published']);
        $this->assertArrayNotHasKey('staff_note', $row);
        $this->assertSame($this->entry()['staff_note'], $records->read($this->actor(), $row['id'])['staff_note']);
        $this->assertStringNotContainsString('Private staff-only', DB::table('discipline_records')->value('staff_note'));
        $this->assertStringNotContainsString('Private staff-only', json_encode(DB::table('audit_log')->get()));
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->denied(fn () => $records->read($this->actor('theo'), $row['id']), 404);
        foreach (DB::getQueryLog() as $q) {
            if (str_contains($q['query'], '`discipline_records`')) {
                $this->assertStringNotContainsString('`description`', $q['query']);
                $this->assertStringNotContainsString('`staff_note`', $q['query']);
            }
        }
        DB::disableQueryLog();
        $this->denied(fn () => $records->create($this->actor(), $this->schoolB->ids['asha'], $this->year, $this->entry()), 404);
        $this->denied(fn () => $records->create($this->actor(), $this->schoolA->ids['ben'], $this->year, $this->entry()), 404);
        $this->actor()->syncRoles([SchoolRoles::COUNSELLOR]);
        $this->denied(fn () => $records->read($this->actor(), $row['id']), 404);
        $this->denied(fn () => $records->create($this->actor(), $this->schoolA->ids['asha'], $this->year, $this->entry()), 404);
    }

    public function test_escalation_requires_grade_review_and_top_level_routes_to_a_principal_hearing(): void
    {
        $this->configure();
        $r = app(DisciplineRecords::class);
        $this->record();
        $second = $this->record();
        $this->assertSame('pending_review', $second['status']);
        $this->assertFalse($second['published']);
        $review = ['expected_revision' => 1, 'decision' => 'confirm', 'reason' => 'Reviewed the school record', 'sanction_code' => 'detention'];
        $this->denied(fn () => $r->review($this->actor(), $second['id'], $review), 403);
        $this->appointGradeHead();
        $r->review($this->actor(), $second['id'], $review);
        $third = $this->record();
        $this->assertSame('hearing', $third['level_code']);
        $hearing = $r->review($this->actor(), $third['id'], ['expected_revision' => 1, 'decision' => 'confirm', 'reason' => 'Refer for a fair principal hearing']);
        $this->assertSame('hearing', $hearing['status']);
        $this->assertNull($hearing['sanction_code']);
        $decision = ['expected_revision' => 2, 'decision' => 'no_action', 'reason' => 'Principal completed the hearing'];
        $this->denied(fn () => $r->hearing($this->superAdmin(), $third['id'], $decision), 403);
        $this->appointPrincipal();
        $decided = $r->hearing($this->superAdmin(), $third['id'], $decision);
        $this->assertSame('confirmed', $decided['status']);
        $this->assertNull($decided['sanction_code']);
        $this->assertSame('active', SchoolMembership::where('school_id', 1)->where('user_id', $this->schoolA->ids['asha_user'])->where('role', 'Student')->value('status'));
        $this->denied(fn () => $r->review($this->actor(), $third['id'], ['expected_revision' => 3, 'decision' => 'overturn', 'reason' => 'Attempt to override the principal']), 403);
    }

    public function test_reasoned_overturn_is_retained_excluded_from_escalation_and_requires_fresh_head(): void
    {
        $this->configure();
        $first = $this->record();
        $r = app(DisciplineRecords::class);
        $this->appointGradeHead();
        $r->review($this->actor(), $first['id'], ['expected_revision' => 1, 'decision' => 'overturn', 'reason' => 'Corrected after the evidence review']);
        $next = $this->record();
        $this->assertSame('warning', $next['level_code']);
        $this->assertSame('confirmed', $next['status']);
        $this->assertSame(2, count($r->versions($this->actor(), $first['id'])));
        AcademicLeadership::where('user_id', $this->actor()->id)->update(['ends_on' => '2026-02-09']);
        $this->denied(fn () => $r->review($this->actor(), $next['id'], ['expected_revision' => 1, 'decision' => 'overturn', 'reason' => 'No longer an appointed grade head']), 403);
    }

    public function test_rolling_reset_retains_old_entries_without_treating_them_as_permanent_penalty(): void
    {
        $this->configure('rolling_days');
        $this->record();
        $this->travelTo(now()->addDay());
        $next = $this->record('2026-02-11');
        $this->assertSame('warning', $next['level_code']);
        $this->assertSame(2, DisciplineRecord::count());
    }

    public function test_family_history_omits_pending_and_staff_notes_and_acknowledges_only_current_revision(): void
    {
        $this->configure();
        $one = $this->record();
        $this->record();
        $r = app(DisciplineRecords::class);
        $guardian = $this->actor('farid');
        $guardian->forceFill(['email_verified_at' => now()])->save();
        $history = $r->family($guardian, $this->schoolA->ids['asha'], $this->year);
        $this->assertCount(1, $history['records']);
        $this->assertStringNotContainsString('staff_note', json_encode($history));
        $this->assertStringNotContainsString('Private staff-only', json_encode($history));
        $ack = $r->acknowledge($guardian, $one['id'], 1);
        $this->assertSame($ack, $r->acknowledge($guardian, $one['id'], 1));
        $this->assertSame(1, DisciplineAcknowledgement::count());
        $this->assertTrue($r->family($guardian, $this->schoolA->ids['asha'], $this->year)['records'][0]['acknowledged']);
        $this->denied(fn () => $r->family($guardian, $this->schoolA->ids['ben'], $this->year), 404);
        $this->denied(fn () => $r->family($this->actor('asha'), $this->schoolA->ids['asha'], $this->year), 403);
        $this->appointGradeHead();
        $r->review($this->actor(), $one['id'], ['expected_revision' => 1, 'decision' => 'overturn', 'reason' => 'Internal evidential finding', 'family_reason' => 'School corrected this entry']);
        $this->assertFalse($r->family($guardian, $this->schoolA->ids['asha'], $this->year)['records'][0]['acknowledged']);
        $this->denied(fn () => $r->acknowledge($guardian, $one['id'], 1), 409);
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $guardian->id], ['status' => 'ended']);
        $this->denied(fn () => $r->family($guardian, $this->schoolA->ids['asha'], $this->year), 404);
    }

    public function test_detention_assignment_unknown_attendance_correction_and_overturn_are_scoped(): void
    {
        $this->configure('school_year', true);
        $row = $this->record();
        $this->appointGradeHead();
        $d = app(DisciplineDetentions::class);
        $session = $d->create($this->actor(), $this->year, $this->grade, ['supervisor_id' => $this->actor('theo')->id, 'on_date' => '2026-02-10', 'starts_at' => '15:00', 'ends_at' => '16:00', 'location' => 'Synthetic supervised room', 'capacity' => 1]);
        $entry = $d->enroll($this->actor(), $session['id'], $row['id'], 1);
        $this->assertNull($d->read($this->actor('theo'), $session['id'])['entries'][0]['attendance']);
        $this->denied(fn () => $d->read($this->actor('mona'), $session['id']), 403);
        $d->attendance($this->actor('theo'), $session['id'], $entry['entry_id'], 1, 2, 'absent', 'Captured by the assigned supervisor');
        $this->denied(fn () => $d->attendance($this->actor('theo'), $session['id'], $entry['entry_id'], 2, 3, 'present', 'Supervisor cannot overturn a captured state'), 403);
        $d->attendance($this->actor(), $session['id'], $entry['entry_id'], 2, 3, 'excused', 'Grade head reviewed the absence');
        app(DisciplineRecords::class)->review($this->actor(), $row['id'], ['expected_revision' => 1, 'decision' => 'overturn', 'reason' => 'Sanction withdrawn after evidence review']);
        $this->assertFalse($d->read($this->actor('theo'), $session['id'])['entries'][0]['source_valid']);
        $this->denied(fn () => $d->attendance($this->actor('theo'), $session['id'], $entry['entry_id'], 3, 4, 'present', 'The original sanction is now overturned'), 409);
        $this->assertSame('excused', DetentionEntry::sole()->attendance);
        $history = app(DisciplineRecords::class)->family($this->actor('farid'), $this->schoolA->ids['asha'], $this->year);
        $this->assertSame('excused', $history['records'][0]['detentions'][0]['attendance']);
        $this->assertFalse($history['records'][0]['detentions'][0]['active']);
        $this->assertStringNotContainsString('reason', json_encode($history['records'][0]['detentions']));
    }

    public function test_aggregate_and_risk_sources_use_confirmed_dated_counts_without_private_labels(): void
    {
        $m = app(DisciplineMetrics::class);
        $this->assertNull($m->observed($this->schoolA->ids['asha'], $this->year, $this->grade, '2026-02-01', '2026-02-10')['score']);
        $this->configure();
        $this->assertSame(0, $m->observed($this->schoolA->ids['asha'], $this->year, $this->grade, '2026-02-01', '2026-02-10')['score']);
        $one = $this->record();
        $this->record();
        $counts = $m->forLeadership($this->superAdmin(), $this->year, '2026-02-01', '2026-02-10', [$this->grade => [$this->schoolA->ids['asha']]]);
        $this->assertSame(1, $counts[$this->grade]);
        $this->assertStringNotContainsString('learner_id', json_encode($counts));
        app(RiskRecompute::class)->grade($this->year, $this->grade);
        $signal = RiskObservation::where('learner_id', $this->schoolA->ids['asha'])->where('source', 'discipline')->latest('id')->firstOrFail();
        $this->assertSame(1, $signal->signal_count);
        $this->assertSame(10, $signal->score);
        $adapter = app(LeadershipSignals::class)->counts($this->superAdmin(), $this->year, '2026-02-01', '2026-02-10', [$this->grade => [$this->schoolA->ids['asha']]]);
        $this->assertSame(1, $adapter[$this->grade]['discipline']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => null]);
        $this->assertSame($counts, $m->forLeadership($this->superAdmin(), $this->year, '2026-02-01', '2026-02-10', [$this->grade => [$this->schoolA->ids['asha']]]));
        $this->denied(fn () => $m->forLeadership($this->actor(), $this->year, '2026-02-01', '2026-02-10', [$this->grade => [$this->schoolA->ids['asha']]]), 403);
    }

    public function test_platform_password_module_and_impersonation_never_grant_discipline_access(): void
    {
        $this->configure();
        $row = $this->record();
        $r = app(DisciplineRecords::class);
        PlatformStaffIdentity::create(['user_id' => $this->actor()->id]);
        $this->denied(fn () => $r->read($this->actor(), $row['id']), 403);
        PlatformStaffIdentity::query()->delete();
        $this->actor()->forceFill(['password_change_required' => true])->save();
        $this->denied(fn () => $r->read($this->actor(), $row['id']), 403);
        $this->actor()->forceFill(['password_change_required' => false])->save();
        SchoolModule::where('module', 'discipline')->update(['enabled' => false]);
        app(ModuleGate::class)->forget();
        $this->denied(fn () => $r->read($this->actor(), $row['id']), 403);
        $this->assertNull(app(DisciplineMetrics::class)->forLeadership($this->superAdmin(), $this->year, '2026-02-01', '2026-02-10', [$this->grade => [$this->schoolA->ids['asha']]])[$this->grade]);
    }

    public function test_term_reset_uses_calendar_days_and_preserves_the_prior_term_history(): void
    {
        $this->configure('term');
        $this->record();
        Term::where('session_year_id', $this->year)->where('id', '!=', $this->term)->delete();
        Term::create(['session_year_id' => $this->year, 'name' => 'Synthetic next term', 'start_date' => '2026-04-01', 'end_date' => '2026-06-30', 'grade_from' => 0, 'grade_to' => 12]);
        $this->travelTo(now()->setDate(2026, 4, 1));
        $next = $this->record('2026-04-01');
        $this->assertSame('warning', $next['level_code']);
        $this->assertSame(2, DisciplineRecord::count());
    }

    public function test_large_multibyte_framework_versions_do_not_truncate_and_populated_rollback_refuses(): void
    {
        $data = $this->framework();
        $data['infractions'] = [];
        for ($i = 0; $i < 100; $i++) {
            $data['infractions'][] = ['code' => 'item_'.$i, 'name' => str_repeat('漢', 150), 'base_level' => 'warning'];
        }
        app(DisciplineFrameworks::class)->save($this->superAdmin(), $this->year, $data);
        $this->assertGreaterThan(65535, strlen(DB::table('discipline_framework_versions')->value('definition')));
        $this->assertCount(100, DisciplineFrameworkVersion::sole()->definition['infractions']);
        $migration = require database_path('migrations/2026_10_25_120000_create_discipline_domain.php');
        try {
            $migration->down();
            $this->fail('Retained school discipline must block destructive rollback.');
        } catch (\LogicException) {
            $this->assertSame(1, DisciplineFrameworkVersion::count());
        }
    }

    public function test_approved_learner_reads_only_own_published_history_and_cannot_acknowledge(): void
    {
        $this->configure();
        $one = $this->record();
        $guardian = $this->actor('farid');
        $guardian->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $guardian->id], ['status' => 'active', 'can_approve' => true]);
        LearnerApproval::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $guardian->id, 'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        $service = app(DisciplineRecords::class);
        $this->assertCount(1, $service->family($this->actor('asha'), $this->schoolA->ids['asha'], $this->year)['records']);
        $this->denied(fn () => $service->acknowledge($this->actor('asha'), $one['id'], 1), 403);
        app(CurrentSchool::class)->impersonate(School::findOrFail(1));
        $this->denied(fn () => $service->read($this->superAdmin(), $one['id']), 403);
        app(CurrentSchool::class)->set(1);
    }
}
