<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\ExamMarks;
use App\Models\FeesPaid;
use App\Models\Platform\AuditLog;
use App\Models\Settings;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use LogicException;
use Tests\TestCase;

/**
 * audit_log records who created, changed and deleted people, marks, fee
 * payments and settings, without passwords, tokens or other hidden values,
 * and cannot be rewritten.
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        AuditLog::query()->toBase()->delete();
    }

    public function test_users_are_audited_without_passwords_or_tokens(): void
    {
        $this->actingAs($admin = $this->superAdmin());

        $user = User::create([
            'first_name' => 'Tara', 'last_name' => 'Teacher', 'email' => 'tara@example.org',
            'password' => 'Secret#Password1', 'fcm_id' => 'device-token-123', 'mobile' => '0', 'status' => 1,
        ]);
        $user->forceFill(['remember_token' => 'remember-me-token', 'fcm_id' => 'device-token-456'])->save();
        $user->update(['first_name' => 'Tamsin', 'password' => 'Another#Password2']);
        $user->delete();
        $user->restore();
        $user->forceDelete();

        $rows = $this->rowsFor($user);
        // Soft-deleting a user frees the address (User::booted()), and restoring
        // saves that change: it is recorded like any other.
        $this->assertSame(['created', 'updated', 'deleted', 'updated', 'restored', 'force_deleted'], $rows->pluck('action')->all());
        $this->assertStringStartsWith('tara@example.org::deleted-', $rows[3]->after['email']);
        $this->assertSame('Tara', $rows[0]->after['first_name']);
        $this->assertSame(['first_name' => 'Tara'], $rows[1]->before);
        $this->assertSame(['first_name' => 'Tamsin'], $rows[1]->after);
        $this->assertSame('tara@example.org', $rows[2]->before['email']);
        $this->assertSame($admin->id, $rows[0]->actor_id);
        $this->assertSame('user', $rows[0]->actor_type);
        $this->assertSame(User::class, $rows[0]->subject_type);

        $all = json_encode(AuditLog::all()->toArray());
        foreach (['Secret#Password1', 'Another#Password2', 'remember-me-token', 'device-token', 'password', 'remember_token', 'fcm_id'] as $secret) {
            $this->assertStringNotContainsString($secret, $all);
        }
        $this->assertStringNotContainsString((string) DB::table('users')->where('id', $admin->id)->value('password'), $all);
    }

    public function test_learners_are_audited(): void
    {
        $student = Students::create([
            'user_id' => $this->superAdmin()->id, 'category_id' => 1, 'admission_no' => 'A-1', 'admission_date' => '2026-01-15', 'roll_number' => 4,
        ]);
        $student->update(['roll_number' => 5]);
        $student->delete();

        $rows = $this->rowsFor($student);
        $this->assertSame(['created', 'updated', 'deleted'], $rows->pluck('action')->all());
        $this->assertSame('A-1', $rows[0]->after['admission_no']);
        $this->assertSame(['roll_number' => 4], $rows[1]->before);
        $this->assertSame(['roll_number' => 5], $rows[1]->after);
        $this->assertSame('system', $rows[0]->actor_type);
        $this->assertNull($rows[0]->actor_id);
    }

    public function test_marks_are_audited(): void
    {
        $marks = ExamMarks::forceCreate([
            'exam_timetable_id' => 1, 'student_id' => 1, 'subject_id' => 1, 'obtained_marks' => 61, 'passing_status' => 1, 'session_year_id' => 1, 'grade' => 'B',
        ]);
        $marks->forceFill(['obtained_marks' => 72, 'grade' => 'A'])->save();
        $marks->forceDelete();

        $rows = $this->rowsFor($marks);
        $this->assertSame(['created', 'updated', 'force_deleted'], $rows->pluck('action')->all());
        $this->assertSame(['obtained_marks' => 61, 'grade' => 'B'], $rows[1]->before);
        $this->assertSame(['obtained_marks' => 72, 'grade' => 'A'], $rows[1]->after);
        $this->assertSame(72, $rows[2]->before['obtained_marks']);
    }

    public function test_fee_payments_are_audited(): void
    {
        $paid = FeesPaid::forceCreate([
            'student_id' => 1, 'class_id' => 1, 'mode' => 0, 'total_amount' => 1500, 'is_fully_paid' => 0, 'date' => '2026-02-01', 'session_year_id' => 1,
        ]);
        $paid->forceFill(['is_fully_paid' => 1])->save();
        $paid->delete();

        $rows = $this->rowsFor($paid);
        $this->assertSame(['created', 'updated', 'deleted'], $rows->pluck('action')->all());
        $this->assertSame(['is_fully_paid' => 0], $rows[1]->before);
        $this->assertSame(['is_fully_paid' => 1], $rows[1]->after);
    }

    public function test_settings_are_audited_and_secret_values_are_not_kept(): void
    {
        $setting = Settings::create(['type' => 'school_tagline', 'message' => 'Learn well']);
        $setting->update(['message' => 'Learn better']);
        $secret = Settings::create(['type' => 'mail_password', 'message' => 'smtp-secret-value']);
        $secret->update(['message' => 'new-smtp-secret']);
        $setting->delete();

        $rows = $this->rowsFor($setting);
        $this->assertSame(['created', 'updated', 'deleted'], $rows->pluck('action')->all());
        $this->assertSame(['message' => 'Learn well'], $rows[1]->before);
        $this->assertSame(['message' => 'Learn better'], $rows[1]->after);

        $secretRows = $this->rowsFor($secret);
        $this->assertSame(['created', 'updated'], $secretRows->pluck('action')->all());
        $this->assertSame(['message' => '[redacted]'], $secretRows[1]->after);
        $this->assertStringNotContainsString('smtp-secret', json_encode(AuditLog::all()->toArray()));
    }

    public function test_explicit_actions_record_the_actor_address_and_request(): void
    {
        Route::post('/_audit-test', function () {
            $setting = Settings::where('type', 'email_configration_verification')->first() ?? Settings::create(['type' => 'email_configration_verification', 'message' => '0']);
            Audit::record('settings.mail_verified', $setting, ['verified' => false], ['verified' => true]);

            return response()->noContent();
        })->middleware(['web', 'auth', 'school.context']);
        AuditLog::query()->toBase()->delete();
        $admin = $this->superAdmin();
        // A new request starts without the request id the seeding above made.
        Context::flush();

        $this->actingAs($admin)->withHeaders(['X-Request-Id' => 'req-1234abcd'])->post('/_audit-test')->assertNoContent();

        $row = AuditLog::where('action', 'settings.mail_verified')->sole();
        $this->assertSame($admin->id, $row->actor_id);
        $this->assertSame('user', $row->actor_type);
        $this->assertSame(Settings::class, $row->subject_type);
        $this->assertSame(['verified' => false], $row->before);
        $this->assertSame(['verified' => true], $row->after);
        $this->assertSame('127.0.0.1', $row->ip);
        $this->assertSame('req-1234abcd', $row->request_id);
        $this->assertNull($row->school_id);
    }

    public function test_an_explicit_action_needs_no_subject(): void
    {
        $row = Audit::record('platform.cache_cleared');

        $this->assertNotNull($row);
        $this->assertNull($row->subject_type);
        $this->assertNull($row->subject_id);
        $this->assertNotEmpty($row->request_id);
    }

    public function test_an_update_of_hidden_attributes_only_writes_nothing(): void
    {
        $user = $this->superAdmin();
        $before = AuditLog::count();

        $user->forceFill(['password' => 'Changed#Password3', 'remember_token' => 'x'])->save();

        $this->assertSame($before, AuditLog::count());
    }

    public function test_audit_rows_cannot_be_changed_or_deleted(): void
    {
        $row = Audit::record('platform.test');

        try {
            $row->update(['action' => 'something.else']);
            $this->fail('An audit row was changed');
        } catch (LogicException) {
        }
        try {
            $row->delete();
            $this->fail('An audit row was deleted');
        } catch (LogicException) {
        }

        $this->assertSame('platform.test', $row->fresh()->action);
    }

    private function rowsFor(object $model)
    {
        return AuditLog::where('subject_type', $model::class)->where('subject_id', $model->getKey())->orderBy('id')->get();
    }
}
