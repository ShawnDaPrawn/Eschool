<?php

declare(strict_types=1);

namespace Tests\Feature\Leadership;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\AttendanceRegister;
use App\Models\Academics\GradeLevel;
use App\Models\Attendance;
use App\Models\ClassSection;
use App\Models\FeesChoiceable;
use App\Models\FeesPaid;
use App\Models\Leadership\LearnerActivityDay;
use App\Models\PaymentTransaction;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Leadership\LeadershipDashboard;
use App\Services\Leadership\LeadershipFeeCollections;
use App\Services\Leadership\LeadershipSignals;
use App\Services\Leadership\LearnerActivity;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
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
        // The existing non-financial cases exercise an unavailable fee source.
        config(['modules.fees.default' => false, 'modules.fees.legacy' => false]);
        SchoolModule::updateOrCreate(['module' => 'fees'], ['enabled' => false, 'in_package' => true, 'read_only' => false]);
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        $this->learner = $this->schoolA->ids['asha'];
        $this->principal = User::findOrFail($this->schoolA->ids['tara_user']);
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->grade = (int) GradeLevel::where('code', '5')->value('id');
        ClassSection::with('class')->findOrFail($this->schoolA->ids['section_a'])->class->forceFill(['grade_level_id' => $this->grade])->save();
        Enrolment::create(['learner_id' => $this->learner, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => null, 'legacy_dates' => false]);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'leadership', ['user_id' => $this->principal->id, 'session_year_id' => $this->year, 'kind' => 'principal', 'grade_level_id' => null, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic dashboard appointment']);
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

    public function test_fresh_school_admin_can_read_trends_without_a_principal_appointment(): void
    {
        AcademicLeadership::query()->delete();
        $this->actingAs($this->superAdmin())->get('/leadership-dashboard')->assertOk()->assertSee('Leadership dashboard');
    }

    private function enableFees(): void
    {
        config(['modules.fees.always_on' => true]);
        PaymentTransaction::where('session_year_id', $this->year)->delete();
        FeesPaid::where('session_year_id', $this->year)->delete();
    }

    private function receipt(int $cents, ?string $date = '2026-02-05', array $attributes = []): PaymentTransaction
    {
        return PaymentTransaction::forceCreate($attributes + ['student_id' => $this->learner,
            'class_id' => ClassSection::findOrFail($this->schoolA->ids['section_a'])->class_id,
            'parent_id' => $this->schoolA->ids['farid'], 'payment_status' => 1, 'mode' => 0, 'type_of_fee' => 0,
            'total_amount_cents' => $cents, 'session_year_id' => $this->year, 'date' => $date]);
    }

    private function feeBalance(int $cents): FeesPaid
    {
        return FeesPaid::forceCreate(['student_id' => $this->learner,
            'class_id' => ClassSection::findOrFail($this->schoolA->ids['section_a'])->class_id,
            'total_amount_cents' => $cents, 'session_year_id' => $this->year, 'date' => '2026-02-05', 'is_fully_paid' => 0]);
    }

    public function test_fee_receipts_are_exact_reconciled_dated_and_not_added_to_the_running_balance(): void
    {
        $this->enableFees();
        $first = $this->receipt(1001);
        $this->receipt(2002, '2026-02-01');
        $this->feeBalance(3003);
        $this->receipt(90000, attributes: ['payment_status' => 2]);
        $this->receipt(80000, attributes: ['payment_status' => 0]);
        $this->receipt(70000)->delete();
        // The deprecated float is deliberately contradictory. Only cents count.
        DB::table('payment_transactions')->where('id', $first->id)->update(['total_amount' => 98765.43]);
        $data = $this->read();
        $this->assertSame(['status' => 'available', 'value' => 1001], $this->lastGrade($data)['fee_collection_cents']);
        $this->assertSame(['status' => 'available', 'value' => 2002], $data['periods'][0]['grades'][0]['fee_collection_cents']);
        $this->assertStringNotContainsString('payment_id', json_encode($data, JSON_THROW_ON_ERROR));
        $this->assertSame(3003, (int) FeesPaid::firstOrFail()->getRawOriginal('total_amount_cents'));
    }

    public function test_inconsistent_missing_duplicate_and_unsafe_fee_amounts_are_unavailable(): void
    {
        $this->enableFees();
        $receipt = $this->receipt(1234);
        $balance = $this->feeBalance(1235);
        $this->assertNull($this->lastGrade($this->read())['fee_collection_cents']['value']);
        $balance->forceFill(['total_amount_cents' => 1234])->save();
        DB::table('payment_transactions')->where('id', $receipt->id)->update(['total_amount_cents' => null]);
        $this->assertNull($this->lastGrade($this->read())['fee_collection_cents']['value']);
        DB::table('payment_transactions')->where('id', $receipt->id)->update(['total_amount_cents' => 1234]);
        $duplicate = $this->feeBalance(1234);
        $this->assertNull($this->lastGrade($this->read())['fee_collection_cents']['value']);
        $duplicate->delete();
        DB::table('payment_transactions')->where('id', $receipt->id)->update(['total_amount_cents' => 9007199254740992]);
        DB::table('fees_paids')->where('id', $balance->id)->update(['total_amount_cents' => 9007199254740992]);
        $this->assertNull($this->lastGrade($this->read())['fee_collection_cents']['value']);
    }

    public function test_known_zero_is_distinct_from_disabled_fee_source_and_retained_readonly_receipts_remain_readable(): void
    {
        $this->enableFees();
        $this->assertSame(['status' => 'available', 'value' => 0], $this->lastGrade($this->read())['fee_collection_cents']);
        config(['modules.fees.always_on' => false]);
        SchoolModule::updateOrCreate(['module' => 'fees'], ['enabled' => false, 'in_package' => true, 'read_only' => false]);
        app(ModuleGate::class)->forget();
        $this->assertNull($this->lastGrade($this->read())['fee_collection_cents']['value']);
        SchoolModule::updateOrCreate(['module' => 'fees'], ['enabled' => true, 'in_package' => true, 'read_only' => true]);
        $this->receipt(1234);
        $this->feeBalance(1234);
        $this->assertSame(['status' => 'available', 'value' => 1234], $this->lastGrade($this->read())['fee_collection_cents']);
        $this->assertFalse(app(ModuleGate::class)->writable('fees'));
    }

    public function test_fee_grade_attribution_requires_one_matching_dated_enrolment(): void
    {
        $this->enableFees();
        $this->receipt(1234);
        $this->feeBalance(1234);
        Enrolment::where('learner_id', $this->learner)->update(['starts_on' => '2026-02-06']);
        $this->assertSame(0, $this->lastGrade($this->read())['fee_collection_cents']['value']);
        Enrolment::where('learner_id', $this->learner)->update(['starts_on' => '2026-01-01']);
        Enrolment::create(['learner_id' => $this->learner, 'session_year_id' => $this->year,
            'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-02', 'ends_on' => null, 'legacy_dates' => false]);
        $this->assertNull($this->lastGrade($this->read())['fee_collection_cents']['value']);
    }

    public function test_undated_office_receipt_uses_only_exact_matching_settled_components(): void
    {
        $this->enableFees();
        $receipt = $this->receipt(1234, null, ['type_of_fee' => 2]);
        $this->feeBalance(1234);
        $part = FeesChoiceable::create(['student_id' => $receipt->student_id, 'class_id' => $receipt->class_id,
            'session_year_id' => $this->year, 'payment_transaction_id' => $receipt->id, 'total_amount_cents' => 1234,
            'is_due_charges' => 0, 'status' => 1, 'date' => '2026-02-05']);
        $this->assertSame(1234, $this->lastGrade($this->read())['fee_collection_cents']['value']);
        $part->forceFill(['status' => 0])->save();
        $this->assertNull($this->lastGrade($this->read())['fee_collection_cents']['value']);
        $part->forceFill(['status' => 1, 'total_amount_cents' => 1233])->save();
        $this->assertNull($this->lastGrade($this->read())['fee_collection_cents']['value']);
    }

    public function test_fee_adapter_denies_other_school_staff_operators_and_revoked_principals(): void
    {
        $this->enableFees();
        $this->receipt(1234);
        $this->feeBalance(1234);
        $this->receipt(90000, attributes: ['school_id' => 2]);
        $this->assertSame(1234, $this->lastGrade($this->read())['fee_collection_cents']['value']);
        try {
            app(LeadershipFeeCollections::class)->read(User::findOrFail($this->schoolA->ids['theo_user']), $this->year, '2026-02-01', '2026-02-10');
            $this->fail('Ordinary staff cannot read fee aggregates');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        AcademicLeadership::where('user_id', $this->principal->id)->delete();
        try {
            $this->read();
            $this->fail('Revoked principal cannot read fee aggregates');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_new_office_optional_receipt_records_the_actual_date_and_settled_components(): void
    {
        $this->enableFees();
        $class = ClassSection::findOrFail($this->schoolA->ids['section_a'])->class_id;
        $this->actingAs($this->superAdmin())->postJson('/fees/optional-paid/store', [
            'date' => '2026-02-05', 'mode' => 0, 'session_year_id' => $this->year,
            'student_id' => $this->learner, 'class_id' => $class, 'total_amount' => '12.34',
            'optional_fees_type_data' => [['id' => $this->schoolA->ids['fees_bus'], 'amount' => '12.34']],
        ])->assertOk()->assertJsonPath('error', false);
        $receipt = PaymentTransaction::where('student_id', $this->learner)->where('payment_status', 1)->firstOrFail();
        $this->assertSame('2026-02-05 00:00:00', $receipt->getRawOriginal('date'));
        $part = FeesChoiceable::where('payment_transaction_id', $receipt->id)->firstOrFail();
        $this->assertSame(1, (int) $part->status);
        $this->assertSame(1234, $this->lastGrade($this->read())['fee_collection_cents']['value']);
    }

    public function test_unknown_fee_kind_and_overlapping_different_grades_are_unavailable(): void
    {
        $this->enableFees();
        $receipt = $this->receipt(1234, attributes: ['type_of_fee' => 99]);
        $this->feeBalance(1234);
        $this->assertNull($this->lastGrade($this->read())['fee_collection_cents']['value']);
        $receipt->forceFill(['type_of_fee' => 0])->save();
        $other = ClassSection::with('class')->findOrFail($this->schoolA->ids['section_c']);
        $other->class->forceFill(['grade_level_id' => GradeLevel::where('code', '6')->value('id')])->save();
        Enrolment::create(['learner_id' => $this->learner, 'session_year_id' => $this->year,
            'class_section_id' => $other->id, 'starts_on' => '2026-01-02', 'ends_on' => null, 'legacy_dates' => false]);
        $data = $this->read();
        $grades = $data['periods'][count($data['periods']) - 1]['grades'];
        $this->assertCount(2, $grades);
        foreach ($grades as $row) {
            $this->assertNull($row['fee_collection_cents']['value']);
        }
    }
}
