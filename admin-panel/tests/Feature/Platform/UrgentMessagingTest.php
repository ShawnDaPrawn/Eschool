<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\SendText;
use App\Models\Notification;
use App\Models\Platform\AuditLog;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMessagingSetting;
use App\Models\User;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\TwilioClient;
use App\Services\Platform\Notifications\UrgentMessaging;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * SMS and WhatsApp for urgent and safety notices (Action Plan 2.7): one
 * NavigateEd Twilio account, opt-in by the school, notice-only text for
 * anything that may be about one child, a signed status callback and
 * pass-through pricing. Twilio is never called: every request is faked.
 * engineering/specs/platform/sms-whatsapp.md.
 */
class UrgentMessagingTest extends TestCase
{
    use UsesTwoSchools;

    private const SID = 'ACaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private const TOKEN = 'aaaabbbbccccddddtoken';

    private const NUMBER = '+27821234567';

    private const MESSAGE_SID = 'SM0123456789abcdef0123456789abcdef';

    private const CALLBACK = 'https://panel.example.test/webhook/messaging/twilio';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        config([
            'services.twilio.account_sid' => self::SID,
            'services.twilio.auth_token' => self::TOKEN,
            'services.twilio.messaging_service_sid' => 'MGbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
            'services.twilio.sms_from' => null,
            'services.twilio.whatsapp_from' => 'whatsapp:+27870000000',
            'services.twilio.status_callback_url' => self::CALLBACK,
        ]);
        $types = config('urgent_messaging.types');
        $types['comms.urgent']['whatsapp_notice_template'] = 'HXnotice00000000000000000000000000';
        $types['comms.urgent']['whatsapp_text_template'] = 'HXurgent00000000000000000000000000';
        $types['attendance.absence']['whatsapp_notice_template'] = 'HXnotice00000000000000000000000000';
        config(['urgent_messaging.types' => $types]);
        Http::preventStrayRequests();
        // The school office's record of each School A guardian's number.
        DB::table('parents')->where('school_id', 1)->where('user_id', $this->schoolA->ids['farid_user'])->update(['mobile' => '082 123 4567']);
        DB::table('parents')->where('school_id', 2)->update(['mobile' => '083 999 0000']);
    }

    /** Twilio accepts every message. Call it before anything is sent: the first fake that matches wins. */
    private function twilioAccepts(int $status = 201, array $body = []): void
    {
        Http::fake(['api.twilio.com/*' => Http::response($body + [
            'sid' => self::MESSAGE_SID, 'status' => 'accepted', 'num_segments' => '1', 'price' => null, 'price_unit' => 'USD',
        ], $status)]);
    }

    private function guardian(): User
    {
        return User::findOrFail($this->schoolA->ids['farid_user']);
    }

    /**
     * School A opts in through the service as its office; School B's row is a
     * fixture written directly (no School B office account exists).
     *
     * @param  list<string>  $sms
     * @param  list<string>  $whatsapp
     */
    private function optIn(int $school, array $sms = ['comms.urgent', 'attendance.absence'], array $whatsapp = []): void
    {
        if ($school !== 1) {
            app(CurrentSchool::class)->run($school, fn () => SchoolMessagingSetting::query()->updateOrCreate([], [
                'sms_enabled' => $sms !== [], 'sms_types' => $sms, 'whatsapp_enabled' => $whatsapp !== [], 'whatsapp_types' => $whatsapp,
                'whatsapp_consent_confirmed_at' => $whatsapp === [] ? null : now(), 'revision' => 1,
            ]));

            return;
        }
        app(UrgentMessaging::class)->change($this->superAdmin(), [
            'revision' => (int) (SchoolMessagingSetting::query()->value('revision') ?? 0),
            'sms_enabled' => $sms !== [], 'sms_types' => $sms,
            'whatsapp_enabled' => $whatsapp !== [], 'whatsapp_types' => $whatsapp, 'whatsapp_consent' => $whatsapp !== [],
        ]);
    }

    private function notice(string $type, string $title, string $body, bool $wholeSchool = false): Notification
    {
        $notice = app(Notifier::class)->makeNotice($type, $title, $body, '/notifications');
        if ($wholeSchool) {
            $notice->forceFill(['send_to' => 1])->save();
        }

        return $notice;
    }

    /** @return list<array<string, mixed>> */
    private function twilioRequests(): array
    {
        return Http::recorded(fn (HttpRequest $request) => str_contains($request->url(), 'api.twilio.com'))
            ->map(fn (array $pair) => $pair[0]->data())->values()->all();
    }

    private function texts(): int
    {
        return NotificationDelivery::query()->whereIn('channel', ['sms', 'whatsapp'])->count();
    }

    private function signed(array $params, ?string $signature = null): TestResponse
    {
        $signature ??= TwilioClient::signature(self::TOKEN, self::CALLBACK, $params);

        return $this->call('POST', '/webhook/messaging/twilio', [], [], [], [
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded', 'HTTP_X_TWILIO_SIGNATURE' => $signature,
        ], http_build_query($params));
    }

    private function sentText(int $school = 1, string $sid = self::MESSAGE_SID, array $changes = []): NotificationDelivery
    {
        return NotificationDelivery::query()->create($changes + [
            'school_id' => $school, 'delivery_key' => (string) Str::uuid(), 'type' => 'sms.comms.urgent', 'channel' => 'sms',
            'recipient_user_id' => $this->schoolA->ids['farid_user'], 'recipient_hash' => DeliveryLog::hash(self::NUMBER),
            'status' => 'sent', 'provider' => 'twilio', 'provider_message_id' => $sid, 'attempt' => 1, 'queued_at' => now(), 'sent_at' => now(),
        ]);
    }

    private function staff(string $role): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $role.'@platform.example', 'mobile' => '', 'password' => 'Synthetic-messaging-password-582!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHour()]);

        return $user;
    }

    public function test_an_urgent_notice_is_texted_by_sms_and_whatsapp_through_twilio_for_an_opted_in_school(): void
    {
        $this->twilioAccepts();
        $this->optIn(1, ['comms.urgent'], ['comms.urgent']);
        $notice = $this->notice('comms.urgent', 'Storm warning', 'School closes at 11:00 today.', true);

        $sent = app(Notifier::class)->send($this->guardian(), 'comms.urgent', 'Storm warning', 'School closes at 11:00 today.', '/notifications', $notice);

        $this->assertContains('sms', $sent);
        $this->assertContains('whatsapp', $sent);
        $requests = $this->twilioRequests();
        $this->assertCount(2, $requests);
        $school = School::query()->findOrFail(1)->name;
        [$sms, $whatsapp] = $requests[0]['To'] === self::NUMBER ? $requests : array_reverse($requests);
        $this->assertSame(self::NUMBER, $sms['To']);
        $this->assertSame('MGbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', $sms['MessagingServiceSid']);
        $this->assertSame(self::CALLBACK, $sms['StatusCallback']);
        $this->assertStringContainsString('Storm warning', $sms['Body']);
        $this->assertStringContainsString($school, $sms['Body']);
        $this->assertSame('whatsapp:'.self::NUMBER, $whatsapp['To']);
        $this->assertSame('whatsapp:+27870000000', $whatsapp['From']);
        $this->assertSame('HXurgent00000000000000000000000000', $whatsapp['ContentSid']);
        $this->assertSame(['1' => $school, '2' => 'Storm warning. School closes at 11:00 today.'], json_decode($whatsapp['ContentVariables'], true));
        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/'.self::SID.'/Messages.json'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode(self::SID.':'.self::TOKEN)));

        $rows = NotificationDelivery::query()->whereIn('channel', ['sms', 'whatsapp'])->orderBy('channel')->get();
        $this->assertSame(['sms.comms.urgent', 'whatsapp.comms.urgent'], $rows->pluck('type')->all());
        foreach ($rows as $row) {
            $this->assertSame(['sent', 'twilio', self::MESSAGE_SID, 1, 1], [$row->status, $row->provider, $row->provider_message_id, $row->segments, $row->school_id]);
            $this->assertSame(DeliveryLog::hash(self::NUMBER), $row->recipient_hash);
            $this->assertStringNotContainsString('821234567', json_encode($row->getAttributes()));
        }
        $this->assertSame(2, DB::table('notification_dispatches')->where('notification_id', $notice->id)->whereIn('channel', ['sms', 'whatsapp'])->count());
    }

    public function test_an_absence_alert_or_a_notice_to_one_family_never_carries_its_text(): void
    {
        $this->twilioAccepts();
        $this->optIn(1, ['comms.urgent', 'attendance.absence'], ['attendance.absence']);
        $notifier = app(Notifier::class);

        $notifier->send($this->guardian(), 'attendance.absence', 'Absence alert', 'Asha was marked absent on 2026-11-03.', '/notifications', $this->notice('attendance.absence', 'Absence alert', 'Asha was marked absent on 2026-11-03.'));
        $notifier->send($this->guardian(), 'comms.urgent', 'Asha hurt her arm', 'Please collect Asha from the office.', '/notifications', $this->notice('comms.urgent', 'Asha hurt her arm', 'Please collect Asha from the office.'));

        $requests = $this->twilioRequests();
        $this->assertCount(3, $requests);
        $school = School::query()->findOrFail(1)->name;
        foreach ($requests as $request) {
            $this->assertStringNotContainsString('Asha', json_encode($request));
            if (isset($request['Body'])) {
                $this->assertSame("You have an urgent message from {$school}. Open the NavigateEd app.", $request['Body']);
            } else {
                $this->assertSame('HXnotice00000000000000000000000000', $request['ContentSid']);
                $this->assertSame(['1' => $school], json_decode($request['ContentVariables'], true));
            }
        }
    }

    public function test_a_school_that_has_not_opted_in_gets_nothing_sent(): void
    {
        $this->twilioAccepts();
        $notifier = app(Notifier::class);
        $sent = $notifier->send($this->guardian(), 'comms.urgent', 'Storm', 'Closed', '/notifications', $this->notice('comms.urgent', 'Storm', 'Closed', true));
        $this->assertNotContains('sms', $sent);

        // Switched on, but no notice chosen; or WhatsApp chosen without the consent confirmation.
        $this->optIn(1, []);
        app(CurrentSchool::class)->run(1, fn () => SchoolMessagingSetting::query()->update(['sms_enabled' => true, 'whatsapp_enabled' => true, 'whatsapp_types' => json_encode(['comms.urgent'])]));
        $notifier->send($this->guardian(), 'comms.urgent', 'Storm', 'Closed', '/notifications', $this->notice('comms.urgent', 'Storm', 'Closed', true));

        $this->assertSame([], $this->twilioRequests());
        $this->assertSame(0, $this->texts());
        $this->assertFalse(app(UrgentMessaging::class)->enabledFor('comms.urgent', 'sms'));
    }

    public function test_a_non_urgent_type_is_never_sent_by_sms(): void
    {
        $this->twilioAccepts();
        $this->optIn(1);
        $before = SchoolMessagingSetting::query()->firstOrFail()->toArray();
        try {
            $this->optIn(1, ['comms.urgent', 'comms.notice']);
            $this->fail('A non-urgent type was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('sms_types.1', $e->errors());
        }
        $this->assertSame($before, SchoolMessagingSetting::query()->firstOrFail()->toArray());

        // Even a row changed behind the service's back never texts it.
        SchoolMessagingSetting::query()->update(['sms_types' => json_encode(['comms.urgent', 'attendance.absence', 'comms.notice', 'fees.reminder'])]);
        $notifier = app(Notifier::class);
        $notifier->send($this->guardian(), 'comms.notice', 'Trip forms', 'Bring them back', '/notifications', $this->notice('comms.notice', 'Trip forms', 'Bring them back', true));
        $notifier->send($this->guardian(), 'fees.reminder', 'Fees due', 'Pay soon', '/notifications', $this->notice('fees.reminder', 'Fees due', 'Pay soon', true));

        $this->assertSame([], $this->twilioRequests());
        $this->assertSame(0, $this->texts());
    }

    public function test_only_office_staff_with_the_settings_permission_change_the_setting(): void
    {
        $payload = ['revision' => 0, 'sms_enabled' => '1', 'whatsapp_enabled' => '0', 'sms_types' => ['comms.urgent']];
        foreach (['tara_user', 'farid_user', 'asha_user'] as $person) {
            $this->actingAs(User::findOrFail($this->schoolA->ids[$person]))->get('/urgent-messaging')->assertForbidden();
            $this->put('/urgent-messaging', $payload)->assertForbidden();
            $this->app['auth']->forgetGuards();
        }
        $this->assertSame(0, SchoolMessagingSetting::query()->count());
        $this->assertSame(0, AuditLog::query()->where('action', 'messaging.settings_changed')->count());
        $this->expectException(HttpException::class);
        try {
            app(UrgentMessaging::class)->change(User::findOrFail($this->schoolA->ids['tara_user']), $payload);
        } finally {
            $this->assertSame(0, SchoolMessagingSetting::query()->count());
        }
    }

    public function test_the_school_office_switches_sms_and_whatsapp_on_from_settings(): void
    {
        $this->actingAs($this->superAdmin())->get('/urgent-messaging')->assertOk()
            ->assertSee('Urgent school notice')->assertSee('Absence alert')->assertSee('Guardians have agreed to receive WhatsApp messages');

        $this->put('/urgent-messaging', ['revision' => 0, 'sms_enabled' => '1', 'whatsapp_enabled' => '1', 'sms_types' => ['comms.urgent'], 'whatsapp_types' => ['comms.urgent']])
            ->assertSessionHasErrors('whatsapp_consent');
        $this->assertSame(0, SchoolMessagingSetting::query()->count());

        $this->put('/urgent-messaging', ['revision' => 0, 'sms_enabled' => '1', 'whatsapp_enabled' => '1', 'sms_types' => ['comms.urgent', 'attendance.absence'], 'whatsapp_types' => ['comms.urgent'], 'whatsapp_consent' => '1'])
            ->assertRedirect('/urgent-messaging')->assertSessionHasNoErrors();
        $setting = SchoolMessagingSetting::query()->firstOrFail();
        $this->assertSame([true, true, ['comms.urgent', 'attendance.absence'], ['comms.urgent'], 1], [$setting->sms_enabled, $setting->whatsapp_enabled, $setting->sms_types, $setting->whatsapp_types, $setting->revision]);
        $this->assertNotNull($setting->whatsapp_consent_confirmed_at);
        $this->assertSame(1, AuditLog::query()->where('action', 'messaging.settings_changed')->count());

        // A stale form is refused rather than overwriting a newer choice.
        $this->put('/urgent-messaging', ['revision' => 0, 'sms_enabled' => '0', 'whatsapp_enabled' => '0'])->assertSessionHasErrors('revision');
        $this->assertTrue(SchoolMessagingSetting::query()->firstOrFail()->sms_enabled);

        // A type without an approved WhatsApp template cannot be chosen for WhatsApp.
        $types = config('urgent_messaging.types');
        $types['attendance.absence']['whatsapp_notice_template'] = null;
        config(['urgent_messaging.types' => $types]);
        $this->put('/urgent-messaging', ['revision' => 1, 'sms_enabled' => '1', 'whatsapp_enabled' => '1', 'whatsapp_types' => ['attendance.absence']])->assertSessionHasErrors('whatsapp_types.0');
        $this->assertSame(1, SchoolMessagingSetting::query()->firstOrFail()->revision);
    }

    public function test_school_a_never_texts_school_b_guardians_and_school_b_settings_do_not_affect_school_a(): void
    {
        $this->twilioAccepts();
        $this->optIn(2);
        $schoolBGuardian = User::acrossSchools()->findOrFail($this->schoolB->ids['farid_user']);

        // School A has not opted in: B's opt-in does not make A send.
        app(Notifier::class)->send($this->guardian(), 'comms.urgent', 'Storm', 'Closed', '/notifications', $this->notice('comms.urgent', 'Storm', 'Closed', true));
        $this->assertSame([], $this->twilioRequests());

        // School A opted in: its notice still never reaches B's guardian.
        $this->optIn(1);
        $this->assertSame([], app(Notifier::class)->send($schoolBGuardian, 'comms.urgent', 'Storm', 'Closed', '/notifications', $this->notice('comms.urgent', 'Storm', 'Closed', true)));
        $this->assertSame([], $this->twilioRequests());

        // A guardian with no number at School A is not texted the number School B keeps for them.
        DB::table('parents')->where('school_id', 1)->where('user_id', $this->guardian()->id)->update(['mobile' => '']);
        DB::table('parents')->insert(['school_id' => 2, 'user_id' => $this->guardian()->id, 'first_name' => 'Farid', 'last_name' => 'Baobab', 'gender' => 'Male', 'email' => 'farid.b@example.org', 'mobile' => '084 555 1234', 'occupation' => 'Engineer', 'created_at' => now(), 'updated_at' => now()]);
        $sent = app(Notifier::class)->send($this->guardian(), 'comms.urgent', 'Storm', 'Closed', '/notifications', $this->notice('comms.urgent', 'Storm', 'Closed', true));
        $this->assertNotContains('sms', $sent);
        $this->assertSame([], $this->twilioRequests());
        $this->assertSame(0, $this->texts());

        // School A's change leaves School B's row as it was.
        $schoolB = app(CurrentSchool::class)->run(2, fn () => SchoolMessagingSetting::query()->firstOrFail()->toArray());
        $this->optIn(1, ['attendance.absence']);
        $this->assertSame($schoolB, app(CurrentSchool::class)->run(2, fn () => SchoolMessagingSetting::query()->firstOrFail()->toArray()));
    }

    public function test_a_retry_or_a_repeated_fanout_never_repeats_a_sent_message(): void
    {
        $this->twilioAccepts();
        $this->optIn(1, ['comms.urgent']);
        $notice = $this->notice('comms.urgent', 'Storm', 'Closed', true);
        Queue::fake();
        app(Notifier::class)->send($this->guardian(), 'comms.urgent', 'Storm', 'Closed', '/notifications', $notice);
        app(Notifier::class)->send($this->guardian(), 'comms.urgent', 'Storm', 'Closed', '/notifications', $notice);
        Queue::assertPushed(SendText::class, 1);
        $job = Queue::pushed(SendText::class)->first();

        $job->handle(app(TwilioClient::class));
        $job->handle(app(TwilioClient::class));

        $this->assertCount(1, $this->twilioRequests());
        $this->assertSame(1, NotificationDelivery::query()->where('delivery_key', $job->deliveryKey)->count());
    }

    public function test_a_changed_number_or_a_withdrawn_opt_in_stops_a_queued_text(): void
    {
        $this->twilioAccepts();
        $this->optIn(1, ['comms.urgent']);
        Queue::fake();
        app(Notifier::class)->send($this->guardian(), 'comms.urgent', 'Storm', 'Closed', '/notifications', $this->notice('comms.urgent', 'Storm', 'Closed', true));
        app(Notifier::class)->send($this->guardian(), 'comms.urgent', 'Flood', 'Closed', '/notifications', $this->notice('comms.urgent', 'Flood', 'Closed', true));
        [$first, $second] = Queue::pushed(SendText::class)->values()->all();

        DB::table('parents')->where('school_id', 1)->where('user_id', $this->guardian()->id)->update(['mobile' => '072 000 1111']);
        $first->handle(app(TwilioClient::class));
        DB::table('parents')->where('school_id', 1)->where('user_id', $this->guardian()->id)->update(['mobile' => '082 123 4567']);
        $this->optIn(1, []);
        $second->handle(app(TwilioClient::class));

        $this->assertSame([], $this->twilioRequests());
        $this->assertSame(['suppressed', 'suppressed'], NotificationDelivery::query()->where('channel', 'sms')->orderBy('id')->pluck('status')->all());
    }

    public function test_quiet_hours_are_not_applied_to_urgent_texts(): void
    {
        $this->twilioAccepts();
        $this->travelTo(CarbonImmutable::parse('2026-11-03 02:30', 'Africa/Johannesburg'));
        $this->optIn(1, ['comms.urgent', 'attendance.absence']);

        app(Notifier::class)->send($this->guardian(), 'attendance.absence', 'Absence alert', 'Marked absent', '/notifications', $this->notice('attendance.absence', 'Absence alert', 'Marked absent'));

        $this->assertCount(1, $this->twilioRequests());
        $this->assertSame('sent', NotificationDelivery::query()->where('channel', 'sms')->value('status'));
    }

    public function test_no_credentials_number_or_twilio_message_text_reaches_logs_rows_or_audit(): void
    {
        $logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged) {
            $logged[] = $event->message.' '.json_encode($event->context);
        });
        $this->optIn(1, ['comms.urgent'], ['comms.urgent']);

        // Twilio refuses (quoting the number), then cannot be reached (the URL names the account).
        Http::fake(function (HttpRequest $request) {
            static $calls = 0;
            if ($calls++ === 0) {
                return Http::response(['code' => 21211, 'message' => "The 'To' number ".self::NUMBER.' is not a valid phone number.'], 400);
            }
            throw new ConnectionException('cURL error 7: '.$request->url());
        });
        app(Notifier::class)->send($this->guardian(), 'comms.urgent', 'Storm', 'Closed', '/notifications', $this->notice('comms.urgent', 'Storm', 'Closed', true));

        $rows = NotificationDelivery::query()->whereIn('channel', ['sms', 'whatsapp'])->orderBy('channel')->get();
        $this->assertSame(['failed', 'failed'], $rows->pluck('status')->all());
        $this->assertSame('Twilio answered HTTP 400, error 21211.', $rows[0]->error_summary);
        $this->assertSame('Twilio could not be reached.', $rows[1]->error_summary);
        $haystack = implode("\n", $logged).json_encode(NotificationDelivery::query()->get()->toArray()).json_encode(AuditLog::query()->get()->toArray())
            .json_encode(DB::table('failed_jobs')->get()->all()).json_encode(DB::table('webhook_receipts')->get()->all());
        $this->assertNotSame('', implode('', $logged));
        foreach ([self::TOKEN, self::SID, self::NUMBER, '821234567', 'is not a valid phone number'] as $secret) {
            $this->assertStringNotContainsString($secret, $haystack);
        }
        $this->assertStringNotContainsString(self::TOKEN, json_encode(config('urgent_messaging')));
    }

    public function test_a_signed_status_callback_updates_the_delivery_with_its_segments_and_price(): void
    {
        $row = $this->sentText();
        $params = ['AccountSid' => self::SID, 'MessageSid' => self::MESSAGE_SID, 'SmsSid' => self::MESSAGE_SID, 'MessageStatus' => 'delivered', 'SmsStatus' => 'delivered',
            'From' => '+27870000000', 'To' => self::NUMBER, 'ApiVersion' => '2010-04-01', 'NumSegments' => '2', 'Price' => '-0.07925', 'PriceUnit' => 'USD', 'Body' => ''];

        $this->signed($params)->assertOk()->assertJson(['error' => false, 'message' => 'Recorded']);

        $row->refresh();
        $this->assertSame(['delivered', 2, 8, 'USD'], [$row->status, $row->segments, $row->price_cents, $row->price_currency]);
        $this->assertNotNull($row->feedback_at);

        // A late "sent" does not undo the delivery; an undelivered report is recorded with its code only.
        $this->signed(['AccountSid' => self::SID, 'MessageSid' => self::MESSAGE_SID, 'MessageStatus' => 'sent'])->assertOk();
        $this->assertSame('delivered', $row->refresh()->status);
        $other = $this->sentText(1, 'SM11111111111111111111111111111111');
        $this->signed(['AccountSid' => self::SID, 'MessageSid' => 'SM11111111111111111111111111111111', 'MessageStatus' => 'undelivered', 'ErrorCode' => '30003'])->assertOk();
        $this->assertSame(['undelivered', 'Twilio undelivered, error 30003.'], [$other->refresh()->status, $other->error_summary]);
    }

    public function test_a_callback_with_a_bad_signature_another_account_or_no_token_is_refused(): void
    {
        $row = $this->sentText();
        $before = $row->fresh()->toArray();
        $params = ['AccountSid' => self::SID, 'MessageSid' => self::MESSAGE_SID, 'MessageStatus' => 'failed', 'ErrorCode' => '30008'];

        $this->signed($params, 'bm90IGEgcmVhbCBzaWduYXR1cmU=')->assertStatus(401);
        $this->signed($params, '')->assertStatus(401);
        $this->signed(array_replace($params, ['MessageStatus' => 'failed']), TwilioClient::signature('another-token', self::CALLBACK, $params))->assertStatus(401);
        $this->signed(array_replace($params, ['AccountSid' => 'ACcccccccccccccccccccccccccccccccc']))->assertStatus(403);
        // Signed for a different URL (replayed from elsewhere).
        $this->signed($params, TwilioClient::signature(self::TOKEN, 'https://elsewhere.example/webhook', $params))->assertStatus(401);
        config(['services.twilio.auth_token' => null]);
        $this->signed($params, TwilioClient::signature('', self::CALLBACK, $params))->assertStatus(401);

        $this->assertSame($before, $row->fresh()->toArray());
    }

    public function test_a_callback_for_an_unknown_message_is_ignored(): void
    {
        $row = $this->sentText();
        $before = NotificationDelivery::query()->get()->toArray();

        $this->signed(['AccountSid' => self::SID, 'MessageSid' => 'SM99999999999999999999999999999999', 'MessageStatus' => 'delivered'])
            ->assertOk()->assertJson(['error' => false, 'message' => 'Ignored']);
        $this->signed(['AccountSid' => self::SID, 'MessageSid' => self::MESSAGE_SID, 'MessageStatus' => 'sending'])
            ->assertOk()->assertJson(['message' => 'Ignored']);

        $this->assertSame($before, NotificationDelivery::query()->get()->toArray());
        $this->assertSame('sent', $row->fresh()->status);
    }

    public function test_the_route_is_rate_limited_and_outside_csrf(): void
    {
        $route = app('router')->getRoutes()->getByName('webhook.messaging.twilio');
        $this->assertSame('webhook/messaging/twilio', $route->uri());
        $this->assertContains('throttle:messaging-webhook', $route->gatherMiddleware());
    }

    public function test_prices_twilio_reports_later_are_recorded_and_hq_sees_each_schools_count_and_cost(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00', 'Africa/Johannesburg'));
        $this->sentText(1, self::MESSAGE_SID, ['sent_at' => now()->subHour()]);
        $this->sentText(1, 'SM22222222222222222222222222222222', ['sent_at' => now()->subHour(), 'channel' => 'whatsapp', 'type' => 'whatsapp.comms.urgent', 'segments' => 1, 'price_cents' => 1, 'price_currency' => 'USD', 'status' => 'delivered']);
        $this->sentText(2, 'SM33333333333333333333333333333333', ['sent_at' => now()->subHour(), 'segments' => 3, 'price_cents' => 24, 'price_currency' => 'USD', 'status' => 'failed']);
        $this->sentText(2, 'SM44444444444444444444444444444444', ['sent_at' => now()->subMonth(), 'segments' => 1, 'price_cents' => 8, 'price_currency' => 'USD']);
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => self::MESSAGE_SID, 'status' => 'delivered', 'num_segments' => '2', 'price' => '-0.15850', 'price_unit' => 'USD'])]);

        $this->artisan('messaging:fetch-prices')->assertSuccessful();
        Http::assertSent(fn (HttpRequest $request) => $request->method() === 'GET' && str_ends_with($request->url(), '/Messages/'.self::MESSAGE_SID.'.json'));
        Http::assertSentCount(1);
        $priced = NotificationDelivery::query()->where('provider_message_id', self::MESSAGE_SID)->firstOrFail();
        $this->assertSame([2, 16, 'USD'], [$priced->segments, $priced->price_cents, $priced->price_currency]);

        app(CurrentSchool::class)->forget();
        $this->actingAs($this->staff('finance'))->get('/saas/messaging?month=2026-11')->assertOk()
            ->assertSee('November 2026')->assertSee(School::query()->findOrFail(1)->name)->assertSee('Baobab High')
            ->assertSee('USD 0.16')->assertSee('USD 0.01')->assertSee('USD 0.24')->assertDontSee('USD 0.08')->assertDontSee('821234567');
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->staff('content'))->get('/saas/messaging')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->superAdmin())->get('/saas/messaging')->assertForbidden();
    }

    public function test_recorded_numbers_are_read_as_south_african_unless_they_say_otherwise(): void
    {
        $this->assertSame('+27821234567', UrgentMessaging::normalise('082 123 4567'));
        $this->assertSame('+27821234567', UrgentMessaging::normalise('27821234567'));
        $this->assertSame('+27821234567', UrgentMessaging::normalise('+27 (82) 123-4567'));
        $this->assertSame('+447700900123', UrgentMessaging::normalise('0044 7700 900123'));
        $this->assertNull(UrgentMessaging::normalise('12345'));
        $this->assertNull(UrgentMessaging::normalise('not a number'));
        $this->assertSame(8, TwilioClient::priceCents('-0.07925'));
        $this->assertSame(7, TwilioClient::priceCents('-0.07490'));
        $this->assertSame(125, TwilioClient::priceCents('1.25'));
        $this->assertNull(TwilioClient::priceCents(null));
    }
}
