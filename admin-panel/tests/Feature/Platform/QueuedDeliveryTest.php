<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\FanoutNotification;
use App\Jobs\Platform\SendMail;
use App\Jobs\Platform\SendPush;
use App\Mail\Platform\TemplateMail;
use App\Models\EmailTemplate;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\User;
use App\Services\EmailTemplateService;
use App\Services\MailService;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\FcmClient;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Tests\TestCase;

/**
 * Mail and push leave the web request: MailService and the FCM helpers queue
 * SendMail and SendPush jobs with the messages they always sent, and every
 * attempt is a notification_deliveries row without raw addresses or tokens.
 */
class QueuedDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        config(['mail.from.address' => 'noreply@example.org', 'mail.from.name' => 'Demo School']);
    }

    public function test_the_default_queue_is_the_database_and_tests_stay_sync(): void
    {
        $config = require base_path('config/queue.php');
        $this->assertSame('sync', config('queue.default'));
        $this->assertTrue(config('queue.connections.database.after_commit'));
        $this->assertGreaterThan(120, config('queue.connections.database.retry_after'));
        $this->assertStringContainsString("env('QUEUE_CONNECTION', 'database')", (string) file_get_contents(base_path('config/queue.php')));
        $this->assertStringContainsString("\nQUEUE_CONNECTION=database\n", (string) file_get_contents(base_path('.env.example')));
        $this->assertIsArray($config);
    }

    public function test_a_welcome_mail_is_queued_encrypted_with_a_delivery_row(): void
    {
        Queue::fake();

        $this->assertTrue(MailService::queue(TemplateMail::parentWelcome($this->welcomeData())->from('school@example.org', 'Demo School'), 42));

        Queue::assertPushed(SendMail::class, function (SendMail $job) {
            return $job->mailable instanceof TemplateMail && $job->tries === 3 && $job->backoff() === [60, 300, 900];
        });
        $this->assertInstanceOf(ShouldBeEncrypted::class, new SendMail(TemplateMail::parentWelcome($this->welcomeData()), 'key'));

        $row = NotificationDelivery::sole();
        $this->assertSame('queued', $row->status);
        $this->assertSame('mail', $row->channel);
        $this->assertSame('mail.student_registration', $row->type);
        $this->assertSame(42, $row->recipient_user_id);
        $this->assertSame(hash('sha256', 'father@example.org'), $row->recipient_hash);
        $this->assertStringNotContainsString('father@example.org', json_encode($row->toArray()));
    }

    public function test_the_queued_welcome_mail_has_the_same_subject_and_body_as_before(): void
    {
        $data = $this->welcomeData();
        $template = (string) EmailTemplate::where('type', 'student_registration')->value('content');
        $this->assertNotSame('', $template);

        $this->assertTrue(MailService::queue(TemplateMail::parentWelcome($data)->from('school@example.org', 'Demo School')));

        $messages = $this->sentMessages();
        $this->assertCount(1, $messages);
        $email = $messages[0]->getOriginalMessage();
        $this->assertSame('Welcome to Demo School', $email->getSubject());
        $this->assertSame('father@example.org', $email->getTo()[0]->getAddress());
        $this->assertSame('school@example.org', $email->getFrom()[0]->getAddress());
        $this->assertSame('Demo School', $email->getFrom()[0]->getName());
        $this->assertStringContainsString(EmailTemplateService::render($template, $data), (string) $email->getHtmlBody());

        $row = NotificationDelivery::sole();
        $this->assertSame('sent', $row->status);
        $this->assertSame('array', $row->provider);
        $this->assertNotEmpty($row->provider_message_id);
        $this->assertNotNull($row->sent_at);
    }

    public function test_a_failed_attempt_is_recorded_and_retried_with_a_row_per_attempt(): void
    {
        $this->useFailingMailer();
        Queue::fake();
        MailService::queue(TemplateMail::parentWelcome($this->welcomeData()));
        $job = Queue::pushed(SendMail::class)->first();
        Log::spy();

        foreach ([1, 2] as $attempt) {
            $job->withFakeQueueInteractions();
            $job->job->attempts = $attempt;
            try {
                $job->handle();
                $this->fail('The failing mailer should have thrown');
            } catch (TransportException) {
                // The worker releases the job for the next attempt.
            }
        }
        $job->failed(new TransportException('550 father@example.org rejected'));

        $rows = NotificationDelivery::orderBy('attempt')->get();
        $this->assertSame([1, 2], $rows->pluck('attempt')->all());
        $this->assertSame(['failed', 'failed'], $rows->pluck('status')->all());
        $this->assertCount(1, $rows->pluck('delivery_key')->unique());
        $this->assertStringContainsString('[address]', (string) $rows[0]->error_summary);
        $this->assertStringNotContainsString('father@example.org', (string) $rows[0]->error_summary);

        Log::shouldHaveReceived('error')->withArgs(fn (string $message, array $context = []) => $message === 'notification_delivery_abandoned'
            && $context['job'] === 'SendMail' && $context['delivery_key'] === $rows[0]->delivery_key
            && ! str_contains(json_encode($context), 'father@example.org'))->once();
        Log::shouldHaveReceived('info')->withArgs(fn (string $message, array $context = []) => $message === 'notification_delivery'
            && $context['status'] === 'failed' && $context['attempt'] === 2);
    }

    public function test_a_legacy_callback_mail_keeps_its_addresses_subject_and_attachments(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'att');
        file_put_contents($file, 'attachment body');
        $data = [
            'subject' => 'Reply to Your Query',
            'email' => 'visitor@example.org',
            'first_name' => 'Vee',
            'last_name' => 'Sitor',
            'query' => 'When is the open day?',
            'reply_message' => 'Saturday.',
            'school_name' => 'Demo School',
            'attachments' => [['path' => $file, 'name' => 'plan.txt', 'mime' => 'text/plain']],
        ];

        $sent = MailService::sendWithFallback('web_settings.testmail', $data, function ($message) use ($data, $file) {
            $message->to($data['email'])->subject($data['subject']);
            $message->from('school@example.org', 'Demo School');
            $message->attach($file, ['as' => 'plan.txt', 'mime' => 'text/plain']);
        });
        unlink($file);

        $this->assertTrue($sent);
        $email = $this->sentMessages()[0]->getOriginalMessage();
        $this->assertSame('Reply to Your Query', $email->getSubject());
        $this->assertSame('visitor@example.org', $email->getTo()[0]->getAddress());
        $this->assertSame('Demo School', $email->getFrom()[0]->getName());
        $this->assertStringContainsString('Saturday.', (string) $email->getHtmlBody());
        $this->assertSame('plan.txt', $email->getAttachments()[0]->getFilename());
        $this->assertSame('attachment body', $email->getAttachments()[0]->getBody());
        $this->assertSame('mail.web_settings.testmail', NotificationDelivery::sole()->type);
    }

    public function test_a_mail_that_cannot_be_built_or_addressed_is_not_queued(): void
    {
        Queue::fake();

        // teacher.email prints $body, which this call never passed.
        $this->assertFalse(MailService::sendWithFallback('teacher.email', ['email' => 'teacher@example.org', 'subject' => 'Hi'], function ($message) {
            $message->to('teacher@example.org')->subject('Hi');
        }));
        $data = $this->welcomeData();
        $data['email'] = 'not an address';
        $this->assertFalse(MailService::queue(TemplateMail::parentWelcome($data)));

        Queue::assertNothingPushed();
        $this->assertSame(['failed'], NotificationDelivery::pluck('status')->all());
    }

    public function test_the_mail_settings_test_is_sent_at_once_and_reports_failure(): void
    {
        Queue::fake();
        $callback = fn ($message) => $message->to('admin@example.org')->subject('Connection Verified successfully');

        $this->assertTrue(MailService::sendNow('mail', ['email' => 'admin@example.org'], $callback));
        $this->useFailingMailer();
        $this->assertFalse(MailService::sendNow('mail', ['email' => 'admin@example.org'], $callback));

        Queue::assertNothingPushed();
        $this->assertSame(['sent', 'failed'], NotificationDelivery::orderBy('id')->pluck('status')->all());
    }

    public function test_pushes_are_queued_per_device_with_the_same_messages(): void
    {
        Queue::fake();
        $android = $this->userWithDevice('android', 'token-android');
        $ios = $this->userWithDevice('ios', 'token-ios');
        $none = $this->userWithDevice('android', '');

        sendSimpleNotification([$android->id, $ios->id, $none->id], 'New message', 'Hello', 'chat', null, ['id' => 7]);

        $this->deliverFanout();

        $data = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'title' => 'New message',
            'body' => 'Hello',
            'type' => 'chat',
            'image' => '',
            'school_id' => '1', 'link' => '/chat', 'notice_type' => 'comms.message', 'channel' => 'comms',
            'sender_info' => json_encode(['id' => 7]),
            // Rich push (2.9): a direct message offers Reply and names its sender when it has one.
            'actions' => 'reply', 'category' => 'reply', 'sender_name' => '', 'sender_avatar' => '',
        ];
        $messages = Queue::pushed(SendPush::class)->map->message->sortBy(fn ($m) => $m['message']['token'])->values()->all();
        $this->assertEquals([
            ['message' => ['token' => 'token-android', 'data' => $data]],
            ['message' => ['token' => 'token-ios', 'notification' => ['title' => 'New message', 'body' => 'Hello'], 'data' => $data,
                'apns' => ['payload' => ['aps' => ['interruption-level' => 'active', 'thread-id' => 'comms', 'category' => 'reply']]]]],
        ], $messages);

        $rows = NotificationDelivery::orderBy('recipient_user_id')->get();
        $this->assertSame([$android->id, $ios->id], $rows->pluck('recipient_user_id')->all());
        $this->assertSame(['push.chat', 'push.chat'], $rows->pluck('type')->all());
        $this->assertSame(hash('sha256', 'token-android'), $rows[0]->recipient_hash);
        $this->assertStringNotContainsString('token-android', json_encode($rows->toArray()));
    }

    public function test_broadcast_content_uses_active_recipients_instead_of_unfilterable_topics(): void
    {
        Queue::fake();
        $parent = $this->userWithDevice('android', 'only-this-parent');
        sendNotificationToTopic('Notice', 'School closes early', 'custom', null, 'all');
        $this->deliverFanout();

        Queue::assertPushed(SendPush::class, 1);
        Queue::assertPushed(SendPush::class, fn ($job) => ($job->message['message']['token'] ?? null) === 'only-this-parent'
            && ! isset($job->message['message']['topic']));
        $this->assertSame([$parent->id], NotificationDelivery::where('type', 'push.custom')->pluck('recipient_user_id')->all());
    }

    public function test_another_school_broadcast_never_reaches_the_default_school_devices(): void
    {
        School::query()->forceCreate(['id' => 2, 'name' => 'Baobab High', 'slug' => 'baobab-high', 'timezone' => 'Africa/Johannesburg']);
        app(SchoolRoles::class)->seed(2);
        $this->userWithDevice('android', 'school-one');
        $parent = app(CurrentSchool::class)->run(2, function () {
            $year = SessionYear::forceCreate(['name' => 'School B year', 'default' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
            Settings::forceCreate(['type' => 'session_year', 'message' => $year->id]);

            return $this->userWithDevice('ios', 'school-two');
        });
        Queue::fake();
        app(CurrentSchool::class)->run(2, fn () => sendNotificationToTopic('Notice', 'Sports day', 'custom', null, 'parents'));
        $this->deliverFanout();

        Queue::assertPushed(SendPush::class, 1);
        Queue::assertPushed(SendPush::class, fn ($job) => $job->message['message']['token'] === 'school-two'
            && $job->message['message']['data']['school_id'] === '2');
        $this->assertSame([$parent->id], NotificationDelivery::where('type', 'push.custom')->pluck('recipient_user_id')->all());
        $this->assertSame([2], NotificationDelivery::where('type', 'push.custom')->pluck('school_id')->all());
    }

    public function test_a_push_is_sent_to_fcm_and_recorded_with_its_message_id(): void
    {
        $this->fakeFcmCredentials();
        Http::fake(['fcm.googleapis.com/*' => Http::response(['name' => 'projects/demo/messages/0:123'])]);
        $user = $this->userWithDevice('android', 'token-android');

        sendSimpleNotification([$user->id], 'Absent', 'Asha was absent today', 'attendance', null, null);

        Http::assertSent(fn ($request) => $request->url() === 'https://fcm.googleapis.com/v1/projects/demo-project/messages:send'
            && $request->hasHeader('Authorization', 'Bearer test-access-token')
            && $request['message']['token'] === 'token-android'
            && $request['message']['data']['type'] === 'attendance');
        $row = NotificationDelivery::where('channel', 'push')->sole();
        $this->assertSame('sent', $row->status);
        $this->assertSame('fcm', $row->provider);
        $this->assertSame('projects/demo/messages/0:123', $row->provider_message_id);
    }

    public function test_fcm_server_errors_are_retried_and_refusals_are_not(): void
    {
        $this->fakeFcmCredentials();
        Http::fake(['fcm.googleapis.com/*' => Http::sequence()
            ->push(['error' => ['status' => 'UNAVAILABLE']], 503)
            ->push(['error' => ['status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]]], 404)]);
        $message = ['message' => ['token' => 'token-android', 'data' => ['type' => 'custom']]];

        $job = new SendPush($message, DeliveryLog::queued(DeliveryLog::PUSH, 'push.custom', 1, 'token-android'));
        $job->withFakeQueueInteractions();
        try {
            $job->handle(app(FcmClient::class));
            $this->fail('A 503 from FCM should be retried');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('HTTP 503', $e->getMessage());
        }
        $job->job->attempts = 2;
        $job->handle(app(FcmClient::class));

        $rows = NotificationDelivery::orderBy('attempt')->get();
        $this->assertSame(['failed', 'failed'], $rows->pluck('status')->all());
        $this->assertStringContainsString('HTTP 404 UNREGISTERED', (string) $rows[1]->error_summary);
    }

    public function test_a_school_without_push_configured_records_the_failure_without_retrying(): void
    {
        Http::fake();
        $user = $this->userWithDevice('ios', 'token-ios');

        sendSimpleNotification([$user->id], 'Leave', 'Approved', 'leave', null, null);

        Http::assertNothingSent();
        $row = NotificationDelivery::sole();
        $this->assertSame('failed', $row->status);
        $this->assertStringContainsString('not configured', (string) $row->error_summary);
    }

    /** @return array<string, string> */
    private function welcomeData(): array
    {
        return [
            'subject' => 'Welcome to Demo School',
            'email' => 'father@example.org',
            'name' => ' Farid Parent',
            'username' => ' father@example.org',
            'password' => ' 01012000',
            'child_name' => ' Asha Learner',
            'child_grnumber' => ' 20261',
            'child_password' => ' 02022015',
            'type' => 'application_accept',
            'class_name' => 'Grade 5 - A',
            'school_name' => 'Demo School',
            'school_email' => 'school@example.org',
            'school_contact' => '012 345 6789',
            'android_app' => '',
            'ios_app' => '',
            'url' => 'http://localhost',
        ];
    }

    /** @return list<SentMessage> */
    private function sentMessages(): array
    {
        return app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->all();
    }

    private function useFailingMailer(): void
    {
        Mail::extend('failing', fn () => new class extends AbstractTransport
        {
            protected function doSend(SentMessage $message): void
            {
                throw new TransportException('550 father@example.org rejected');
            }

            public function __toString(): string
            {
                return 'failing://';
            }
        });
        config(['mail.mailers.failing' => ['transport' => 'failing'], 'mail.default' => 'failing']);
        app('mail.manager')->forgetMailers();
    }

    private function userWithDevice(string $deviceType, string $token): User
    {
        static $n = 0;
        $n++;

        $user = User::create([
            'first_name' => 'Device',
            'last_name' => 'User'.$n,
            'email' => "device{$n}@example.org",
            'password' => 'x',
            'mobile' => '0',
            'gender' => 'female',
            'image' => '',
            'fcm_id' => $token,
            'device_type' => $deviceType,
            'status' => 1,
        ]);
        $user->assignRole('Parent');

        return $user;
    }

    private function deliverFanout(): void
    {
        Outbox::dispatchPending();
        foreach (Queue::pushed(FanoutNotification::class) as $job) {
            app()->call([$job, 'handle']);
        }
    }

    private function fakeFcmCredentials(): void
    {
        Settings::create(['type' => 'project_id', 'message' => 'demo-project']);
        $this->app->bind(FcmClient::class, fn () => new class extends FcmClient
        {
            public function accessToken(): ?string
            {
                return 'test-access-token';
            }
        });
    }
}
