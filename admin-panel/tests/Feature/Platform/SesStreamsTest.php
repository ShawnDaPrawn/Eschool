<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\SendMail;
use App\Mail\Platform\NotificationMail;
use App\Mail\Platform\TemplateMail;
use App\Models\Platform\MailStream;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Services\MailService;
use App\Services\Platform\Mail\MailStreams;
use App\Services\Platform\Mail\SesConfigurationSets;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Aws\CommandInterface;
use Aws\Result;
use Aws\SesV2\Exception\SesV2Exception;
use Aws\SesV2\SesV2Client;
use GuzzleHttp\Promise\Create;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mime\Email;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * SES streams (Action Plan 2.3): each school's account and bulk configuration
 * sets, the stream and set SendMail picks from the delivery's own school, the
 * scheduled set-up and check, and the /saas mail streams page. SES is an
 * SesV2Client with a fake handler: no test calls AWS.
 * engineering/specs/platform/ses-streams.md.
 */
class SesStreamsTest extends TestCase
{
    use UsesTwoSchools;

    private const TOPIC = 'arn:aws:sns:af-south-1:123456789012:navigateed-ses-feedback';

    /** @var array<string, array{sending: bool, vdm: array<string, mixed>, tags: list<array{Key: string, Value: string}>, destinations: array<string, array<string, mixed>>}> configuration sets the fake SES holds */
    private array $sets = [];

    /** @var list<array{0: string, 1: array<string, mixed>}> */
    private array $commands = [];

    private bool $sesDown = false;

