<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Imports\AttendanceImport;
use App\Jobs\Platform\SendMail;
use App\Jobs\Platform\SendPush;
use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\AttendanceNotice;
use App\Models\Academics\AttendanceOperation;
use App\Models\Academics\AttendanceRegister;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\HomeroomAllocation;
use App\Models\Attendance;
use App\Models\ClassSchool;
use App\Models\Leave;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\Attendance\RegisterNotices;
use App\Services\Academics\Attendance\RegisterQr;
use App\Services\Academics\Attendance\Registers;
use App\Services\Academics\Attendance\RegisterSettings;
use App\Services\Academics\TeachingGroups;
use App\Services\Academics\TimetableConfiguration;
use App\Services\Academics\TimetableEngine;
use App\Services\Platform\Notifications\DeliveryGuard;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\FcmClient;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class RegisterEngineTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private int $year;

    private int $grade;

    private User $tara;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10 08:00:00', 'Africa/Johannesburg'));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        $this->grade = (int) GradeLevel::where('code', '7')->value('id');
        $this->tara = User::findOrFail($this->schoolA->ids['tara_user']);
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $this->grade]);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Register rehearsal']);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'homerooms', ['teacher_id' => (int) Teacher::where('user_id', $this->tara->id)->value('id'),
            'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Homeroom register']);
    }

    private function open(): array
    {
        return app(Registers::class)->open($this->tara, ['session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'on_date' => '2026-02-10']);
    }

    private function write(array $matrix, string $state = 'present', array $changes = []): array
    {
        return $changes + ['operation_id' => (string) Str::uuid(), 'base_revision' => $matrix['revision'], 'roster_hash' => $matrix['roster_hash'], 'rows' => [
            ['learner_id' => $this->schoolA->ids['asha'], 'state' => $state, 'source' => 'offline', 'expected_revision' => $matrix['learners'][0]['revision']]]];
    }

    private function denied(int $status, callable $action): void
    {
        try {
            $action();
            $this->fail('Expected refusal '.$status);
        } catch (HttpExceptionInterface $error) {
            $this->assertSame($status, $error->getStatusCode());
        } catch (ModelNotFoundException $error) {
            $this->assertSame(404, $status);
        }
    }

    public function test_homeroom_capture_projects_existing_types_and_family_reads_only_its_own_child(): void
    {
        $this->actingAs($this->tara);
        $matrix = $this->open();
        $this->assertTrue($matrix['can_write']);
        $this->assertSame('unmarked', $matrix['learners'][0]['state']);
        $result = app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix, 'late'));
        $this->assertSame(1, $result['revision']);
        $row = Attendance::whereNotNull('attendance_entry_id')->sole();
        $this->assertSame(Attendance::TYPE_PRESENT, (int) $row->type);
        $this->assertSame('offline', $row->source);
        $this->assertDatabaseHas('audit_log', ['action' => 'academics.register_captured', 'actor_id' => $this->tara->id, 'school_id' => 1]);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->app['auth']->forgetGuards();
        $this->withToken($parent->createToken('register-test')->plainTextToken)->withHeader('X-School-Id', '1');
        $this->getJson('/api/v2/academics/registers/learners/'.$this->schoolA->ids['asha'].'/today')->assertOk()->assertJsonPath('data.entries.0.state', 'late');
        $this->getJson('/api/v2/academics/registers/learners/'.$this->schoolA->ids['ben'].'/today')->assertNotFound();
        $this->getJson('/api/v2/academics/registers/'.$matrix['register_id'])->assertForbidden();
    }

    public function test_duplicate_operation_is_exactly_once_and_changed_payload_cannot_reuse_its_id(): void
    {
        $matrix = $this->open();
        $data = $this->write($matrix);
        $one = app(Registers::class)->sync($this->tara, $matrix['register_id'], $data);
        $this->assertSame($one, app(Registers::class)->sync($this->tara, $matrix['register_id'], $data));
        $this->assertSame(1, AttendanceOperation::count());
        $this->assertSame(1, AttendanceEntry::count());
        $this->assertSame(1, AttendanceRegister::findOrFail($matrix['register_id'])->revision);
        $changed = $data;
        $changed['rows'][0]['state'] = 'absent';
        $this->denied(409, fn () => app(Registers::class)->sync($this->tara, $matrix['register_id'], $changed));
    }

    public function test_offline_order_and_competing_edits_preserve_all_original_revisions(): void
    {
        $matrix = $this->open();
        $first = $this->write($matrix);
        $second = $this->write($matrix, 'excused', ['base_revision' => 1, 'reason' => 'Approved explanation']);
        $second['rows'][0]['expected_revision'] = 1;
        $this->denied(409, fn () => app(Registers::class)->sync($this->tara, $matrix['register_id'], $second));
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $first);
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $second);
        $this->assertSame('excused', AttendanceEntry::sole()->state);
        $this->assertSame($this->tara->id, (int) AttendanceEntry::sole()->amended_by);
        $this->assertNotNull(AttendanceEntry::sole()->amended_at);
        $this->denied(409, fn () => app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix, 'absent')));
        $this->assertSame(2, AttendanceOperation::count());
    }

    public function test_changed_rosters_revoked_teachers_wrong_school_and_foreign_learners_cannot_sync(): void
    {
        $matrix = $this->open();
        $input = $this->write($matrix);
        $input['rows'][0]['learner_id'] = $this->schoolA->ids['cara'];
        $this->denied(404, fn () => app(Registers::class)->sync($this->tara, $matrix['register_id'], $input));
        app(CurrentSchool::class)->run(2, fn () => $this->denied(404, fn () => app(Registers::class)->matrix($this->superAdmin(), $matrix['register_id'])));
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->update(['ends_on' => '2026-02-09']);
        $this->denied(409, fn () => app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix)));
        HomeroomAllocation::where('class_section_id', $this->schoolA->ids['section_a'])->update(['ends_on' => '2026-02-09']);
        $this->denied(403, fn () => app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix)));
        $this->assertSame(0, AttendanceEntry::count());
    }

    public function test_only_current_grade_heads_can_correct_past_days_and_every_correction_needs_a_reason(): void
    {
        $matrix = $this->open();
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix));
        $matrix = app(Registers::class)->matrix($this->tara, $matrix['register_id']);
        $this->travelTo(CarbonImmutable::parse('2026-02-11 00:01:00', 'Africa/Johannesburg'));
        $this->denied(403, fn () => app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix, 'absent')));
        $head = User::findOrFail($this->schoolA->ids['theo_user']);
        AcademicLeadership::create(['user_id' => $head->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Grade head']);
        try {
            app(Registers::class)->sync($head, $matrix['register_id'], $this->write($matrix, 'absent'));
            $this->fail('A correction needs a reason');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('reason', $error->errors());
        }
        app(Registers::class)->sync($head, $matrix['register_id'], $this->write($matrix, 'absent', ['reason' => 'Verified yesterday register']));
        $this->assertSame($head->id, (int) AttendanceEntry::sole()->amended_by);
        AcademicLeadership::where('user_id', $head->id)->update(['ends_on' => '2026-02-10']);
        $this->denied(403, fn () => app(Registers::class)->matrix($head, $matrix['register_id']));
    }

    public function test_qr_identifies_only_an_eligible_learner_and_tamper_or_rotation_is_refused(): void
    {
        $matrix = $this->open();
        $input = $this->write($matrix);
        $input['rows'][0]['source'] = 'qr';
        $input['rows'][0]['qr_token'] = $matrix['learners'][0]['qr_code'];
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $input);
        $this->assertSame('qr', AttendanceEntry::sole()->source);
        try {
            app(RegisterQr::class)->learner($input['rows'][0]['qr_token'].'x');
            $this->fail('Tampered code');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('qr_token', $error->errors());
        }
        DB::table('students')->where('id', $this->schoolA->ids['asha'])->increment('attendance_qr_version');
        $this->denied(409, fn () => app(RegisterQr::class)->learner($input['rows'][0]['qr_token']));
        app(CurrentSchool::class)->run(2, fn () => $this->denied(404, fn () => app(RegisterQr::class)->learner($input['rows'][0]['qr_token'])));
    }

    public function test_current_grade_head_can_correct_previous_year_register_but_expired_or_other_grade_cannot(): void
    {
        $matrix = $this->open();
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix));
        $next = SessionYear::create(['name' => '2027', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31', 'default' => 0]);
        $head = User::findOrFail($this->schoolA->ids['theo_user']);
        $lead = AcademicLeadership::create(['user_id' => $head->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade, 'session_year_id' => $next->id,
            'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'reason' => 'Current grade head']);
        $this->travelTo(CarbonImmutable::parse('2027-01-10 08:00:00', 'Africa/Johannesburg'));
        $this->denied(403, fn () => app(Registers::class)->matrix($this->tara, $matrix['register_id']));
        $fresh = app(Registers::class)->matrix($head, $matrix['register_id']);
        $this->assertTrue($fresh['can_write']);
        $write = $this->write($fresh, 'excused', ['reason' => 'Verified historical correction']);
        app(Registers::class)->sync($head, $matrix['register_id'], $write);
        $this->assertSame('excused', AttendanceEntry::sole()->state);
        $this->assertSame($head->id, (int) AttendanceEntry::sole()->amended_by);
        $lead->update(['grade_level_id' => (int) GradeLevel::where('code', '8')->value('id')]);
        $this->denied(403, fn () => app(Registers::class)->sync($head, $matrix['register_id'], $write));
        $lead->update(['grade_level_id' => $this->grade, 'ends_on' => '2027-01-09']);
        $this->denied(403, fn () => app(Registers::class)->matrix($head, $matrix['register_id']));
    }

    public function test_cutoff_alerts_are_current_once_per_day_and_corrections_suppress_absence(): void
    {
        $matrix = $this->open();
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix, 'absent'));
        $this->assertSame(0, app(RegisterNotices::class)->due());
        $this->travelTo(CarbonImmutable::parse('2026-02-10 10:01:00', 'Africa/Johannesburg'));
        $this->assertGreaterThan(0, app(RegisterNotices::class)->due());
        $this->assertSame(0, app(RegisterNotices::class)->due());
        $this->assertDatabaseHas('notifications', ['type' => 'attendance.absence', 'school_id' => 1]);
        $matrix = app(Registers::class)->matrix($this->tara, $matrix['register_id']);
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix, 'present', ['reason' => 'Learner arrived late']));
        $this->assertSame(0, app(RegisterNotices::class)->due());
    }

    public function test_period_attendance_is_distinct_and_never_replaces_the_daily_register(): void
    {
        $daily = $this->open();
        app(Registers::class)->sync($this->tara, $daily['register_id'], $this->write($daily));
        $service = app(TeachingGroups::class);
        $group = $service->save($this->superAdmin(), ['name' => 'Register Maths', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'class_section_id' => null, 'active' => true]);
        $teacher = (int) Teacher::where('user_id', $this->tara->id)->value('id');
        $dates = ['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Period register'];
        $service->dated($this->superAdmin(), 'teachers', $dates + ['teacher_id' => $teacher], $group->id);
        $service->dated($this->superAdmin(), 'memberships', $dates + ['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'catch_up'], $group->id);
        $config = app(TimetableConfiguration::class);
        $room = $config->room($this->superAdmin(), ['name' => 'Register room', 'active' => true, 'type' => 'classroom']);
        $bells = $config->bells($this->superAdmin(), ['session_year_id' => $this->year, 'name' => 'Register bells', 'section' => 'Senior', 'periods' => [['name' => 'Early', 'starts_at' => '06:00', 'ends_at' => '06:45', 'is_break' => false]]]);
        $engine = app(TimetableEngine::class);
        $preview = $engine->preview($this->superAdmin(), [['session_year_id' => $this->year, 'teaching_group_id' => $group->id, 'class_section_id' => null, 'teacher_id' => $teacher, 'room_id' => $room->id, 'bell_period_id' => $bells->periods->sole()->id, 'starts_on' => '2026-02-10', 'ends_on' => '2026-02-10', 'weekday' => 2, 'week_pattern' => 'all', 'active' => true]]);
        $lesson = $engine->save($this->superAdmin(), $preview['rows'], $preview['fingerprint'])['lessons'][0];
        $period = app(Registers::class)->open($this->tara, ['session_year_id' => $this->year, 'timetable_lesson_id' => $lesson['id'], 'on_date' => '2026-02-10']);
        app(Registers::class)->sync($this->tara, $period['register_id'], $this->write($period, 'absent'));
        $otherRoom = $config->room($this->superAdmin(), ['name' => 'Replacement room', 'active' => true, 'type' => 'classroom']);
        $move = $engine->preview($this->superAdmin(), [array_replace($lesson, ['room_id' => $otherRoom->id, 'ends_on' => '2026-02-17'])]);
        $lesson = $engine->save($this->superAdmin(), $move['rows'], $move['fingerprint'])['lessons'][0];
        $this->assertSame($otherRoom->id, $lesson['room_id']);
        $this->denied(409, fn () => $engine->preview($this->superAdmin(), [array_replace($lesson, ['starts_on' => '2026-02-17'])]));
        $cancel = $engine->preview($this->superAdmin(), [array_replace($lesson, ['active' => false])]);
        $engine->save($this->superAdmin(), $cancel['rows'], $cancel['fingerprint']);
        $this->assertSame('absent', app(Registers::class)->matrix($this->superAdmin(), $period['register_id'])['learners'][0]['state']);
        $this->assertSame(2, AttendanceEntry::count());
        $this->assertSame(1, Attendance::whereNotNull('attendance_entry_id')->count());
        $this->assertSame(Attendance::TYPE_PRESENT, (int) Attendance::whereNotNull('attendance_entry_id')->sole()->type);
        $this->travelTo(CarbonImmutable::parse('2026-02-10 10:01:00', 'Africa/Johannesburg'));
        $this->assertSame(0, app(RegisterNotices::class)->due());
    }

    public function test_canonical_daily_rows_refuse_old_model_import_and_teacher_writers(): void
    {
        $matrix = $this->open();
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix));
        $row = Attendance::whereNotNull('attendance_entry_id')->sole();
        $this->denied(409, fn () => $row->forceFill(['type' => 0])->save());
        $this->denied(409, fn () => $row->delete());
        $this->denied(409, fn () => (new AttendanceImport($this->schoolA->ids['section_a']))->collection(collect([['student_id' => $this->schoolA->ids['asha'], 'date' => '2026-02-10', 'type' => 0]])));
        $this->app['auth']->forgetGuards();
        $this->withToken($this->tara->createToken('legacy-register')->plainTextToken)->withHeader('X-School-Id', '1');
        $this->postJson('/api/teacher/submit-attendance', ['class_section_id' => $this->schoolA->ids['section_a'], 'date' => '2026-02-10', 'attendance' => [['student_id' => $this->schoolA->ids['asha'], 'type' => 0]]])->assertStatus(409);
        $this->assertSame(Attendance::TYPE_PRESENT, (int) $row->fresh()->type);
        $this->assertSame('present', AttendanceEntry::sole()->state);
    }

    public function test_failed_batches_are_atomic_and_completed_replays_still_require_current_allocation(): void
    {
        $matrix = $this->open();
        $data = $this->write($matrix);
        $invalid = $data;
        $invalid['rows'][] = ['learner_id' => $this->schoolA->ids['ben'], 'state' => 'absent', 'source' => 'offline', 'expected_revision' => 0];
        $this->denied(404, fn () => app(Registers::class)->sync($this->tara, $matrix['register_id'], $invalid));
        $this->assertSame(0, AttendanceEntry::count());
        $this->assertSame(0, AttendanceOperation::count());
        $this->assertSame(0, AttendanceRegister::findOrFail($matrix['register_id'])->revision);
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $data);
        HomeroomAllocation::where('class_section_id', $this->schoolA->ids['section_a'])->update(['ends_on' => '2026-02-09']);
        $this->denied(403, fn () => app(Registers::class)->sync($this->tara, $matrix['register_id'], $data));
        $this->assertSame(1, AttendanceOperation::count());
    }

    public function test_api_capture_replay_authority_and_signed_qr_rotation_are_scoped_before_any_response(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->withToken($this->tara->createToken('academic-register-api')->plainTextToken)->withHeader('X-School-Id', '1');
        $this->getJson('/api/v2/academics/registers/choices')->assertOk()->assertJsonPath('data.can_manage', false)->assertJsonPath('data.homerooms.0.id', $this->schoolA->ids['section_a']);
        $this->getJson('/api/v2/academics/registers/settings')->assertForbidden();
        $this->postJson('/api/v2/academics/registers/open', ['session_year_id' => $this->year, 'class_section_id' => $this->schoolB->ids['section_a'], 'on_date' => '2026-02-10'])->assertNotFound();
        $matrix = $this->postJson('/api/v2/academics/registers/open', ['session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'on_date' => '2026-02-10'])->assertOk()->json('data');
        $input = $this->write($matrix);
        $path = '/api/v2/academics/registers/'.$matrix['register_id'].'/sync';
        $this->postJson($path, $input)->assertOk()->assertJsonPath('data.revision', 1);
        $this->postJson($path, $input)->assertOk()->assertJsonPath('data.revision', 1);
        $this->withHeader('X-School-Id', '2')->postJson($path, $input)->assertForbidden();
        $this->withHeader('X-School-Id', '1');
        HomeroomAllocation::where('class_section_id', $this->schoolA->ids['section_a'])->update(['ends_on' => '2026-02-09']);
        $this->postJson($path, $input)->assertForbidden();
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->withToken($parent->createToken('register-qr')->plainTextToken)->withHeader('X-School-Id', '1');
        $qrPath = '/api/v2/academics/registers/learners/'.$this->schoolA->ids['asha'].'/qr';
        $old = $this->getJson($qrPath)->assertOk()->json('data.qr_code');
        $new = $this->withHeader('Idempotency-Key', 'rotate-learner-qr')->postJson($qrPath.'/rotate')->assertOk()->json('data.qr_code');
        $this->assertNotSame($old, $new);
        $this->assertSame($new, $this->postJson($qrPath.'/rotate')->assertOk()->json('data.qr_code'));
        $this->assertSame(2, (int) Students::findOrFail($this->schoolA->ids['asha'])->attendance_qr_version);
        $this->getJson('/api/v2/academics/registers/learners/'.$this->schoolA->ids['ben'].'/qr')->assertNotFound();
        $this->postJson('/api/v2/academics/registers/open', ['session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'on_date' => '2026-02-10'])->assertForbidden();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $parent->id], ['relationship' => 'father', 'status' => 'ended']);
        $this->postJson($qrPath.'/rotate')->assertNotFound();
    }

    public function test_approved_leave_suppresses_alerts_and_transport_rechecks_a_revoked_guardian(): void
    {
        $matrix = $this->open();
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix, 'absent'));
        $leave = Leave::create(['user_id' => $this->schoolA->ids['asha_user'], 'session_year_id' => $this->year, 'from_date' => '2026-02-10', 'to_date' => '2026-02-10', 'status' => 1, 'reason' => 'Approved register leave']);
        $leave->leave_detail()->create(['date' => '2026-02-10', 'type' => 'Full']);
        $this->travelTo(CarbonImmutable::parse('2026-02-10 10:01:00', 'Africa/Johannesburg'));
        $this->assertSame(0, app(RegisterNotices::class)->due());
        $this->assertSame(0, AttendanceNotice::count());
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $delivery = new NotificationDelivery(['school_id' => 1, 'recipient_user_id' => $parent->id, 'recipient_hash' => DeliveryLog::hash($parent->email)]);
        $guard = app(DeliveryGuard::class);
        $context = ['register_learner_id' => $this->schoolA->ids['asha']];
        $this->assertTrue($guard->allows($delivery, 'attendance.absence', 'email', $parent->email, $context));
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $parent->id], ['relationship' => 'father', 'status' => 'ended']);
        $this->assertFalse($guard->allows($delivery, 'attendance.absence', 'email', $parent->email, $context));
    }

    public function test_school_cutoff_settings_are_scoped_and_stale_amendment_events_do_not_send_old_states(): void
    {
        $matrix = $this->open();
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix));
        $matrix = app(Registers::class)->matrix($this->tara, $matrix['register_id']);
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix, 'late', ['reason' => 'Amendment one']));
        $matrix = app(Registers::class)->matrix($this->tara, $matrix['register_id']);
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix, 'excused', ['reason' => 'Amendment two']));
        $entry = AttendanceEntry::sole();
        $this->assertSame(0, app(RegisterNotices::class)->entry($entry->id, 'amended', 2));
        $this->assertGreaterThan(0, app(RegisterNotices::class)->entry($entry->id, 'amended', 3));
        $this->assertSame(0, app(RegisterNotices::class)->entry($entry->id, 'amended', 3));
        $settings = app(RegisterSettings::class);
        $this->denied(403, fn () => $settings->save($this->tara, ['absence_cutoff' => '09:15', 'teaching_days' => [1, 2, 3]]));
        $this->assertSame('09:15', $settings->save($this->superAdmin(), ['absence_cutoff' => '09:15', 'teaching_days' => [1, 2, 3]])['absence_cutoff']);
        app(CurrentSchool::class)->run(2, fn () => $this->assertSame('10:00', app(RegisterSettings::class)->get()['absence_cutoff']));
    }

    public function test_correction_reasons_are_encrypted_in_storage_and_audit_snapshots(): void
    {
        $matrix = $this->open();
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix));
        $matrix = app(Registers::class)->matrix($this->tara, $matrix['register_id']);
        $reason = 'Verified private correction explanation';
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix, 'late', ['reason' => $reason]));
        $this->assertSame($reason, AttendanceEntry::sole()->reason);
        $this->assertNotSame($reason, DB::table('attendance_entries')->value('reason'));
        $this->assertStringNotContainsString($reason, json_encode(DB::table('audit_log')->where('action', 'academics.register_amended')->get()->all()));
    }

    public function test_existing_half_day_registers_remain_visible_and_corrections_adopt_the_original_row(): void
    {
        $legacy = new Attendance;
        $legacy->forceFill(['student_id' => $this->schoolA->ids['asha'], 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->year, 'date' => '2026-02-10', 'type' => Attendance::TYPE_FIRST_HALF, 'status' => 1])->save();
        $matrix = $this->open();
        $this->assertSame('first_half', $matrix['learners'][0]['state']);
        $this->assertSame('legacy', $matrix['learners'][0]['source']);
        $this->assertSame(0, AttendanceEntry::count());
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->app['auth']->forgetGuards();
        $this->withToken($parent->createToken('existing-register')->plainTextToken)->withHeader('X-School-Id', '1');
        $this->getJson('/api/v2/academics/registers/learners/'.$this->schoolA->ids['asha'].'/today')->assertOk()->assertJsonPath('data.entries.0.state', 'first_half')->assertJsonPath('data.entries.0.register_id', null);
        try {
            app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix));
            $this->fail('A legacy correction needs a reason');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('reason', $error->errors());
        }
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix, 'present', ['reason' => 'Verified original register correction']));
        $this->assertSame($legacy->id, Attendance::whereNotNull('attendance_entry_id')->sole()->id);
        $this->assertSame($this->tara->id, (int) AttendanceEntry::sole()->amended_by);
    }

    public function test_half_day_capture_retains_the_existing_fractional_attendance_report_contract(): void
    {
        $matrix = $this->open();
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix, 'second_half'));
        $this->assertSame(Attendance::TYPE_SECOND_HALF, (int) Attendance::whereNotNull('attendance_entry_id')->sole()->type);
        $report = Attendance::monthlyReport($this->schoolA->ids['asha'], $this->year, 2, 2026);
        $this->assertSame(0.5, $report['total_present']);
        $this->assertSame(0.5, $report['total_absent']);
    }

    public function test_physical_enrolment_and_historical_rosters_do_not_activate_or_hide_a_disabled_app_account(): void
    {
        User::whereKey($this->schoolA->ids['asha_user'])->update(['status' => 0]);
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->update(['ends_on' => '2026-02-10']);
        $matrix = $this->open();
        $this->assertSame($this->schoolA->ids['asha'], $matrix['learners'][0]['learner_id']);
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix));
        $this->travelTo(CarbonImmutable::parse('2026-02-11 08:00:00', 'Africa/Johannesburg'));
        $history = app(Registers::class)->matrix($this->tara, $matrix['register_id']);
        $this->assertSame('present', $history['learners'][0]['state']);
        $this->assertFalse($history['can_write']);
        $this->assertSame(0, (int) User::findOrFail($this->schoolA->ids['asha_user'])->status);
    }

    public function test_audited_canonical_presence_is_not_replaced_by_an_old_leave_overlay_in_existing_reports(): void
    {
        $matrix = $this->open();
        $leave = Leave::create(['user_id' => $this->schoolA->ids['asha_user'], 'session_year_id' => $this->year, 'from_date' => '2026-02-10', 'to_date' => '2026-02-10', 'status' => 1, 'reason' => 'Earlier approved leave']);
        $leave->leave_detail()->create(['date' => '2026-02-10', 'type' => 'Full']);
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix, 'present'));
        $report = Attendance::monthlyReport($this->schoolA->ids['asha'], $this->year, 2, 2026);
        $this->assertSame(1.0, $report['total_present']);
        $this->assertSame(0.0, $report['total_absent']);
    }

    public function test_queued_absence_mail_and_push_are_suppressed_after_correction_or_later_leave_approval(): void
    {
        Queue::fake();
        Mail::fake();
        $fcm = $this->mock(FcmClient::class, fn ($mock) => $mock->shouldNotReceive('send'));
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $parent->forceFill(['email_verified_at' => now(), 'fcm_id' => 'synthetic-register-device', 'device_type' => 'android'])->save();
        $matrix = $this->open();
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix, 'absent'));
        $this->travelTo(CarbonImmutable::parse('2026-02-10 10:01:00', 'Africa/Johannesburg'));
        $this->assertGreaterThan(0, app(RegisterNotices::class)->due());
        Queue::assertPushed(SendMail::class);
        Queue::assertPushed(SendPush::class);
        $matrix = app(Registers::class)->matrix($this->tara, $matrix['register_id']);
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix, 'present', ['reason' => 'Learner arrived before delivery']));
        foreach (Queue::pushed(SendMail::class) as $job) {
            $job->handle();
        }
        foreach (Queue::pushed(SendPush::class) as $job) {
            $job->handle($fcm);
        }
        Mail::assertNothingSent();
        $matrix = app(Registers::class)->matrix($this->tara, $matrix['register_id']);
        app(Registers::class)->sync($this->tara, $matrix['register_id'], $this->write($matrix, 'absent', ['reason' => 'A new verified absence']));
        $this->assertGreaterThan(0, app(RegisterNotices::class)->due());
        $this->assertSame(0, app(RegisterNotices::class)->due());
        $leave = Leave::create(['user_id' => $this->schoolA->ids['asha_user'], 'session_year_id' => $this->year, 'from_date' => '2026-02-10', 'to_date' => '2026-02-10', 'status' => 1, 'reason' => 'Approved while notification waited']);
        $leave->leave_detail()->create(['date' => '2026-02-10', 'type' => 'Full']);
        foreach (Queue::pushed(SendMail::class) as $job) {
            $job->handle();
        }
        foreach (Queue::pushed(SendPush::class) as $job) {
            $job->handle($fcm);
        }
        Mail::assertNothingSent();
    }

    public function test_panel_captures_native_and_json_registers_and_requires_explicit_conflict_review(): void
    {
        $matrix = $this->open();
        $this->actingAs($this->tara)->get(route('academic-registers.index', ['register' => $matrix['register_id']]))
            ->assertOk()->assertSee('Capture register')->assertSee('Mark remaining present')->assertHeader('Cache-Control', 'no-store, private');
        $body = $this->write($matrix) + ['register_id' => $matrix['register_id'], 'expected_row_count' => '1', 'records' => [
            $this->schoolA->ids['asha'] => ['state' => 'present', 'expected_revision' => 0],
        ]];
        unset($body['rows']);
        $this->post(route('academic-registers.sync'), $body)->assertRedirect(route('academic-registers.index', ['register' => $matrix['register_id']]));
        $this->assertSame('present', AttendanceEntry::firstOrFail()->state);
        $body['operation_id'] = (string) Str::uuid();
        $body['records'][$this->schoolA->ids['asha']]['state'] = 'absent';
        $this->post(route('academic-registers.sync'), $body)->assertStatus(409)->assertSee('Prepare reviewed draft')->assertSee('Your entry');
        $this->assertSame('present', AttendanceEntry::firstOrFail()->state);
        $review = ['register_id' => $matrix['register_id'], 'expected_row_count' => 1, 'payload' => json_encode([['learner_id' => $this->schoolA->ids['asha'], 'state' => 'absent']]), 'reason' => 'Verified late correction'];
        $this->post(route('academic-registers.review'), $review)->assertOk()->assertSee('Reviewed entries are a draft');
        $this->assertSame('present', AttendanceEntry::firstOrFail()->state);
        $fresh = app(Registers::class)->matrix($this->tara, $matrix['register_id']);
        $write = $this->write($fresh, 'absent', ['reason' => 'Verified late correction']);
        $this->post(route('academic-registers.sync'), $write + ['register_id' => $matrix['register_id'], 'expected_row_count' => 1, 'payload' => json_encode($write['rows'])])->assertRedirect();
        $this->assertSame('absent', AttendanceEntry::firstOrFail()->state);
    }

    public function test_panel_rejects_truncated_native_batches_without_partial_writes_and_does_not_guess_absence(): void
    {
        $matrix = $this->open();
        $body = $this->write($matrix) + ['register_id' => $matrix['register_id'], 'expected_row_count' => 2, 'records' => [
            $this->schoolA->ids['asha'] => ['state' => 'present', 'expected_revision' => 0],
        ]];
        unset($body['rows']);
        $this->actingAs($this->tara)->postJson(route('academic-registers.sync'), $body)->assertUnprocessable()->assertJsonValidationErrors('received_row_count');
        $this->assertSame(0, AttendanceEntry::count());
        $body['expected_row_count'] = 1;
        $body['records'][$this->schoolA->ids['asha']]['state'] = 'unmarked';
        $this->postJson(route('academic-registers.sync'), $body)->assertUnprocessable()->assertJsonValidationErrors('rows');
        $this->assertSame(0, AttendanceEntry::count());
        $this->get(route('academic-registers.index', ['register' => $matrix['register_id']]))->assertOk()->assertSee('Unmarked');
    }

    public function test_panel_checks_school_role_owner_and_fresh_correction_rights(): void
    {
        $matrix = $this->open();
        $this->actingAs($this->tara)->postJson(route('academic-registers.settings'), ['absence_cutoff' => '11:00', 'teaching_days' => [1]])->assertForbidden();
        $foreign = User::withoutGlobalScopes()->findOrFail($this->schoolB->ids['tara_user']);
        $this->actingAs($foreign)->getJson(route('academic-registers.index', ['register' => $matrix['register_id']]))->assertStatus(404);
        $this->actingAs(User::withoutGlobalScopes()->findOrFail($this->schoolA->ids['asha_user']))->getJson(route('academic-registers.index'))->assertRedirect()->assertSessionHasErrors();
        $this->actingAs($this->tara);
        HomeroomAllocation::query()->delete();
        $body = $this->write($matrix) + ['register_id' => $matrix['register_id'], 'expected_row_count' => 1, 'payload' => json_encode($this->write($matrix)['rows'])];
        $this->postJson(route('academic-registers.sync'), $body)->assertForbidden();
        $this->getJson(route('academic-registers.index', ['register' => $matrix['register_id']]))->assertForbidden();
        $this->assertSame(0, AttendanceEntry::count());
    }
}
