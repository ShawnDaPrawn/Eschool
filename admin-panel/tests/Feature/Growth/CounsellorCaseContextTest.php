<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\TeachingGroup;
use App\Models\Growth\RiskCaseload;
use App\Models\Growth\RiskObservation;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Models\Wellbeing\CareCase;
use App\Models\Wellbeing\CaseAssignment;
use App\Services\Growth\CounsellorCaseContext;
use App\Services\Growth\RiskRecompute;
use App\Services\Growth\RiskViews;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Wellbeing\WellbeingAccess;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesMasteryScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class CounsellorCaseContextTest extends TestCase
{
    use UsesMasteryScenario, ValidatesOpenApiResponses;

    private int $grade;

    private int $learner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        Bus::fake();
        foreach (['early_warning', 'wellbeing'] as $module) {
            SchoolModule::updateOrCreate(['module' => $module], ['in_package' => true, 'enabled' => true]);
        }
        app(ModuleGate::class)->forget();
        $this->grade = (int) TeachingGroup::findOrFail($this->group)->grade_level_id;
        $this->learner = $this->schoolA->ids['asha'];
        $this->actor()->assignRole('Counsellor');
        RiskCaseload::create(['learner_id' => $this->learner, 'session_year_id' => $this->year, 'professional_id' => $this->actor()->id,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'revision' => 1]);
    }

    private function careCase(string $status = 'open', ?int $owner = null): CareCase
    {
        $case = CareCase::create(['learner_id' => $this->learner, 'opened_by' => $this->superAdmin()->id,
            'status' => $status, 'opened_at' => now(), 'revision' => 1]);
        CaseAssignment::create(['support_case_id' => $case->id, 'user_id' => $owner ?? $this->actor()->id,
            'active' => true, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic explicit case assignment', 'revision' => 1]);

        return $case;
    }

    private function ownCount(): ?int
    {
        return app(CounsellorCaseContext::class)->count($this->actor(), $this->learner, $this->year, $this->grade);
    }

    public function test_counts_only_own_current_open_cases_without_loading_bodies_or_persisting_a_signal(): void
    {
        $this->careCase();
        $this->careCase('closed');
        $this->careCase('open', $this->actor('theo')->id);
        app(RiskRecompute::class)->grade($this->year, $this->grade);
        $before = RiskObservation::where('source', 'wellbeing')->get()->toJson();
        DB::enableQueryLog();
        $this->assertSame(1, $this->ownCount());
        $rows = app(RiskViews::class)->list($this->actor(), $this->year);
        $signal = collect($rows['learners'][0]['signals'])->firstWhere('source', 'wellbeing');
        $this->assertSame(1, $signal['signal_count']);
        $this->assertNull($signal['score']);
        $this->assertSame('own_authorised_active_cases', $signal['reason_code']);
        $queries = collect(DB::getQueryLog())->pluck('query')->implode("\n");
        foreach (['support_case_entries', 'incident_reports', 'incident_threads'] as $privateTable) {
            $this->assertStringNotContainsString($privateTable, $queries);
        }
        DB::disableQueryLog();
        $this->assertSame($before, RiskObservation::where('source', 'wellbeing')->get()->toJson());
        $this->actingAs($this->actor())->getJson('/api/v2/growth/risk/signals?session_year_id='.$this->year)
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($this->actor(), 'web')->get('/risk-interventions?mode=signals&session_year_id='.$this->year)->assertOk()->assertSee('Own authorised active cases')->assertSee('does not affect the risk score');
    }

    public function test_each_independent_grant_is_fresh_and_no_grant_is_not_a_measured_zero(): void
    {
        $case = $this->careCase('closed');
        $this->assertSame(0, $this->ownCount());
        CaseAssignment::where('support_case_id', $case->id)->update(['active' => false]);
        $this->assertNull($this->ownCount());
        CaseAssignment::where('support_case_id', $case->id)->update(['active' => true]);
        RiskCaseload::query()->update(['revoked_at' => now()]);
        $this->assertNull($this->ownCount());
        RiskCaseload::query()->update(['revoked_at' => null, 'ends_on' => '2026-02-09']);
        $this->assertNull($this->ownCount());
    }

    public function test_grade_head_safeguarding_parent_platform_and_other_school_do_not_inherit_counts(): void
    {
        $this->careCase();
        AcademicLeadership::create(['user_id' => $this->actor()->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade,
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic grade head']);
        $this->actor()->syncRoles(['Teacher']);
        $this->assertNull($this->ownCount());
        $this->actor()->syncRoles(['Safeguarding']);
        $this->assertNull($this->ownCount());
        $this->actor()->syncRoles(['Counsellor']);
        $this->assertSame(1, $this->ownCount());
        $this->assertNull(app(CounsellorCaseContext::class)->count($this->actor('farid'), $this->learner, $this->year, $this->grade));
        PlatformStaffIdentity::create(['user_id' => $this->actor()->id]);
        $this->assertNull($this->ownCount());
        PlatformStaffIdentity::query()->delete();
        $original = $this->actor();
        app(CurrentSchool::class)->set(2);
        $this->assertNull(app(CounsellorCaseContext::class)->count($original, $this->learner, $this->year, $this->grade));
    }

    public function test_password_module_and_clinical_assignment_dates_are_checked_on_every_read(): void
    {
        $case = $this->careCase();
        $this->actor()->forceFill(['password_change_required' => true])->save();
        $this->assertNull($this->ownCount());
        $this->actor()->forceFill(['password_change_required' => false])->save();
        SchoolModule::where('module', 'wellbeing')->update(['enabled' => false]);
        $this->assertNull($this->ownCount());
        SchoolModule::where('module', 'wellbeing')->update(['enabled' => true]);
        CaseAssignment::where('support_case_id', $case->id)->update(['ends_on' => '2026-02-09']);
        $this->assertNull($this->ownCount());
    }

    public function test_revocation_during_case_retrieval_discards_the_entire_count(): void
    {
        $this->careCase();
        app()->instance(WellbeingAccess::class, new class extends WellbeingAccess
        {
            public function case(User $actor, int $id, bool $write = false): CareCase
            {
                $case = parent::case($actor, $id, $write);
                CaseAssignment::where('support_case_id', $id)->update(['active' => false]);

                return $case;
            }
        });
        $this->assertNull($this->ownCount());
    }
}
