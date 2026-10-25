<?php

declare(strict_types=1);

namespace Tests\Feature\Wellbeing;

use App\Jobs\Platform\FanoutNotification;
use App\Models\Notification;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\Students;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\Wellbeing\CaseAssignment;
use App\Models\Wellbeing\IncidentReport;
use App\Models\Wellbeing\NoticeIntent;
use App\Models\Wellbeing\TeamAssignment;
use App\Services\ParentPolicyService;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\DeliveryGuard;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\NotificationVisibility;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\StaffPrivateNotices;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Wellbeing\IncidentReporting;
use App\Services\Wellbeing\WellbeingNotices;
use App\Services\Wellbeing\WellbeingSettings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class WellbeingNoticesTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    private User $professional;

    private User $parent;

    private array $category;

    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        Queue::fake();
        Mail::fake();
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        app(ModuleGate::class)->change('wellbeing', true, $this->superAdmin());
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->schoolA->ids['session_year'], 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic private notice enrolment']);
        $this->professional = $this->professional('notice-counsellor@example.test');
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $settings = app(WellbeingSettings::class);
        $this->category = $settings->saveCategory($this->superAdmin(), null, $this->op(['revision' => 0, 'name' => 'PRIVATE category not for delivery', 'emergency_guidance' => 'Seek a trusted adult.', 'active' => true]));
        $settings->assignTeam($this->superAdmin(), $this->category['id'], null, $this->grant($this->professional));
        $this->secret = bin2hex(random_bytes(32));
    }

    private function professional(string $email): User
    {
        $user = User::create(['first_name' => 'PRIVATE', 'last_name' => 'Professional', 'email' => $email, 'password' => 'SyntheticOnly', 'status' => 1, 'password_change_required' => false, 'email_verified_at' => now()]);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $user->id, 'role' => 'Counsellor', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $user->assignRole('Counsellor');

        return $user;
    }

    private function op(array $input): array
    {
        return $input + ['operation_id' => (string) Str::uuid()];
    }

    private function grant(User $user): array
    {
        return $this->op(['revision' => 0, 'user_id' => $user->id, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'PRIVATE assignment reason']);
    }

    private function submit(bool $anonymous = true): int
    {
        app(IncidentReporting::class)->submit(['secret' => $this->secret, 'category_id' => $this->category['id'], 'body' => 'PRIVATE synthetic reporter body', 'urgency' => 'urgent', 'anonymous' => $anonymous,
            ...($anonymous ? [] : ['identify_consent' => true, 'learner_id' => $this->schoolA->ids['asha']])], $anonymous ? null : $this->parent);

        return (int) IncidentReport::firstOrFail()->id;
    }

    private function queue(NoticeIntent $intent): Notification
    {
        app(WellbeingNotices::class)->queue($intent->id);

        return Notification::findOrFail($intent->fresh()->notification_id);
    }

    private function fanout(Notification $notice, User $actor): void
    {
        (new FanoutNotification(1, (int) $notice->id, [(int) $actor->id]))->handle(app(CurrentSchool::class), app(Notifier::class));
    }

    private function visible(User $actor): int
    {
        return NotificationVisibility::forUser(Notification::where('type', 'like', 'wellbeing.%'), (int) $actor->id, 2)->count();
    }

    public function test_anonymous_report_creates_only_generic_staff_intent_and_retries_once(): void
    {
        $this->submit();
        $intent = NoticeIntent::sole();
        $this->assertSame('report_staff', $intent->kind);
        $this->assertSame((int) $this->professional->id, $intent->recipient_user_id);
        $this->assertNull(IncidentReport::firstOrFail()->reporter_user_id);
        $outbox = OutboxEvent::where('type', 'wellbeing.notice_requested')->sole();
        $this->assertSame(['intent_id' => $intent->id], $outbox->payload);
        $this->assertSame('dispatched', Outbox::dispatchOne((int) $outbox->id));
        $this->assertSame('skipped', Outbox::dispatchOne((int) $outbox->id));
        $notice = Notification::findOrFail($intent->fresh()->notification_id);
        $fanout = OutboxEvent::where('type', 'notification.requested')->where('payload->notification_id', $notice->id)->sole();
        $this->assertSame('dispatched', Outbox::dispatchOne((int) $fanout->id));
        Queue::assertPushed(FanoutNotification::class, 1);
        app(WellbeingNotices::class)->queue($intent->id);
        $this->assertSame(1, Notification::where('type', WellbeingNotices::STAFF)->count());
        $this->assertSame(['link' => '/wellbeing', 'wellbeing_intent_id' => $intent->id], $notice->delivery_payload);
        $plain = json_encode([$notice->title, $notice->message, $notice->delivery_payload, $outbox->payload], JSON_THROW_ON_ERROR);
        foreach (['PRIVATE', $this->secret, 'urgency', 'reporter_user_id', 'learner_id', 'incident_report_id', 'support_case_id'] as $private) {
            $this->assertStringNotContainsString($private, $plain);
        }
        $this->fanout($notice, $this->professional);
        $this->assertSame(1, $this->visible($this->professional));
        $this->assertSame(0, $this->visible($this->parent));
        $this->assertSame(1, UserNotification::where('notification_id', $notice->id)->count());
    }

    public function test_internal_reply_never_notifies_reporter_but_visible_reply_has_own_guardian_scope(): void
    {
        $report = $this->submit(false);
        app(IncidentReporting::class)->respond($this->professional, $report, $this->op(['revision' => 1, 'body' => 'PRIVATE internal triage', 'internal' => true, 'status' => 'triaged']));
        $this->assertSame(0, NoticeIntent::where('kind', 'report_reply')->count());
        app(IncidentReporting::class)->respond($this->professional, $report, $this->op(['revision' => 2, 'body' => 'PRIVATE reporter-visible reply', 'internal' => false, 'status' => 'triaged']));
        $intent = NoticeIntent::where('kind', 'report_reply')->sole();
        $notice = $this->queue($intent);
        $this->fanout($notice, $this->parent);
        $this->assertSame(1, $this->visible($this->parent));
        $this->assertTrue(app(WellbeingNotices::class)->allows($this->parent, WellbeingNotices::REPORTER, $notice->delivery_payload));
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->parent->id], ['status' => 'ended', 'relationship' => 'father']);
        $this->assertFalse(app(WellbeingNotices::class)->allows($this->parent, WellbeingNotices::REPORTER, $notice->delivery_payload));
        $this->assertSame(0, $this->visible($this->parent));
        $this->assertSame([], app(Notifier::class)->send($this->parent, WellbeingNotices::REPORTER, $notice->title, $notice->message, '/report-concern', $notice));
    }

    public function test_team_revocation_denies_fanout_existing_inbox_and_final_delivery(): void
    {
        $this->submit();
        $notice = $this->queue(NoticeIntent::sole());
        $this->fanout($notice, $this->professional);
        $types = config('notification_types');
        $types[WellbeingNotices::STAFF]['channels']['email'] = 'default_on';
        config(['notification_types' => $types]);
        $delivery = new NotificationDelivery(['school_id' => 1, 'recipient_user_id' => $this->professional->id, 'recipient_hash' => DeliveryLog::hash($this->professional->email)]);
        $guard = app(DeliveryGuard::class);
        $this->assertTrue($guard->allows($delivery, WellbeingNotices::STAFF, 'email', $this->professional->email, $notice->delivery_payload));
        TeamAssignment::query()->update(['active' => false]);
        $this->assertSame(0, $this->visible($this->professional));
        UserNotification::where('notification_id', $notice->id)->delete();
        $this->fanout($notice, $this->professional);
        $this->assertSame(0, UserNotification::where('notification_id', $notice->id)->count());
        $this->assertFalse($guard->allows($delivery, WellbeingNotices::STAFF, 'email', $this->professional->email, $notice->delivery_payload));
        $this->assertSame([], app(Notifier::class)->send($this->professional, WellbeingNotices::STAFF, $notice->title, $notice->message, '/wellbeing', $notice));
    }

    public function test_case_notices_need_their_own_current_assignment_not_an_incident_team_or_support_grant(): void
    {
        $caseUser = $this->professional('case-notice@example.test');
        $settings = app(WellbeingSettings::class);
        $case = $settings->openCase($this->superAdmin(), $this->op(['learner_id' => $this->schoolA->ids['asha']]));
        $settings->assignCase($this->superAdmin(), $case['id'], null, $this->grant($caseUser));
        $intent = NoticeIntent::where('kind', 'case_staff')->sole();
        $notice = $this->queue($intent);
        $this->assertTrue(app(WellbeingNotices::class)->allows($caseUser, WellbeingNotices::STAFF, $notice->delivery_payload));
        $this->assertFalse(app(WellbeingNotices::class)->allows($this->professional, WellbeingNotices::STAFF, $notice->delivery_payload));
        $this->fanout($notice, $caseUser);
        CaseAssignment::query()->update(['ends_on' => '2026-02-09']);
        $this->assertSame(0, $this->visible($caseUser));
        $this->assertFalse(app(WellbeingNotices::class)->allows($caseUser, WellbeingNotices::STAFF, $notice->delivery_payload));
    }

    public function test_password_platform_role_module_and_school_changes_deny_retained_private_notices(): void
    {
        $this->submit();
        $notice = $this->queue(NoticeIntent::sole());
        $this->fanout($notice, $this->professional);
        $service = app(WellbeingNotices::class);
        $this->professional->forceFill(['password_change_required' => true])->save();
        $this->assertFalse($service->allows($this->professional, WellbeingNotices::STAFF, $notice->delivery_payload));
        $this->assertSame(0, $this->visible($this->professional));
        $this->professional->forceFill(['password_change_required' => false])->save();
        PlatformStaffIdentity::create(['user_id' => $this->professional->id, 'active' => true]);
        $this->assertFalse($service->allows($this->professional, WellbeingNotices::STAFF, $notice->delivery_payload));
        PlatformStaffIdentity::where('user_id', $this->professional->id)->delete();
        SchoolModule::where('module', 'wellbeing')->update(['read_only' => true]);
        app(ModuleGate::class)->forget();
        $this->assertTrue($service->allows($this->professional, WellbeingNotices::STAFF, $notice->delivery_payload));
        SchoolModule::where('module', 'wellbeing')->update(['enabled' => false]);
        app(ModuleGate::class)->forget();
        $this->assertFalse($service->allows($this->professional, WellbeingNotices::STAFF, $notice->delivery_payload));
        SchoolModule::where('module', 'wellbeing')->update(['enabled' => true]);
        app(ModuleGate::class)->forget();
        $this->assertFalse(app(CurrentSchool::class)->run(2, fn () => $service->allows($this->professional, WellbeingNotices::STAFF, $notice->delivery_payload)));
        SchoolMembership::where('school_id', 1)->where('user_id', $this->professional->id)->update(['role' => 'Teacher']);
        $this->assertFalse($service->allows($this->professional, WellbeingNotices::STAFF, $notice->delivery_payload));
    }

    public function test_suppressed_intent_does_not_wake_after_regrant_and_rollback_preserves_no_intent(): void
    {
        $before = NoticeIntent::count();
        $events = OutboxEvent::where('type', 'wellbeing.notice_requested')->count();
        DB::beginTransaction();
        $this->submit();
        DB::rollBack();
        $this->assertSame($before, NoticeIntent::count());
        $this->assertSame($events, OutboxEvent::where('type', 'wellbeing.notice_requested')->count());
        $this->assertSame(0, IncidentReport::count());
        $this->submit();
        $intent = NoticeIntent::sole();
        TeamAssignment::query()->update(['active' => false]);
        app(WellbeingNotices::class)->queue($intent->id);
        $this->assertNull($intent->fresh()->notification_id);
        TeamAssignment::query()->update(['active' => true]);
        app(WellbeingNotices::class)->queue($intent->id);
        $this->assertNull($intent->fresh()->notification_id);
        $this->assertSame(0, Notification::where('type', WellbeingNotices::STAFF)->count());
    }

    public function test_code_reply_retry_and_migration_guard_preserve_unique_private_intent(): void
    {
        $this->submit();
        $input = $this->op(['body' => 'PRIVATE reporter follow-up']);
        app(IncidentReporting::class)->reply($this->secret, $input);
        $count = NoticeIntent::count();
        app(IncidentReporting::class)->reply($this->secret, $input);
        $this->assertSame($count, NoticeIntent::count());
        $migration = require database_path('migrations/2026_10_25_180000_create_wellbeing_notice_intents.php');
        try {
            $migration->down();
            $this->fail('Retained private notice intent deleted.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('Retained private wellbeing notice intents', $error->getMessage());
        }
        try {
            $row = NoticeIntent::firstOrFail()->getAttributes();
            $row['id'] = (string) Str::uuid();
            $row['school_id'] = 2;
            DB::table('wellbeing_notice_intents')->insert($row);
            $this->fail('Cross-school intent reference accepted.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }
    }

    public function test_student_approval_withdrawal_and_current_enrolment_gate_named_reply_notices(): void
    {
        config(['identity.approval_enforced' => true]);
        $learner = Students::findOrFail($this->schoolA->ids['asha']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $learner->id, 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
        app(ApprovalService::class)->decide($this->parent, $learner, 'approve', config('identity.consent_version'));
        $student = User::findOrFail($learner->user_id);
        app(IncidentReporting::class)->submit(['secret' => $this->secret, 'category_id' => $this->category['id'], 'body' => 'PRIVATE learner concern', 'urgency' => 'routine', 'anonymous' => false, 'identify_consent' => true, 'learner_id' => $learner->id], $student);
        $report = IncidentReport::firstOrFail();
        app(IncidentReporting::class)->respond($this->professional, (int) $report->id, $this->op(['revision' => 1, 'body' => 'PRIVATE learner reply', 'internal' => false, 'status' => 'triaged']));
        $notice = $this->queue(NoticeIntent::where('kind', 'report_reply')->sole());
        $this->fanout($notice, $student);
        $this->assertSame(1, $this->visible($student));
        $this->assertTrue(app(WellbeingNotices::class)->allows($student, WellbeingNotices::REPORTER, $notice->delivery_payload));
        app(ApprovalService::class)->decide($this->parent, $learner, 'withdraw', config('identity.consent_version'));
        $this->assertFalse(app(WellbeingNotices::class)->allows($student, WellbeingNotices::REPORTER, $notice->delivery_payload));
        $this->assertSame(0, $this->visible($student));
    }

    public function test_ended_enrolment_and_school_calendar_expiry_remove_current_notice_scope(): void
    {
        $report = $this->submit(false);
        app(IncidentReporting::class)->respond($this->professional, $report, $this->op(['revision' => 1, 'body' => 'PRIVATE family reply', 'internal' => false, 'status' => 'triaged']));
        $notice = $this->queue(NoticeIntent::where('kind', 'report_reply')->sole());
        $this->fanout($notice, $this->parent);
        $this->assertSame(1, $this->visible($this->parent));
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->update(['ends_on' => '2026-02-09']);
        $this->assertFalse(app(WellbeingNotices::class)->allows($this->parent, WellbeingNotices::REPORTER, $notice->delivery_payload));
        $this->assertSame(0, $this->visible($this->parent));
        $staff = $this->queue(NoticeIntent::where('kind', 'report_staff')->sole());
        $this->assertTrue(app(WellbeingNotices::class)->allows($this->professional, WellbeingNotices::STAFF, $staff->delivery_payload));
        TeamAssignment::query()->update(['ends_on' => '2026-02-10']);
        $this->travelTo(CarbonImmutable::parse('2026-02-10T22:30:00Z'));
        $this->assertFalse(app(WellbeingNotices::class)->allows($this->professional, WellbeingNotices::STAFF, $staff->delivery_payload));
    }

    private function api(User $actor, int $school = 1): self
    {
        $this->app['auth']->forgetGuards();
        if ($actor->id === $this->professional->id) {
            ParentPolicyService::acceptCurrent($actor);
        }
        $name = $actor->id === $this->professional->id ? AppTokens::FACILITATOR : 'wellbeing-notice-fixture';

        return $this->flushHeaders()->withToken($actor->createToken($name)->plainTextToken)->withHeaders(['X-School-Id' => (string) $school, 'X-Account-Id' => (string) $actor->id]);
    }

    public function test_actual_facilitator_inbox_and_first_read_receipt_have_no_teacher_dependency_or_private_source_fields(): void
    {
        $this->submit();
        $this->api($this->professional)->getJson('/api/v2/wellbeing/notices')->assertOk()->assertJsonCount(0, 'data.items');
        $notice = $this->queue(NoticeIntent::sole());
        $this->getJson('/api/v2/wellbeing/notices')->assertOk()->assertJsonCount(0, 'data.items');
        $this->fanout($notice, $this->professional);
        $page = $this->getJson('/api/v2/wellbeing/notices')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('data.unread_count', 1)->assertJsonPath('data.items.0.link', '/wellbeing')->assertJsonPath('data.items.0.read_at', null);
        foreach (['PRIVATE', 'incident_report_id', 'support_case_id', 'event_id', 'wellbeing_intent_id', $this->secret] as $private) {
            $this->assertStringNotContainsString($private, $page->getContent());
        }
        $first = $this->postJson('/api/v2/wellbeing/notices/'.$notice->id.'/read', [])->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data.read_at');
        $this->travel(2)->minutes();
        SchoolModule::where('module', 'wellbeing')->update(['read_only' => true]);
        app(ModuleGate::class)->forget();
        $this->postJson('/api/v2/wellbeing/notices/'.$notice->id.'/read', [])->assertOk()->assertJsonPath('data.read_at', $first);
        $this->getJson('/api/v2/wellbeing/notices')->assertOk()->assertJsonPath('data.unread_count', 0)->assertJsonPath('data.items.0.read_at', $first);
        $this->getJson('/api/v2/wellbeing/notices?page=0')->assertUnprocessable();
    }

    public function test_private_inbox_refuses_wrong_owner_context_and_revoked_source_before_receipt_replay(): void
    {
        $this->submit();
        $notice = $this->queue(NoticeIntent::sole());
        $this->fanout($notice, $this->professional);
        $this->api($this->parent)->getJson('/api/v2/wellbeing/notices')->assertOk()->assertJsonCount(0, 'data.items');
        $this->postJson('/api/v2/wellbeing/notices/'.$notice->id.'/read', [])->assertNotFound();
        $this->api($this->professional)->withHeader('X-Account-Id', (string) $this->parent->id)->getJson('/api/v2/wellbeing/notices')->assertForbidden();
        $this->api($this->professional, 2)->getJson('/api/v2/wellbeing/notices')->assertForbidden();
        $this->api($this->professional)->postJson('/api/v2/wellbeing/notices/'.$notice->id.'/read', [])->assertOk();
        TeamAssignment::query()->update(['active' => false]);
        $this->getJson('/api/v2/wellbeing/notices')->assertOk()->assertJsonPath('data.unread_count', 0)->assertJsonCount(0, 'data.items');
        $this->postJson('/api/v2/wellbeing/notices/'.$notice->id.'/read', [])->assertNotFound();
        $this->assertSame(1, NoticeIntent::whereNotNull('read_at')->count());
    }

    public function test_shared_staff_inbox_preserves_existing_intent_read_authority_and_source_revocation(): void
    {
        ParentPolicyService::acceptCurrent($this->professional);
        $this->submit();
        $firstIntent = NoticeIntent::sole();
        $first = $this->queue($firstIntent);
        $this->fanout($first, $this->professional);
        $this->secret = bin2hex(random_bytes(32));
        $this->submit();
        $secondIntent = NoticeIntent::whereKeyNot($firstIntent->id)->sole();
        $second = $this->queue($secondIntent);
        $this->fanout($second, $this->professional);
        $shared = app(StaffPrivateNotices::class);
        $legacy = app(WellbeingNotices::class);
        $this->assertSame(2, $shared->inbox($this->professional)['unread_count']);
        $legacy->markRead($this->professional, (int) $first->id);
        $this->assertSame(1, $shared->inbox($this->professional)['unread_count']);
        $this->assertNotNull(collect($shared->inbox($this->professional)['items'])->firstWhere('id', $first->id)['read_at']);
        $shared->markRead($this->professional, (int) $second->id);
        $this->assertNotNull($secondIntent->fresh()->read_at);
        $this->assertSame(0, $legacy->inbox($this->professional, area: 'staff')['unread_count']);
        $this->assertSame(0, $shared->inbox($this->professional)['unread_count']);
        TeamAssignment::query()->update(['active' => false]);
        $this->assertSame([], $shared->inbox($this->professional)['items']);
        $this->expectException(ModelNotFoundException::class);
        $shared->markRead($this->professional, (int) $second->id);
    }
}