    private bool $clientBuilt = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        config([
            'mail.streams.enabled' => true,
            'mail.streams.prefix' => 'ne',
            'mail.streams.event_topic_arn' => null,
            'services.ses_notifications.topic_arns' => [self::TOPIC],
            'services.ses.options' => [],
            'mail.default' => 'array',
            'mail.from.address' => 'noreply@navigateed.co.za',
            'mail.from.name' => 'NavigateEd',
        ]);
        $this->app->instance(SesConfigurationSets::class, new SesConfigurationSets(function (): SesV2Client {
            $this->clientBuilt = true;

            return new SesV2Client([
                'version' => '2019-09-27', 'region' => 'af-south-1', 'credentials' => ['key' => 'test', 'secret' => 'test'], 'retries' => 0,
                'handler' => fn (CommandInterface $command) => $this->answer($command),
            ]);
        }));
        $this->app->forgetInstance(MailStreams::class);
    }

    private function answer(CommandInterface $command): mixed
    {
        $name = $command->getName();
        $args = $command->toArray();
        $this->commands[] = [$name, $args];
        $set = (string) ($args['ConfigurationSetName'] ?? '');
        $fail = fn (string $code) => Create::rejectionFor(new SesV2Exception($code, $command, ['code' => $code]));
        if ($this->sesDown) {
            return $fail('ServiceUnavailable');
        }
        $ok = fn (array $result = []) => Create::promiseFor(new Result($result));
        $destination = (string) ($args['EventDestinationName'] ?? '');

        return match ($name) {
            'CreateConfigurationSet' => isset($this->sets[$set]) ? $fail('AlreadyExistsException') : (function () use ($set, $args, $ok) {
                $this->sets[$set] = ['sending' => (bool) $args['SendingOptions']['SendingEnabled'], 'vdm' => $args['VdmOptions'], 'tags' => $args['Tags'], 'destinations' => []];

                return $ok();
            })(),
            'PutConfigurationSetReputationOptions' => isset($this->sets[$set]) ? $ok() : $fail('NotFoundException'),
            'PutConfigurationSetVdmOptions' => ! isset($this->sets[$set]) ? $fail('NotFoundException') : (function () use ($set, $args, $ok) {
                $this->sets[$set]['vdm'] = $args['VdmOptions'];

                return $ok();
            })(),
            'CreateConfigurationSetEventDestination' => ! isset($this->sets[$set]) ? $fail('NotFoundException') : (isset($this->sets[$set]['destinations'][$destination]) ? $fail('AlreadyExistsException') : (function () use ($set, $destination, $args, $ok) {
                $this->sets[$set]['destinations'][$destination] = $args['EventDestination'];

                return $ok();
            })()),
            'UpdateConfigurationSetEventDestination' => ! isset($this->sets[$set]['destinations'][$destination]) ? $fail('NotFoundException') : (function () use ($set, $destination, $args, $ok) {
                $this->sets[$set]['destinations'][$destination] = $args['EventDestination'];

                return $ok();
            })(),
            'GetConfigurationSet' => ! isset($this->sets[$set]) ? $fail('NotFoundException') : $ok([
                'ConfigurationSetName' => $set,
                'ReputationOptions' => ['ReputationMetricsEnabled' => true],
                'SendingOptions' => ['SendingEnabled' => $this->sets[$set]['sending']],
                'VdmOptions' => $this->sets[$set]['vdm'],
            ]),
            default => $fail('UnexpectedCommand'),
        };
    }

    /** @return list<string> the configuration sets named in commands of this name */
    private function sent(string $command): array
    {
        return array_values(array_map(fn (array $c) => (string) ($c[1]['ConfigurationSetName'] ?? ''), array_filter($this->commands, fn (array $c) => $c[0] === $command)));
    }

    /** @return array<string, string> configuration_set => status */
    private function streamRows(): array
    {
        return MailStream::query()->orderBy('configuration_set')->pluck('status', 'configuration_set')->all();
    }

    private function staff(string $role): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $role.'@platform.example', 'mobile' => '', 'password' => 'Synthetic-streams-password-582!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHour()]);

        return $user;
    }

    /** @return list<Email> */
    private function messages(): array
    {
        return array_values(array_map(fn ($sent) => $sent->getOriginalMessage(), app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->all()));
    }

    private function lastMessage(int $before): Email
    {
        $messages = $this->messages();
        $this->assertCount($before + 1, $messages, 'One mail was sent');

        return end($messages);
    }

    private function configurationSetOf(Email $message): ?string
    {
        return $message->getHeaders()->get(SendMail::CONFIGURATION_SET_HEADER)?->getBodyAsString();
    }

    /** An urgent notice (account stream) to Farid at School A, through the Notifier. */
    private function urgentToFarid(): Email
    {
        $before = count($this->messages());
        app(CurrentSchool::class)->run(1, fn () => app(Notifier::class)->send(User::query()->findOrFail($this->schoolA->ids['farid_user']), 'comms.urgent', 'Gate closed', 'The north gate is closed today.'));

        return $this->lastMessage($before);
    }

    /** A school notice (bulk stream) to a member of the school, sent by SendMail for a delivery row of that school. */
    private function noticeIn(int $schoolId, User $recipient, ?int $ambientSchool = null): Email
    {
        $before = count($this->messages());
        // School notices by email are the family's choice; this one has chosen them.
        app(CurrentSchool::class)->run($schoolId, fn () => app(NotificationRules::class)->choose($recipient, 'comms.notice', 'email', true));
        $key = app(CurrentSchool::class)->run($schoolId, fn () => DeliveryLog::queued(DeliveryLog::MAIL, 'mail.comms.notice', (int) $recipient->id, (string) $recipient->email));
        $job = new SendMail(new NotificationMail((string) $recipient->email, 'comms.notice', 'Sports day', 'Sports day is on Friday.', 'https://app.navigateed.co.za/notifications', 'School'), $key);
        $ambientSchool === null ? $job->handle() : app(CurrentSchool::class)->run($ambientSchool, fn () => $job->handle());

        return $this->lastMessage($before);
    }

    private function welcomeMail(?int $schoolId): Email
    {
        $before = count($this->messages());
        $send = fn () => MailService::queue(TemplateMail::parentWelcome([
            'subject' => 'Welcome', 'email' => 'father@example.org', 'name' => 'Farid', 'username' => 'father@example.org', 'password' => 'Synthetic-1',
            'child_name' => 'Asha', 'child_grnumber' => '20261', 'child_password' => 'Synthetic-2', 'type' => 'application_accept', 'class_name' => 'Grade 5 - A',
            'school_name' => 'Jacaranda', 'school_email' => 'school@example.org', 'school_contact' => '012 345 6789', 'android_app' => '', 'ios_app' => '', 'url' => 'http://localhost',
        ]));
        if ($schoolId === null) {
            app(CurrentSchool::class)->forget();
            $send();
        } else {
            app(CurrentSchool::class)->run($schoolId, $send);
        }

        return $this->lastMessage($before);
    }

    private function schoolBMember(): User
    {
        return User::query()->findOrFail($this->schoolB->ids['farid_user']);
    }

    public function test_the_poll_sets_up_an_account_and_a_bulk_set_for_the_platform_and_each_school(): void
    {
        $this->artisan('mail:ensure-streams')->assertSuccessful();

        $this->assertSame([
            'ne-platform-account' => 'ready', 'ne-platform-bulk' => 'ready', 'ne-s1-account' => 'ready', 'ne-s1-bulk' => 'ready', 'ne-s2-account' => 'ready', 'ne-s2-bulk' => 'ready',
        ], $this->streamRows());
        $this->assertSame([[1, 'account'], [1, 'bulk']], MailStream::query()->where('school_id', 1)->orderBy('stream')->get()->map(fn ($r) => [$r->school_id, $r->stream])->all());
        $create = collect($this->commands)->first(fn (array $c) => $c[0] === 'CreateConfigurationSet' && $c[1]['ConfigurationSetName'] === 'ne-s1-account')[1];
        $this->assertTrue($create['ReputationOptions']['ReputationMetricsEnabled']);
        $this->assertTrue($create['SendingOptions']['SendingEnabled']);
        $this->assertSame(['DashboardOptions' => ['EngagementMetrics' => 'DISABLED'], 'GuardianOptions' => ['OptimizedSharedDelivery' => 'ENABLED']], $create['VdmOptions']);
        $this->assertSame([['Key' => 'navigateed:school', 'Value' => '1'], ['Key' => 'navigateed:stream', 'Value' => 'account']], $create['Tags']);
        $this->assertSame('ENABLED', $this->sets['ne-s1-bulk']['vdm']['DashboardOptions']['EngagementMetrics']);
        $this->assertSame('platform', $this->sets['ne-platform-bulk']['tags'][0]['Value']);
        foreach (array_keys($this->sets) as $set) {
            $this->assertSame(['Enabled' => true, 'MatchingEventTypes' => ['DELIVERY', 'BOUNCE', 'COMPLAINT', 'REJECT'], 'SnsDestination' => ['TopicArn' => self::TOPIC]], $this->sets[$set]['destinations']['navigateed-feedback']);
        }
        $row = MailStream::query()->where('configuration_set', 'ne-s1-bulk')->sole();
        $this->assertSame([true, true, true, true], [$row->sending_enabled, $row->engagement_tracking, $row->optimized_delivery, $row->event_destination]);
        $this->assertSame(2, DB::table('audit_log')->where('action', 'mail_stream.ready')->where('school_id', 1)->count());
        $this->assertSame(2, DB::table('audit_log')->where('action', 'mail_stream.ready')->where('school_id', 2)->count());

        // Everything ready and checked today: the next hourly run asks SES nothing.
        $this->commands = [];
        $this->artisan('mail:ensure-streams')->assertSuccessful();
        $this->assertSame([], $this->commands);

        // A day later each set is checked; one paused in SES is shown paused and audited.
        $this->sets['ne-s1-bulk']['sending'] = false;
        $this->travel(25)->hours();
        $this->artisan('mail:ensure-streams')->assertSuccessful();
        $this->assertCount(6, $this->sent('GetConfigurationSet'));
        $this->assertFalse(MailStream::query()->where('configuration_set', 'ne-s1-bulk')->sole()->sending_enabled);
        $this->assertSame(1, DB::table('audit_log')->where('action', 'mail_stream.paused')->where('school_id', 1)->count());

        // Ensuring again is idempotent: no new rows, the existing sets are updated, and the paused one stays paused.
        $this->commands = [];
        $this->artisan('mail:ensure-streams', ['--school' => 1])->assertSuccessful();
        $this->assertCount(6, MailStream::query()->get());
        $this->assertSame(['ne-s1-account', 'ne-s1-bulk'], $this->sent('PutConfigurationSetVdmOptions'));
        $this->assertSame(['ne-s1-account', 'ne-s1-bulk'], $this->sent('UpdateConfigurationSetEventDestination'));
        $this->assertSame([], $this->sent('PutConfigurationSetSendingOptions'));
        $this->assertFalse($this->sets['ne-s1-bulk']['sending']);
        $this->assertSame(2, DB::table('audit_log')->where('action', 'mail_stream.ready')->where('school_id', 1)->count());
    }

    public function test_mail_goes_through_the_stream_of_its_own_school(): void
    {
        $this->artisan('mail:ensure-streams')->assertSuccessful();

        // Account stream: an urgent notice, a welcome mail, and mail for no school.
        $this->assertSame('ne-s1-account', $this->configurationSetOf($this->urgentToFarid()));
        $row = NotificationDelivery::query()->where('type', 'mail.comms.urgent')->latest('id')->firstOrFail();
        $this->assertSame([1, 'account', 'ne-s1-account', 'sent'], [$row->school_id, $row->mail_stream, $row->configuration_set, $row->status]);
        $this->assertSame('ne-s1-account', $this->configurationSetOf($this->welcomeMail(1)));
        $this->assertSame('ne-platform-account', $this->configurationSetOf($this->welcomeMail(null)));
        $this->assertSame(['account', 'ne-platform-account'], [NotificationDelivery::query()->latest('id')->firstOrFail()->mail_stream, NotificationDelivery::query()->latest('id')->firstOrFail()->configuration_set]);

        // Bulk stream: a school notice, by the delivery row's school even when another school is current.
        $farid = User::query()->findOrFail($this->schoolA->ids['farid_user']);
        $this->assertSame('ne-s1-bulk', $this->configurationSetOf($this->noticeIn(1, $farid)));
        $this->assertSame('ne-s2-bulk', $this->configurationSetOf($this->noticeIn(2, $this->schoolBMember(), ambientSchool: 1)));
        $this->assertSame([2, 'bulk', 'ne-s2-bulk'], [NotificationDelivery::query()->latest('id')->firstOrFail()->school_id, NotificationDelivery::query()->latest('id')->firstOrFail()->mail_stream, NotificationDelivery::query()->latest('id')->firstOrFail()->configuration_set]);

        // School B's sets not ready: its mail falls back to the platform's set of the same stream, never School A's.
        MailStream::query()->where('school_id', 2)->update(['status' => MailStream::FAILED]);
        $this->assertSame('ne-platform-bulk', $this->configurationSetOf($this->noticeIn(2, $this->schoolBMember())));

        // Switched off: no configuration set, and the stream is still recorded.
        config(['mail.streams.enabled' => false]);
        $this->assertNull($this->configurationSetOf($this->noticeIn(1, $farid)));
        $row = NotificationDelivery::query()->latest('id')->firstOrFail();
        $this->assertSame(['bulk', null], [$row->mail_stream, $row->configuration_set]);
    }

    public function test_streams_follow_the_notification_type_and_names_are_deterministic(): void
    {
        foreach (['account.password_reset', 'account.invitation', 'account.sign_in_link', 'approval.requested', 'safeguarding.alert', 'comms.urgent', 'attendance.absence'] as $type) {
            $this->assertSame('account', MailStreams::streamForType($type), $type);
        }
        foreach (['comms.notice', 'communication.message', 'digest.weekly', 'fees.invoice', 'academics.marks_released', 'school.new_year'] as $type) {
            $this->assertSame('bulk', MailStreams::streamForType($type), $type);
        }
        $this->assertSame('account', MailStreams::streamFor(TemplateMail::parentWelcome(['email' => 'father@example.org', 'subject' => 'Welcome'])));
        $this->assertSame(['ne-s12-account', 'ne-s12-bulk', 'ne-platform-account'], [MailStreams::name(12, 'account'), MailStreams::name(12, 'bulk'), MailStreams::name(null, 'account')]);
        config(['mail.streams.prefix' => ' NE_Staging! ']);
        $this->assertSame('nestaging-s3-bulk', MailStreams::name(3, 'bulk'));
        config(['mail.streams.prefix' => '---']);
        $this->assertSame('ne-s3-bulk', MailStreams::name(3, 'bulk'));
    }

    public function test_hq_sees_each_schools_rates_and_checks_one_school_without_touching_another(): void
    {
        // School A: 60 bulk mails, 4 bounced (6.7%: SES's review level); School B: 10 account mails, 1 complaint (too few to judge).
        $rows = [];
        foreach (range(1, 60) as $i) {
            $rows[] = ['school_id' => 1, 'mail_stream' => 'bulk', 'status' => $i <= 4 ? 'bounced' : 'delivered'];
        }
        foreach (range(1, 10) as $i) {
            $rows[] = ['school_id' => 2, 'mail_stream' => 'account', 'status' => $i === 1 ? 'complained' : 'sent'];
        }
        $rows[] = ['school_id' => 1, 'mail_stream' => 'bulk', 'status' => 'failed'];
        DB::table('notification_deliveries')->insert(array_map(fn (array $r) => $r + [
            'delivery_key' => (string) Str::uuid(), 'type' => 'mail.comms.notice', 'channel' => 'mail', 'attempt' => 1, 'created_at' => now(), 'updated_at' => now(),
        ], $rows));

        $this->actingAs($this->staff('operations'));
        $page = $this->get('/saas/mail-streams')->assertOk()->assertSee('Mail streams')->assertSee('ne-s1-bulk')->assertSee('ne-s2-account')->assertSee('Baobab High');
        $targets = collect($page->viewData('targets'))->keyBy(fn (array $t) => $t['school_id'] ?? 'platform');
        $aBulk = collect($targets[1]['streams'])->firstWhere('stream', 'bulk');
        $this->assertSame([60, 4, 0, 'review', 'not_set_up'], [$aBulk['sent'], $aBulk['bounced'], $aBulk['complained'], $aBulk['flag'], $aBulk['status']]);
        $bAccount = collect($targets[2]['streams'])->firstWhere('stream', 'account');
        $this->assertSame([10, 1, null], [$bAccount['sent'], $bAccount['complained'], $bAccount['flag']]);
        $this->assertSame(0, collect($targets[1]['streams'])->firstWhere('stream', 'account')['sent']);
        $this->assertSame(0, collect($targets[2]['streams'])->firstWhere('stream', 'bulk')['sent']);

        // Set up School A only.
        $this->post('/saas/mail-streams/check', ['school_id' => 1])->assertRedirect('/saas/mail-streams')->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Both streams are set up in SES and checked.');
        $this->assertSame(['ne-s1-account' => 'ready', 'ne-s1-bulk' => 'ready'], $this->streamRows());
        $this->assertSame(['ne-s1-account', 'ne-s1-bulk'], $this->sent('CreateConfigurationSet'));
        $this->assertSame(1, DB::table('audit_log')->where('action', 'platform_mail_streams_checked')->where('school_id', 1)->count());

        // The platform's pair has no school id.
        $this->post('/saas/mail-streams/check')->assertSessionHasNoErrors();
        $this->assertSame(['ne-platform-account' => 'ready', 'ne-platform-bulk' => 'ready', 'ne-s1-account' => 'ready', 'ne-s1-bulk' => 'ready'], $this->streamRows());

        // A school that does not exist: refused, nothing written, no SES call.
        $this->commands = [];
        $this->post('/saas/mail-streams/check', ['school_id' => 999])->assertSessionHasErrors('school_id');
        $this->post('/saas/mail-streams/check', ['school_id' => 'ne-s2'])->assertSessionHasErrors('school_id');
        $this->assertSame([], $this->commands);
        $this->assertCount(4, $this->streamRows());
        $this->assertSame(0, MailStream::query()->where('school_id', 2)->count());
    }

    public function test_roles_without_the_ability_and_school_staff_cannot_see_or_check_streams(): void
    {
        // Support sees the page but cannot set anything up.
        $this->actingAs($this->staff('support'));
        $this->get('/saas/mail-streams')->assertOk()->assertDontSee('Set up or check now');
        $this->post('/saas/mail-streams/check', ['school_id' => 1])->assertForbidden();

        $this->actingAs($this->staff('sales'));
        $this->get('/saas/mail-streams')->assertForbidden();
        $this->post('/saas/mail-streams/check', ['school_id' => 1])->assertForbidden();

        // A school's Super Admin is not platform staff.
        $this->actingAs($this->superAdmin());
        $this->assertNotSame(200, $this->get('/saas/mail-streams')->getStatusCode());
        $response = $this->post('/saas/mail-streams/check', ['school_id' => 1]);
        $this->assertNotSame(url('/saas/mail-streams'), $response->headers->get('Location'));
        $response->assertSessionMissing('success');

        // The service checks the ability itself too.
        try {
            app(MailStreams::class)->ensureForHq(1, $this->staff('content'));
            $this->fail('A role without mail.manage was allowed.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame(0, MailStream::query()->count());
        $this->assertSame([], $this->commands);
        $this->assertSame(0, DB::table('audit_log')->where('action', 'platform_mail_streams_checked')->count());
    }

    public function test_an_unreachable_ses_a_missing_set_or_no_feedback_topic_leaves_mail_on_a_working_set(): void
    {
        // No feedback topic: nothing is created in SES and no set is used.
        config(['services.ses_notifications.topic_arns' => []]);
        $this->artisan('mail:ensure-streams')->assertFailed();
        config(['services.ses_notifications.topic_arns' => [self::TOPIC], 'mail.streams.event_topic_arn' => 'arn:aws:sns:af-south-1:999999999999:someone-else']);
        $this->assertSame(['failed', 'failed'], array_values(array_map(fn (MailStream $r) => $r->status, app(MailStreams::class)->ensure(1))));
        $this->assertSame('no_event_topic', MailStream::query()->where('configuration_set', 'ne-s1-bulk')->sole()->last_error);
        $this->assertSame([], $this->commands);
        $farid = User::query()->findOrFail($this->schoolA->ids['farid_user']);
        $this->assertNull($this->configurationSetOf($this->noticeIn(1, $farid)));

        // SES down: still failed, and mail goes without a set.
        config(['mail.streams.event_topic_arn' => null]);
        $this->sesDown = true;
        app(MailStreams::class)->ensure(1);
        $this->assertSame('ses_unreachable', MailStream::query()->where('configuration_set', 'ne-s1-bulk')->sole()->last_error);
        $this->assertNull($this->configurationSetOf($this->noticeIn(1, $farid)));

        // SES back: ready. A ready set stays in use while SES cannot be reached for a check.
        $this->sesDown = false;
        app(MailStreams::class)->ensure(null);
        app(MailStreams::class)->ensure(1);
        $this->sesDown = true;
        app(MailStreams::class)->refresh(MailStream::query()->where('configuration_set', 'ne-s1-bulk')->sole());
        $row = MailStream::query()->where('configuration_set', 'ne-s1-bulk')->sole();
        $this->assertSame(['ready', 'ses_unreachable'], [$row->status, $row->last_error]);
        $this->sesDown = false;
        $this->assertSame('ne-s1-bulk', $this->configurationSetOf($this->noticeIn(1, $farid)));

        // A set deleted in SES is no longer named: mail falls back to the platform set.
        unset($this->sets['ne-s1-bulk']);
        app(MailStreams::class)->refresh($row);
        $this->assertSame(['failed', 'missing_in_ses'], [$row->fresh()->status, $row->fresh()->last_error]);
        $this->assertSame(1, DB::table('audit_log')->where('action', 'mail_stream.missing')->where('school_id', 1)->count());
        $this->assertSame('ne-platform-bulk', $this->configurationSetOf($this->noticeIn(1, $farid)));
    }

    public function test_a_check_that_reaches_ses_again_clears_an_earlier_unreachable_error(): void
    {
        $this->artisan('mail:ensure-streams', ['--school' => 1])->assertSuccessful();
        $operator = $this->staff('operations');
        // The grant outlasts the two days this test travels.
        PlatformAdminGrant::query()->where('user_id', $operator->id)->update(['expires_at' => now()->addDays(3)]);
        $this->actingAs($operator);

        // The daily check fails once: the set stays in use and HQ sees why.
        $this->sesDown = true;
        $this->travel(25)->hours();
        $this->artisan('mail:ensure-streams')->assertSuccessful();
        $row = MailStream::query()->where('configuration_set', 'ne-s1-bulk')->sole();
        $this->assertSame(['ready', 'ses_unreachable'], [$row->status, $row->last_error]);
        $this->get('/saas/mail-streams')->assertOk()->assertSee('SES could not be reached at the last check');

        // The next day's check reaches SES: the error is gone from the row and the page.
        $this->sesDown = false;
        $this->travel(25)->hours();
        $this->artisan('mail:ensure-streams')->assertSuccessful();
        foreach (MailStream::query()->where('school_id', 1)->get() as $stream) {
            $this->assertSame(['ready', null], [$stream->status, $stream->last_error], $stream->configuration_set);
        }
        $this->get('/saas/mail-streams')->assertOk()->assertDontSee('SES could not be reached at the last check');
        $this->assertSame('ne-s1-bulk', $this->configurationSetOf($this->noticeIn(1, User::query()->findOrFail($this->schoolA->ids['farid_user']))));
    }

    public function test_switched_off_no_ses_client_is_built_and_the_page_only_explains(): void
    {
        config(['mail.streams.enabled' => false]);

        $this->artisan('mail:ensure-streams')->expectsOutput('SES streams are switched off.')->assertSuccessful();
        $this->assertSame([], app(MailStreams::class)->ensure(1));
        $this->assertNull($this->configurationSetOf($this->urgentToFarid()));
        $this->assertSame('account', NotificationDelivery::query()->where('type', 'mail.comms.urgent')->latest('id')->firstOrFail()->mail_stream);

        $this->actingAs($this->staff('operations'));
        $this->get('/saas/mail-streams')->assertOk()->assertSee('SES streams are not switched on')->assertDontSee('Set up or check now');
        $this->post('/saas/mail-streams/check', ['school_id' => 1])->assertSessionHasErrors(['streams' => 'SES streams are not switched on (MAIL_SES_STREAMS).']);

        $this->assertFalse($this->clientBuilt);
        $this->assertSame(0, MailStream::query()->count());
    }
}
