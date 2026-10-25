<?php

declare(strict_types=1);

namespace Tests\Feature\Leadership;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\AttendanceRegister;
use App\Models\Academics\GradeLevel;
use App\Models\Attendance;
use App\Models\ClassSection;
use App\Models\Leadership\LearnerActivityDay;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Leadership\LeadershipDashboard;
use App\Services\Leadership\LeadershipSignals;
use App\Services\Leadership\LearnerActivity;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class LeadershipDashboardTest extends TestCase
{
    use UsesTwoSchools;

    private int $year;

    private int $grade;

    private int $learner;

    private User $principal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10 10:00:00', 'Africa/Johannesburg'));
        config(['modules.leadership_dashboard' => ['always_on' => true]]);
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        $this->learner = $this->schoolA->ids['asha'];
        $this->principal = $this->superAdmin();
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->grade = (int) GradeLevel::where('code', '5')->value('id');
        ClassSection::with('class')->findOrFail($this->schoolA->ids['section_a'])->class->forceFill(['grade_level_id' => $this->grade])->save();
        Enrolment::create(['learner_id' => $this->learner, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => null, 'legacy_dates' => false]);
        app(TeachingGroups::class)->dated($this->principal, 'leadership', ['user_id' => $this->principal->id, 'session_year_id' => $this->year, 'kind' => 'principal', 'grade_level_id' => null, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic dashboard appointment']);
    }

    private function read(): array
    {
        return app(LeadershipDashboard::class)->read($this->principal, $this->year, 2, '2026-02-10');
    }

    private function lastGrade(array $data): array
    {
        return $data['periods'][count($data['periods']) - 1]['grades'][0];
    }

    private function attendance(string $date, string $state, ?int $lesson = null): void
    {
        $register = AttendanceRegister::create(['session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'on_date' => $date, 'timetable_lesson_id' => $lesson, 'grade_level_id' => $this->grade, 'scope_key' => 'homeroom:'.$this->schoolA->ids['section_a'], 'label' => 'Synthetic homeroom', 'revision' => 1]);
        AttendanceEntry::create(['attendance_register_id' => $register->id, 'learner_id' => $this->learner, 'state' => $state, 'source' => 'manual', 'captured_by' => $this->principal->id, 'revision' => 1]);
    }

    public function test_recorded_attendance_has_canonical_precedence_half_days_and_holiday_exclusion(): void
    {
        Attendance::forceCreate(['student_id' => $this->learner, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'date' => '2026-02-05', 'type' => Attendance::TYPE_ABSENT]);
        $this->attendance('2026-02-05', 'present');
        $this->attendance('2026-02-06', 'first_half');
        $this->attendance('2026-02-07', 'holiday');
        Enrolment::create(['learner_id' => $this->schoolA->ids['ben'], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => null, 'legacy_dates' => false]);
        AttendanceEntry::create(['attendance_register_id' => AttendanceRegister::whereDate('on_date', '2026-02-05')->value('id'), 'learner_id' => $this->schoolA->ids['ben'], 'state' => 'absent', 'source' => 'manual', 'captured_by' => $this->principal->id, 'revision' => 1]);
        $data = $this->read();
        $row = $this->lastGrade($data);
        $this->assertSame(['status' => 'available', 'recorded_days' => 3, 'present_half_days' => 3, 'percentage_bp' => 5000], $row['attendance']);
        $this->assertSame(['status' => 'unavailable', 'value' => null], $row['fee_collection_cents']);
        $this->assertSame(['status' => 'unavailable', 'value' => null], $row['academic_risk']);
        $this->assertSame(['status' => 'unavailable', 'value' => null], $data['periods'][0]['grades'][0]['active_learners']);
        $this->assertArrayNotHasKey('learner_id', $row);
    }

    public function test_out_of_enrolment_and_other_school_activity_never_enter_the_grade_totals(): void
    {
        Enrolment::where('learner_id', $this->learner)->update(['starts_on' => '2026-02-06']);
        $this->attendance('2026-02-05', 'present');
        $this->attendance('2026-02-06', 'absent');
        app(CurrentSchool::class)->run(2, fn () => LearnerActivityDay::create(['learner_id' => $this->schoolB->ids['asha'], 'session_year_id' => $this->schoolB->ids['session_year'], 'grade_level_id' => $this->grade, 'on_date' => '2026-02-10']));
        $row = $this->lastGrade($this->read());
        $this->assertSame(1, $row['attendance']['recorded_days']);
        $this->assertSame(0, $row['attendance']['percentage_bp']);
        $this->assertNull($row['active_learners']['value']);
    }

    public function test_activity_is_dated_approved_school_attributed_idempotent_and_has_partial_observation(): void
    {
        $learner = Students::findOrFail($this->learner);
        $actor = User::findOrFail($learner->user_id);
        $guardian = User::findOrFail($this->schoolA->ids['farid_user']);
        $guardian->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $learner->id, 'guardian_user_id' => $guardian->id], ['relationship' => 'father', 'status' => 'active', 'can_approve' => true]);
        app(ApprovalService::class)->decide($guardian, $learner, 'approve', (string) config('identity.consent_version'));
        Route::middleware(['api', 'auth:sanctum', 'school.context'])->get('/api/synthetic-activity', fn () => response()->json(['ok' => true]));
        Sanctum::actingAs($actor);
        $this->getJson('/api/synthetic-activity')->assertOk();
        $this->assertSame(1, LearnerActivityDay::count());
        $activity = app(LearnerActivity::class);
        $activity->record($actor);
        $activity->record($actor);
        $this->assertSame(1, LearnerActivityDay::count());
        $row = $this->lastGrade($this->read());
        $this->assertSame(['status' => 'partial', 'value' => 1], $row['active_learners']);
        LearnerActivityDay::query()->delete();
        LearnerApproval::where('learner_id', $learner->id)->update(['withdrawn_at' => now()]);
        $activity->record($actor);
        $this->assertSame(0, LearnerActivityDay::count());
        app(CurrentSchool::class)->run(2, function () use ($actor, $activity): void {
            try {
                $activity->record($actor);
            } catch (HttpException|ModelNotFoundException) {
            } $this->assertSame(0, LearnerActivityDay::count());
        });
    }

    public function test_pending_password_ordinary_staff_and_revoked_principal_cannot_open_dashboard(): void
    {
        $this->actingAs(User::findOrFail($this->schoolA->ids['theo_user']))->get('/leadership-dashboard')->assertForbidden();
        $this->actingAs($this->principal)->get('/leadership-dashboard')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertSee('Leadership dashboard')->assertSee('Unavailable')->assertDontSee('Baobab');
        $this->principal->forceFill(['password_change_required' => true])->save();
        try {
            $this->read();
            $this->fail('Pending password must not enter metrics');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->principal->forceFill(['password_change_required' => false])->save();
        AcademicLeadership::where('user_id', $this->principal->id)->update(['ends_on' => '2026-02-09']);
        $this->get('/leadership-dashboard')->assertForbidden();
    }

    public function test_operator_and_cross_school_context_are_refused_even_with_a_school_role(): void
    {
        app(CurrentSchool::class)->run(2, function (): void {
            try {
                $this->read();
                $this->fail('No other-school appointment');
            } catch (HttpException|ModelNotFoundException $e) {
                $this->assertTrue($e instanceof ModelNotFoundException || in_array($e->getStatusCode(), [403, 404], true));
            }
        });
        PlatformStaffIdentity::create(['user_id' => $this->principal->id]);
        $this->actingAs($this->principal)->get('/leadership-dashboard')->assertForbidden();
    }

    public function test_period_validation_does_not_expand_to_future_or_unbounded_history(): void
    {
        $this->actingAs($this->principal)->get('/leadership-dashboard?weeks=13')->assertRedirect()->assertSessionHasErrors('weeks');
        $this->get('/leadership-dashboard?through=2026-02-11')->assertUnprocessable();
        $this->get('/leadership-dashboard?through=2025-12-31')->assertUnprocessable();
        $this->get('/leadership-dashboard?through[]=2026-02-01')->assertRedirect()->assertSessionHasErrors('through');
        config(['modules.leadership_dashboard' => ['default' => false]]);
        app(ModuleGate::class)->forget();
        $this->get('/leadership-dashboard')->assertNotFound();
    }

    public function test_signal_adapter_gets_only_dated_ids_and_real_zero_remains_distinct_from_missing(): void
    {
        $grade = $this->grade;
        $learner = $this->learner;
        app()->instance(LeadershipSignals::class, new class($grade, $learner) extends LeadershipSignals
        {
            public function __construct(private int $grade, private int $learner) {}

            public function counts(User $actor, int $year, string $start, string $end, array $learnersByGrade): array
            {
                Assert::assertContains($this->learner, $learnersByGrade[$this->grade]);

                return [$this->grade => ['academic_risk' => 0, 'discipline' => 1]];
            }
        });
        $row = $this->lastGrade($this->read());
        $this->assertSame(['status' => 'available', 'value' => 0], $row['academic_risk']);
        $this->assertSame(['status' => 'available', 'value' => 1], $row['discipline']);
    }

    public function test_daily_retention_prunes_older_observations_without_changing_canonical_attendance(): void
    {
        $this->attendance('2026-02-05', 'present');
        foreach (['2025-11-12', '2025-11-13'] as $date) {
            LearnerActivityDay::create(['learner_id' => $this->learner, 'session_year_id' => $this->year, 'grade_level_id' => $this->grade, 'on_date' => $date]);
        }
        $this->artisan('leadership:prune-activity')->assertSuccessful();
        $this->assertSame(['2025-11-13'], LearnerActivityDay::all()->map(fn ($r) => $r->on_date->toDateString())->all());
        $this->assertSame(1, AttendanceEntry::count());
    }
}
