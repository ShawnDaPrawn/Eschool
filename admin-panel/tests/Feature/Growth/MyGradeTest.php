<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\AttendanceRegister;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Growth\RiskObservation;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Services\Facilitators\FacilitatorAccess;
use App\Services\Growth\MyGrade;
use App\Services\ParentPolicyService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Bus;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

class MyGradeTest extends TestCase
{
    use UsesMasteryScenario;

    private int $grade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        Bus::fake();
        $this->grade = (int) TeachingGroup::findOrFail($this->group)->grade_level_id;
        SchoolModule::updateOrCreate(['module' => 'my_grade'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        $this->actor()->forceFill(['password_change_required' => false])->save();
    }

    private function appoint(): AcademicLeadership
    {
        return AcademicLeadership::create(['user_id' => $this->actor()->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade,
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic current head']);
    }

    private function expectDenied(callable $operation, int $status = 403): void
    {
        try {
            $operation();
            $this->fail('The unrelated actor or grade must be refused.');
        } catch (HttpExceptionInterface $error) {
            $this->assertSame($status, $error->getStatusCode());
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        }
    }

    public function test_hub_requires_current_year_dated_head_without_generic_staff_or_admin_bypass(): void
    {
        $s = app(MyGrade::class);
        $this->expectDenied(fn () => $s->access($this->actor()));
        $this->expectDenied(fn () => $s->access($this->superAdmin()));
        $head = $this->appoint();
        $access = $s->access($this->actor());
        $this->assertSame($this->year, $access['session_year_id']);
        $this->assertSame([$this->grade], array_column($access['grades'], 'id'));
        $other = (int) GradeLevel::where('id', '!=', $this->grade)->value('id');
        $this->expectDenied(fn () => $s->dashboard($this->actor(), $other));
        $this->expectDenied(fn () => $s->dashboard($this->actor('farid'), $this->grade));
        $this->expectDenied(fn () => $s->dashboard($this->actor('asha'), $this->grade));
        $head->update(['ends_on' => '2026-02-09']);
        $this->assertFalse($s->available($this->actor()));
        $head->update(['starts_on' => '2026-02-11', 'ends_on' => '2026-12-31']);
        $this->assertFalse($s->available($this->actor()));
    }

    public function test_dedicated_staff_profile_tracks_the_fresh_grade_head_appointment(): void
    {
        ParentPolicyService::acceptCurrent($this->actor());
        $profiles = app(FacilitatorAccess::class);
        $this->assertFalse($profiles->profile($this->actor())['features']['my_grade']);
        $head = $this->appoint();
        $this->assertTrue($profiles->profile($this->actor())['features']['my_grade']);
        $head->update(['ends_on' => '2026-02-09']);
        $this->assertFalse($profiles->profile($this->actor())['features']['my_grade']);
    }

    public function test_optional_modules_remain_independent_and_missing_attendance_is_never_an_absence(): void
    {
        $this->appoint();
        $s = app(MyGrade::class);
        $v = $s->dashboard($this->actor(), $this->grade);
        $this->assertSame(1, $v['total']);
        $this->assertSame('unrecorded', $v['learners'][0]['attendance_state']);
        $this->assertSame([], $v['learners'][0]['signals']);
        $this->assertFalse($v['risk_available']);
        $this->assertFalse($v['discipline']['available']);
        $this->assertNull($v['discipline']['total']);
        $this->assertFalse($v['awards']['available']);
        SchoolModule::where('module', 'my_grade')->update(['read_only' => true]);
        $this->assertFalse($s->dashboard($this->actor(), $this->grade)['writable']);
        $this->expectDenied(fn () => $s->dashboard($this->actor(), $this->grade, '2026-02-11'), 422);
        SchoolModule::where('module', 'my_grade')->update(['enabled' => false]);
        $this->expectDenied(fn () => $s->dashboard($this->actor(), $this->grade));
    }

    public function test_roster_requires_current_student_membership_status_and_exact_dated_grade(): void
    {
        $this->appoint();
        $s = app(MyGrade::class);
        $this->assertSame(1, $s->dashboard($this->actor(), $this->grade)['total']);
        $child = $this->actor('asha');
        $child->forceFill(['status' => 0])->save();
        $this->assertSame(0, $s->dashboard($this->actor(), $this->grade)['total']);
        $child->forceFill(['status' => 1])->save();
        SchoolMembership::where('user_id', $child->id)->where('role', 'Student')->update(['status' => 'ended']);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $child->id, 'role' => 'Parent', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $this->assertSame(0, $s->dashboard($this->actor(), $this->grade)['total']);
    }

    private function observation(int $learner, string $source, int $score): void
    {
        RiskObservation::create(['learner_id' => $learner, 'session_year_id' => $this->year, 'grade_level_id' => $this->grade,
            'as_of' => '2026-02-10', 'window_start' => '2026-02-01', 'source' => $source, 'score' => $score, 'signal_count' => 1,
            'reason_code' => $source === 'wellbeing' ? 'SECRET_CLINICAL' : 'academic_band', 'evidence' => ['synthetic' => true],
            'fingerprint' => hash('sha256', $learner.':'.$source.':'.$score), 'created_at' => now()]);
    }

    public function test_watch_is_academic_attendance_only_risk_sorted_and_register_matches_current_homeroom(): void
    {
        $this->appoint();
        SchoolModule::updateOrCreate(['module' => 'early_warning'], ['in_package' => true, 'enabled' => true]);
        Enrolment::create(['learner_id' => $this->schoolA->ids['ben'], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'],
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic grade placement']);
        $this->observation($this->schoolA->ids['asha'], 'academic', 10);
        $this->observation($this->schoolA->ids['ben'], 'attendance', 80);
        $this->observation($this->schoolA->ids['asha'], 'wellbeing', 999);
        $current = null;
        foreach (['section_a' => 'present', 'section_c' => 'absent'] as $section => $state) {
            $id = $this->schoolA->ids[$section];
            $register = AttendanceRegister::create(['session_year_id' => $this->year, 'class_section_id' => $id, 'grade_level_id' => $this->grade,
                'on_date' => '2026-02-10', 'scope_key' => 'homeroom:'.$id, 'label' => 'Synthetic register', 'revision' => 1]);
            AttendanceEntry::create(['attendance_register_id' => $register->id, 'learner_id' => $this->schoolA->ids['asha'], 'state' => $state,
                'source' => 'manual', 'captured_by' => $this->actor()->id, 'revision' => 1]);
            if ($section === 'section_a') {
                $current = (int) $register->id;
            }
        }
        $v = app(MyGrade::class)->dashboard($this->actor(), $this->grade);
        $this->assertSame([$this->schoolA->ids['ben'], $this->schoolA->ids['asha']], array_column($v['learners'], 'learner_id'));
        $this->assertSame('present', $v['learners'][1]['attendance_state']);
        $this->assertSame($current, $v['learners'][1]['register_id']);
        $this->assertStringNotContainsString('wellbeing', json_encode($v));
        $this->assertStringNotContainsString('SECRET', json_encode($v));
    }

    public function test_platform_impersonation_password_and_school_replacement_never_gain_a_grade_projection(): void
    {
        $this->appoint();
        $s = app(MyGrade::class);
        $actor = $this->actor();
        $actor->forceFill(['password_change_required' => true])->save();
        $this->expectDenied(fn () => $s->dashboard($actor, $this->grade));
        $actor->forceFill(['password_change_required' => false])->save();
        PlatformStaffIdentity::create(['user_id' => $actor->id, 'active' => true]);
        $this->expectDenied(fn () => $s->dashboard($actor, $this->grade));
        PlatformStaffIdentity::query()->delete();
        app(CurrentSchool::class)->impersonate(School::findOrFail(1));
        $this->expectDenied(fn () => $s->dashboard($actor, $this->grade));
        app(CurrentSchool::class)->set(2);
        $this->expectDenied(fn () => $s->dashboard($actor, $this->grade));
        app(CurrentSchool::class)->set(1);
    }
}
