<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\FanoutNotification;
use App\Jobs\Platform\SendMail;
use App\Jobs\Platform\SendPush;
use App\Listeners\Platform\NotifyHandleGuardians;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\NotificationPreference;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Identity\Handles;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\FcmClient;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class NotifierTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
    }

    private function recipient(string $key = 'farid_user'): User
    {
        $user = User::findOrFail($this->schoolA->ids[$key]);
        $user->forceFill(['fcm_id' => 'device-'.$user->id, 'device_type' => 'android', 'email_verified_at' => now()])->save();

        return $user;
    }

    public function test_optional_choices_are_checked_before_each_delivery_is_queued(): void
    {
        $parent = $this->recipient();
        app(NotificationRules::class)->choose($parent, 'comms.notice', 'email', true);
        $sent = app(Notifier::class)->send($parent, 'comms.notice', 'Trip forms', 'Bring them back');
        $this->assertSame(['push', 'email', 'in_app'], $sent);
        Queue::assertPushed(SendPush::class, 1);
        Queue::assertPushed(SendMail::class, 1);
        $this->assertSame(2, UserNotification::query()->where('user_id', $parent->id)->count());
        Queue::assertPushed(SendPush::class, fn ($job) => $job->message['message']['data']['school_id'] === '1'
            && $job->message['message']['data']['link'] === '/notifications');
        $this->assertSame([1], DB::table('notification_deliveries')->pluck('school_id')->unique()->all());
    }

    public function test_optional_opt_out_prevents_push_email_and_new_inbox_entries(): void
    {
        $parent = $this->recipient();
        foreach (NotificationRules::CHANNELS as $channel) {
            app(NotificationRules::class)->choose($parent, 'comms.notice', $channel, false);
        }
        $this->assertSame([], app(Notifier::class)->send($parent, 'comms.notice', 'Notice', 'Optional'));
        Queue::assertNothingPushed();
        $this->assertSame(1, UserNotification::query()->where('user_id', $parent->id)->count());
    }

    public function test_a_mandatory_guardian_alert_ignores_a_stale_optional_preference(): void
    {
        $parent = $this->recipient();
        foreach (NotificationRules::CHANNELS as $channel) {
            NotificationPreference::query()->create(['user_id' => $parent->id, 'type' => 'attendance.absence', 'channel' => $channel, 'enabled' => false]);
        }
        $this->assertSame(['push', 'email', 'in_app'], app(Notifier::class)->send($parent, 'attendance.absence', 'Attendance alert', 'Open the register'));
        Queue::assertPushed(SendPush::class, 1);
        Queue::assertPushed(SendMail::class, 1);
    }

    public function test_disabled_modules_unknown_types_and_foreign_school_users_receive_nothing(): void
    {
        $parent = $this->recipient();
        app(ModuleGate::class)->change('chat', false, $this->superAdmin());
        $this->assertSame([], app(Notifier::class)->send($parent, 'comms.message', 'Private', 'Chat'));
        $this->assertSame([], app(Notifier::class)->send($parent, 'not.registered', 'Unknown', 'Notice'));
        app(CurrentSchool::class)->run(2, fn () => $this->assertSame([], app(Notifier::class)->send($parent, 'comms.notice', 'Wrong school', 'Private')));
        Queue::assertNothingPushed();
    }

    public function test_retrying_a_fanout_does_not_repeat_any_channel(): void
    {
        $parent = $this->recipient();
        app(NotificationRules::class)->choose($parent, 'comms.notice', 'email', true);
        $service = app(Notifier::class);
        $notice = $service->makeNotice('custom', 'Notice', 'One message', '/notifications');
        $service->send($parent, 'comms.notice', 'Notice', 'One message', '/notifications', $notice);
        $this->assertSame([], $service->send($parent, 'comms.notice', 'Notice', 'One message', '/notifications', $notice));
        Queue::assertPushed(SendPush::class, 1);
        Queue::assertPushed(SendMail::class, 1);
        $this->assertSame(3, DB::table('notification_dispatches')->where('notification_id', $notice->id)->count());
    }

    public function test_new_broadcasts_only_appear_for_allowed_recipients_while_history_remains(): void
    {
        $parent = $this->recipient();
        app(NotificationRules::class)->choose($parent, 'comms.notice', 'in_app', false);
        $legacyId = DB::table('notifications')->insertGetId([
            'school_id' => 1, 'title' => 'Historical notice', 'message' => 'Old', 'type' => 'custom', 'send_to' => 1,
            'is_custom' => 1, 'date' => now(), 'session_year_id' => $this->schoolA->ids['session_year'], 'created_at' => now(), 'updated_at' => now(),
        ]);
        $notice = app(Notifier::class)->makeNotice('custom', 'New optional notice', 'New', '/notifications');
        $notice->forceFill(['send_to' => 1])->save();
        app(Notifier::class)->send($parent, 'comms.notice', $notice->title, $notice->message, '/notifications', $notice);
        $answer = $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/parent/get-notification')->assertOk();
        $this->assertContains($legacyId, array_column($answer->json('data.data'), 'id'));
        $this->assertNotContains($notice->id, array_column($answer->json('data.data'), 'id'));
    }

    public function test_private_class_notices_do_not_become_whole_portal_broadcasts(): void
    {
        $notice = app(Notifier::class)->makeNotice('assignment', 'Only Asha', 'Private class work', '/notifications');
        $notice->forceFill(['send_to' => 3])->save();
        UserNotification::query()->create(['notification_id' => $notice->id, 'user_id' => $this->schoolA->ids['asha_user']]);
        $this->withToken($this->schoolA->tokens['ben'])->getJson('/api/student/get-notification')->assertOk()->assertDontSee('Only Asha');
        $this->app['auth']->forgetGuards();
        $this->withToken($this->schoolA->tokens['asha'])->getJson('/api/student/get-notification')->assertOk()->assertSee('Only Asha');
    }

    public function test_removed_guardians_and_separate_fee_rights_override_legacy_parent_fields(): void
    {
        $student = Students::findOrFail($this->schoolA->ids['asha']);
        $service = app(GuardianService::class);
        $service->syncLegacy($student);
        $father = $this->schoolA->ids['farid_user'];
        GuardianLink::query()->where('learner_id', $student->id)->where('guardian_user_id', $father)->update(['can_see_fees' => false]);
        $this->assertContains($father, $service->guardianUserIds($student)->all());
        $this->assertNotContains($father, $service->guardianUserIds($student, true)->all());
        GuardianLink::query()->where('learner_id', $student->id)->where('guardian_user_id', $father)->update(['status' => 'ended']);
        $this->assertNotContains($father, $service->guardianUserIds($student)->all());
    }

    public function test_unverified_mailboxes_get_a_generic_notice_without_child_details(): void
    {
        $parent = $this->recipient();
        $parent->forceFill(['email_verified_at' => null])->save();
        app(Notifier::class)->send($parent, 'attendance.absence', 'Attendance alert', 'Asha is absent today');
        Queue::assertPushed(SendMail::class, fn ($job) => $job->mailable->noticeBody === 'Open NavigateEd to view this notice.');
    }

    public function test_private_push_content_is_encrypted_in_the_queue(): void
    {
        $job = new SendPush(['message' => ['token' => 'private-device', 'data' => ['body' => 'private-message']]], 'test-key');
        $payload = (new \ReflectionMethod(\Illuminate\Queue\Queue::class, 'createPayload'))->invoke(Queue::getFacadeRoot()->queue->connection('database'), $job, 'default');
        $this->assertStringNotContainsString('private-device', $payload);
        $this->assertStringNotContainsString('private-message', $payload);
        $this->assertIsString(app('encrypter')->decrypt(json_decode($payload, true)['data']['command']));
    }

    public function test_legacy_helpers_record_durable_intent_and_preserve_chat_metadata(): void
    {
        $parent = $this->recipient();
        sendSimpleNotification([$parent->id], 'Message', 'Hello', 'chat', null, ['id' => 7]);
        Outbox::dispatchPending();
        foreach (Queue::pushed(FanoutNotification::class) as $job) {
            app()->call([$job, 'handle']);
        }
        Queue::assertPushed(SendPush::class, fn ($job) => $job->message['message']['data']['sender_info'] === json_encode(['id' => 7])
            && $job->message['message']['data']['link'] === '/chat');
        $this->assertSame(2, UserNotification::query()->where('user_id', $parent->id)->count());
    }

    public function test_worker_suppresses_a_notice_if_the_recipient_opts_out_after_queueing(): void
    {
        $parent = $this->recipient();
        app(NotificationRules::class)->choose($parent, 'comms.notice', 'email', true);
        app(Notifier::class)->send($parent, 'comms.notice', 'Trip', 'Return forms');
        app(NotificationRules::class)->choose($parent, 'comms.notice', 'push', false);
        app(NotificationRules::class)->choose($parent, 'comms.notice', 'email', false);
        Http::fake();
        Mail::fake();
        app()->call([Queue::pushed(SendPush::class)->first(), 'handle']);
        Queue::pushed(SendMail::class)->first()->handle();
        Http::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertSame(['suppressed'], DB::table('notification_deliveries')->pluck('status')->unique()->all());
    }

    public function test_worker_suppresses_ended_memberships_even_for_mandatory_notices(): void
    {
        $parent = $this->recipient();
        app(Notifier::class)->send($parent, 'attendance.absence', 'Register', 'Private');
        SchoolMembership::query()->where('school_id', 1)->where('user_id', $parent->id)->update(['status' => 'ended']);
        Http::fake();
        Mail::fake();
        app()->call([Queue::pushed(SendPush::class)->first(), 'handle']);
        Queue::pushed(SendMail::class)->first()->handle();
        Http::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertSame(['suppressed'], DB::table('notification_deliveries')->pluck('status')->unique()->all());
    }

    public function test_worker_never_uses_an_old_mailbox_or_device_after_a_profile_change(): void
    {
        $parent = $this->recipient();
        app(Notifier::class)->send($parent, 'attendance.absence', 'Register', 'Private');
        $parent->forceFill(['email' => 'new@example.org', 'fcm_id' => 'new-device'])->save();
        Http::fake();
        Mail::fake();
        app()->call([Queue::pushed(SendPush::class)->first(), 'handle']);
        Queue::pushed(SendMail::class)->first()->handle();
        Http::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertSame(['suppressed'], DB::table('notification_deliveries')->pluck('status')->unique()->all());
    }

    public function test_handle_request_notifies_only_verified_current_approving_guardians_and_deduplicates(): void
    {
        $parent = $this->recipient();
        $student = Students::findOrFail($this->schoolA->ids['asha']);
        app(GuardianService::class)->syncLegacy($student);
        User::findOrFail($student->user_id)->forceFill(['dob' => '2012-06-13'])->save();
        app(Handles::class)->request(User::findOrFail($student->user_id), 'orbit');
        Outbox::dispatchPending();
        $event = OutboxEvent::query()->where('type', 'handle.proposed')->latest('id')->firstOrFail();
        app(NotifyHandleGuardians::class)->handle($event);
        $rows = DB::table('notification_deliveries')->where('channel', 'mail')->get();
        $this->assertCount(1, $rows);
        $this->assertSame($parent->id, $rows->first()->recipient_user_id);
        Queue::assertPushed(SendMail::class, 1);
        Queue::assertPushed(SendPush::class, fn ($job) => ($job->message['message']['data']['link'] ?? null) === '/handles');
        // Rights can end between queueing and transport; even mandatory
        // approval notices must no longer be delivered to this guardian.
        GuardianLink::query()->where('learner_id', $student->id)->where('guardian_user_id', $parent->id)->update(['can_approve' => false]);
        Mail::fake();
        Queue::pushed(SendMail::class)->first()->handle();
        Mail::assertNothingSent();
        $this->assertDatabaseHas('notification_deliveries', ['id' => $rows->first()->id, 'status' => 'suppressed']);
    }

    public function test_database_queue_rows_and_recipient_claims_roll_back_together(): void
    {
        $parent = $this->recipient();
        app(NotificationRules::class)->choose($parent, 'comms.notice', 'email', true);
        Queue::swap(Queue::getFacadeRoot()->queue);
        config(['queue.default' => 'database']);
        $jobsBefore = DB::table('jobs')->count();
        $claimsBefore = DB::table('notification_dispatches')->count();
        DB::beginTransaction();
        try {
            app(Notifier::class)->send($parent, 'comms.notice', 'Confidential trip', 'Private itinerary');
            $this->assertSame($jobsBefore + 2, DB::table('jobs')->count(), 'Queue rows must already participate in the claim transaction.');
            $this->assertSame($claimsBefore + 3, DB::table('notification_dispatches')->count());
            foreach (DB::table('jobs')->pluck('payload') as $payload) {
                $this->assertStringNotContainsString('Private itinerary', $payload);
                $this->assertStringNotContainsString('Confidential trip', $payload);
                $this->assertStringNotContainsString($parent->fcm_id, $payload);
            }
        } finally {
            DB::rollBack();
        }
        $this->assertSame($jobsBefore, DB::table('jobs')->count());
        $this->assertSame($claimsBefore, DB::table('notification_dispatches')->count());
    }

    public function test_worker_uses_the_delivery_school_even_when_invoked_from_another_context(): void
    {
        $parent = $this->recipient();
        app(Notifier::class)->send($parent, 'comms.notice', 'Trip', 'Forms');
        $fcm = new class extends FcmClient
        {
            public ?int $school = null;

            public function send(array $message): array
            {
                $this->school = app(CurrentSchool::class)->id();

                return ['status' => 'sent', 'id' => 'fake-provider-id'];
            }
        };
        app(CurrentSchool::class)->run(2, function () use ($fcm) {
            Queue::pushed(SendPush::class)->first()->handle($fcm);
            $this->assertSame(1, $fcm->school);
            $this->assertSame(2, app(CurrentSchool::class)->id());
        });
        $this->assertDatabaseHas('notification_deliveries', ['channel' => 'push', 'school_id' => 1, 'status' => 'sent']);
    }
}
