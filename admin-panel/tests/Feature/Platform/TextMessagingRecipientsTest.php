<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\SendText;
use App\Models\Notification;
use App\Models\Parents;
use App\Models\Platform\AuditLog;
use App\Models\Platform\MessagingChoice;
use App\Models\Platform\MessagingNumber;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMessagingSetting;
use App\Models\User;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\TextNumbers;
use App\Services\Platform\Notifications\TwilioClient;
use App\Services\Platform\Notifications\UrgentMessaging;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * SMS and WhatsApp follow-ups (Action Plan 2.7): a number is texted only
 * after its owner verifies it with a one-time code; each guardian and staff
 * member chooses SMS, WhatsApp, both or neither per notice; the school can
 * opt staff in. Twilio is never called: every request is faked.
 * engineering/specs/platform/sms-whatsapp.md.
 */
class TextMessagingRecipientsTest extends TestCase
{
    use PassesPlatformTwoFactor;
    use UsesTwoSchools;

    private const NUMBER = '+27821234567';

    private const TARA_NUMBER = '+27123456789';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        // The fixtures' memberships start on the day they are built; some tests move the clock.
        DB::table('school_memberships')->where('starts_on', '>', '2026-01-01')->update(['starts_on' => '2026-01-01']);
        config([
            'services.twilio.account_sid' => 'ACaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'services.twilio.auth_token' => 'aaaabbbbccccddddtoken',
            'services.twilio.messaging_service_sid' => 'MGbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
            'services.twilio.sms_from' => null,
            'services.twilio.whatsapp_from' => 'whatsapp:+27870000000',
            'services.twilio.status_callback_url' => 'https://panel.example.test/webhook/messaging/twilio',
        ]);
        $types = config('urgent_messaging.types');
        foreach (array_keys($types) as $type) {
            $types[$type]['whatsapp_notice_template'] = 'HXnotice00000000000000000000000000';
        }
        config(['urgent_messaging.types' => $types]);
        Http::preventStrayRequests();
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM0123456789abcdef0123456789abcdef', 'status' => 'accepted', 'num_segments' => '1', 'price' => null, 'price_unit' => 'USD'], 201)]);
        DB::table('parents')->where('school_id', 1)->update(['mobile' => '']);
        DB::table('parents')->where('school_id', 1)->where('user_id', $this->schoolA->ids['farid_user'])->update(['mobile' => '082 123 4567']);
        DB::table('parents')->where('school_id', 2)->update(['mobile' => '083 999 0000']);
    }

    private function person(string $name, int $school = 1): User
    {
        return User::acrossSchools()->findOrFail(($school === 1 ? $this->schoolA : $this->schoolB)->ids[$name.'_user']);
    }

    /**
     * @param  list<string>  $sms
     * @param  list<string>  $whatsapp
     * @param  list<string>  $staffSms
     * @param  list<string>  $staffWhatsapp
     */
    private function optIn(array $sms = ['comms.urgent', 'attendance.absence'], array $whatsapp = [], array $staffSms = [], array $staffWhatsapp = []): void
    {
        app(UrgentMessaging::class)->change($this->superAdmin(), [
            'revision' => (int) (SchoolMessagingSetting::query()->value('revision') ?? 0),
            'sms_enabled' => $sms !== [] || $staffSms !== [], 'sms_types' => $sms, 'staff_sms_types' => $staffSms,
            'whatsapp_enabled' => $whatsapp !== [] || $staffWhatsapp !== [], 'whatsapp_types' => $whatsapp, 'staff_whatsapp_types' => $staffWhatsapp,
            'whatsapp_consent' => true,
        ]);
    }

    private function api(User $user, string $method, string $path, array $body = [], int $school = 1): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->json($method, '/api/v2/me/text-messaging'.$path, $body, [
            'Authorization' => 'Bearer '.$user->createToken('app')->plainTextToken, 'X-School-Id' => (string) $school,
        ]);
    }

    /** Back to the panel's session guard after API calls. */
    private function web(User $user): static
    {
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');

        return $this->signInAs($user);
    }

    /** @return list<array<string, mixed>> */
    private function twilioRequests(): array
    {
        return Http::recorded(fn (HttpRequest $request) => str_contains($request->url(), 'api.twilio.com'))
            ->map(fn (array $pair) => $pair[0]->data())->values()->all();
    }

    /** The code in the last SMS Twilio was asked to send. */
    private function lastCode(): string
    {
        $requests = $this->twilioRequests();
        $this->assertNotSame([], $requests);
        $this->assertSame(1, preg_match('/\b(\d{6})\b/', (string) end($requests)['Body'], $match));

        return $match[1];
    }

    private function verifyThroughApp(User $user): void
    {
        $this->api($user, 'POST', '/verification')->assertOk();
        $this->api($user, 'POST', '/verification/confirm', ['code' => $this->lastCode()])->assertOk()->assertJsonPath('data.number.verified', true);
    }

    private function notice(string $type, string $title = 'Storm', string $body = 'Closed', bool $wholeSchool = true): Notification
    {
        $notice = app(Notifier::class)->makeNotice($type, $title, $body, '/notifications');
        if ($wholeSchool) {
            $notice->forceFill(['send_to' => 1])->save();
        }

        return $notice;
    }

    /** @return list<string> the channels texted */
    private function send(User $user, string $type = 'comms.urgent'): array
    {
        $before = count($this->twilioRequests());
        app(Notifier::class)->send($user, $type, 'Storm', 'Closed', '/notifications', $this->notice($type));

        return array_map(fn (array $request) => str_starts_with((string) $request['To'], 'whatsapp:') ? 'whatsapp' : 'sms', array_slice($this->twilioRequests(), $before));
    }

    public function test_emergency_texts_keep_verified_numbers_individual_choices_and_staff_opt_in(): void
    {
        $farid = $this->person('farid');
        $tara = $this->person('tara');
        $this->optIn(['comms.urgent'], ['comms.urgent']);
        $this->assertSame([], $this->send($farid, 'safety.critical'));
        $this->assertSame([], $this->send($tara, 'safety.critical'), 'Guardian channels do not opt staff in.');
        $this->verifyThroughApp($farid);
        $this->assertEqualsCanonicalizing(['sms', 'whatsapp'], $this->send($farid, 'safety.critical'));
        $this->api($farid, 'PUT', '', ['type' => 'safety.critical', 'sms' => false, 'whatsapp' => false])->assertOk();
        $this->assertSame([], $this->send($farid, 'safety.critical'), 'Emergency text delivery retains the person’s own choice.');

        $this->web($this->superAdmin());
        $this->optIn(['comms.urgent'], ['comms.urgent'], ['safety.critical'], ['safety.critical']);
        $this->assertSame([], $this->send($tara, 'safety.critical'), 'School opt-in never replaces number verification.');
        $this->verifyThroughApp($tara);
        $this->assertSame(['sms'], $this->send($tara, 'safety.critical'), 'Staff WhatsApp needs their own opt-in.');
        $this->api($tara, 'PUT', '', ['type' => 'safety.critical', 'sms' => true, 'whatsapp' => true])->assertOk();
        $this->assertEqualsCanonicalizing(['sms', 'whatsapp'], $this->send($tara, 'safety.critical'));
        $this->api($tara, 'PUT', '', ['type' => 'safety.critical', 'sms' => false, 'whatsapp' => false])->assertOk();
        $this->assertSame([], $this->send($tara, 'safety.critical'));
        $this->api($tara, 'PUT', '', ['type' => 'safety.critical', 'sms' => true, 'whatsapp' => true])->assertOk();
        $tara->forceFill(['mobile' => '0729998888'])->save();
        $this->assertSame([], $this->send($tara, 'safety.critical'), 'A changed number must be verified again.');
    }

    public function test_a_guardian_verifies_their_number_with_a_code_sent_by_sms_and_only_then_is_texted(): void
    {
        $logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged) {
            $logged[] = $event->message.' '.json_encode($event->context);
        });
        $this->optIn(['comms.urgent']);
        $farid = $this->person('farid');

        $this->api($farid, 'GET', '')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('data.number.on_record', true)->assertJsonPath('data.number.ending', '567')
            ->assertJsonPath('data.number.verified', false)->assertJsonPath('data.number.can_verify', true)
            ->assertJsonPath('data.notices.0.type', 'comms.urgent')->assertJsonPath('data.notices.0.offered', ['sms']);
        $this->assertSame([], $this->send($farid), 'An unverified number is never texted.');

        $this->api($farid, 'POST', '/verification')->assertOk()->assertJsonPath('data.number.code_sent', true)->assertJsonPath('data.number.verified', false);
        $requests = $this->twilioRequests();
        $this->assertCount(1, $requests);
        $this->assertSame(self::NUMBER, $requests[0]['To']);
        $this->assertStringContainsString(School::query()->findOrFail(1)->name, $requests[0]['Body']);
        $code = $this->lastCode();
        $row = NotificationDelivery::query()->where('type', 'sms.'.TextNumbers::TYPE)->sole();
        $this->assertSame(['sent', 'sms', 1, (int) $farid->id], [$row->status, $row->channel, $row->school_id, (int) $row->recipient_user_id]);
        $stored = app(CurrentSchool::class)->run(1, fn () => MessagingNumber::query()->where('user_id', $farid->id)->sole());
        $this->assertNotNull($stored->code_hash);
        $this->assertStringNotContainsString($code, json_encode(DB::table('messaging_numbers')->get()));

        $this->api($farid, 'POST', '/verification/confirm', ['code' => $code])->assertOk()->assertJsonPath('data.number.verified', true)->assertJsonPath('data.number.code_sent', false);
        $this->assertSame(1, AuditLog::query()->where('action', 'messaging.number_verified')->count());
        // Verifying again sends nothing.
        $this->api($farid, 'POST', '/verification')->assertOk()->assertJsonPath('data.number.verified', true);
        $this->assertCount(1, $this->twilioRequests());

        $this->assertSame(['sms'], $this->send($farid));
        $this->assertSame(self::NUMBER, $this->twilioRequests()[1]['To']);

        // Neither the code nor the number reaches a delivery row, the audit log or a log line.
        $haystack = implode("\n", $logged).json_encode(NotificationDelivery::query()->get()->toArray()).json_encode(AuditLog::query()->get()->toArray())
            .json_encode(DB::table('messaging_numbers')->get()->all());
        foreach ([$code, self::NUMBER, '821234567'] as $secret) {
            $this->assertStringNotContainsString($secret, $haystack);
        }
    }

    public function test_a_wrong_or_expired_code_counts_and_too_many_wrong_codes_lock_verification(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-04 09:00', 'Africa/Johannesburg'));
        $this->optIn(['comms.urgent']);
        $farid = $this->person('farid');
        $row = fn () => app(CurrentSchool::class)->run(1, fn () => MessagingNumber::query()->where('user_id', $farid->id)->sole());

        $this->api($farid, 'POST', '/verification')->assertOk();
        $code = $this->lastCode();
        $wrong = $code === '000000' ? '111111' : '000000';
        $this->api($farid, 'POST', '/verification/confirm', ['code' => $wrong])->assertUnprocessable()->assertJsonFragment(['That code is not right. You have 4 more tries.']);
        $this->api($farid, 'POST', '/verification/confirm', ['code' => 'abc'])->assertUnprocessable();
        $this->assertSame(1, $row()->attempts);

        // Expired: the right code no longer works.
        $this->travel(11)->minutes();
        $this->api($farid, 'POST', '/verification/confirm', ['code' => $code])->assertUnprocessable()->assertJsonFragment(['This code has expired. Ask for a new one.']);
        $this->assertNull($row()->verified_at);

        // A new code, then five wrong tries lock it, even against the right code afterwards.
        $this->api($farid, 'POST', '/verification')->assertOk();
        $code = $this->lastCode();
        $wrong = $code === '000000' ? '111111' : '000000';
        for ($i = 0; $i < 4; $i++) {
            $this->api($farid, 'POST', '/verification/confirm', ['code' => $wrong])->assertUnprocessable();
        }
        $this->api($farid, 'POST', '/verification/confirm', ['code' => $wrong])->assertUnprocessable()->assertJsonFragment(['Too many wrong codes. Try again after 10:11.']);
        $this->api($farid, 'POST', '/verification/confirm', ['code' => $code])->assertUnprocessable();
        $this->api($farid, 'POST', '/verification')->assertUnprocessable();
        $this->assertNull($row()->verified_at);
        $this->assertNotNull($row()->locked_until);
        $this->assertSame(1, AuditLog::query()->where('action', 'messaging.verification_locked')->count());
        $this->assertCount(2, $this->twilioRequests());

        // After the lockout a new code works.
        $this->travel(61)->minutes();
        $this->api($farid, 'POST', '/verification')->assertOk();
        $this->api($farid, 'POST', '/verification/confirm', ['code' => $this->lastCode()])->assertOk()->assertJsonPath('data.number.verified', true);
    }

    public function test_a_delayed_code_from_an_earlier_request_is_never_delivered_after_a_newer_one(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-04 09:00', 'Africa/Johannesburg'));
        $this->optIn(['comms.urgent']);
        $farid = $this->person('farid');
        Queue::fake();
        $this->api($farid, 'POST', '/verification')->assertOk();
        $this->travel(61)->seconds();
        $this->api($farid, 'POST', '/verification')->assertOk();
        [$older, $newer] = Queue::pushed(SendText::class)->values()->all();

        // The first job was delayed: it runs after the second code was issued and must not send its stale code.
        $older->handle(app(TwilioClient::class));
        $this->assertSame([], $this->twilioRequests());
        $this->assertSame('suppressed', NotificationDelivery::query()->where('delivery_key', $older->deliveryKey)->value('status'));

        $newer->handle(app(TwilioClient::class));
        $this->assertCount(1, $this->twilioRequests());
        $this->api($farid, 'POST', '/verification/confirm', ['code' => $this->lastCode()])->assertOk()->assertJsonPath('data.number.verified', true);
    }

    public function test_codes_are_rate_limited_per_person(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-04 09:00', 'Africa/Johannesburg'));
        $this->optIn(['comms.urgent']);
        $farid = $this->person('farid');

        $this->api($farid, 'POST', '/verification')->assertOk();
        $this->api($farid, 'POST', '/verification')->assertStatus(429)->assertHeader('Retry-After');
        $this->travel(61)->seconds();
        $this->api($farid, 'POST', '/verification')->assertOk();
        $this->travel(61)->seconds();
        $this->api($farid, 'POST', '/verification')->assertOk()->assertJsonPath('data.number.next_code_at', fn ($at) => is_string($at));
        $this->travel(61)->seconds();
        $this->api($farid, 'POST', '/verification')->assertStatus(429);
        $this->assertCount(3, $this->twilioRequests());
        $this->travel(1)->hours();
        $this->api($farid, 'POST', '/verification')->assertOk();
        $this->assertCount(4, $this->twilioRequests());
        // The last code is the only one that works.
        $this->api($farid, 'POST', '/verification/confirm', ['code' => $this->lastCode()])->assertOk()->assertJsonPath('data.number.verified', true);
    }

    public function test_a_changed_number_is_no_longer_verified_and_unverified_numbers_are_skipped_with_a_reason(): void
    {
        $this->optIn(['comms.urgent']);
        $farid = $this->person('farid');
        $this->verifyThroughApp($farid);
        $this->assertSame(['sms'], $this->send($farid));

        // The school office records a new number: it is not verified, so it is not texted, and the reason is recorded once.
        $record = Parents::query()->where('user_id', $farid->id)->firstOrFail();
        $record->update(['mobile' => '072 000 1111']);
        $this->api($farid, 'GET', '')->assertJsonPath('data.number.verified', false)->assertJsonPath('data.number.ending', '111');
        $notice = $this->notice('comms.urgent');
        app(Notifier::class)->send($farid, 'comms.urgent', 'Storm', 'Closed', '/notifications', $notice);
        app(Notifier::class)->send($farid, 'comms.urgent', 'Storm', 'Closed', '/notifications', $notice);
        $skipped = NotificationDelivery::query()->where('status', 'suppressed')->where('type', 'sms.comms.urgent')->sole();
        $this->assertSame([UrgentMessaging::UNVERIFIED, DeliveryLog::hash('+27720001111')], [$skipped->error_summary, $skipped->recipient_hash]);
        $this->assertStringNotContainsString('720001111', json_encode($skipped->getAttributes()));
        $this->assertCount(2, $this->twilioRequests(), 'Only the code and the first notice were texted.');

        // Changing it back does not restore the old verification either.
        $record->update(['mobile' => '082 123 4567']);
        $this->api($farid, 'GET', '')->assertJsonPath('data.number.verified', false);
        $this->assertSame([], $this->send($farid));

        // A change written behind the models' back is caught by the number's hash.
        $this->travel(2)->minutes();
        $this->verifyThroughApp($farid);
        DB::table('parents')->where('id', $record->id)->update(['mobile' => '072 000 1111']);
        $this->assertSame([], $this->send($farid));
        $this->assertFalse(app(TextNumbers::class)->verified($farid, '+27720001111'));
    }

    public function test_numbers_recorded_before_verification_started_are_not_texted(): void
    {
        // Rollout: every number the school office recorded is unverified until its owner verifies it.
        $this->optIn(['comms.urgent', 'attendance.absence']);
        DB::table('parents')->where('school_id', 1)->where('user_id', $this->schoolA->ids['mona_user'])->update(['mobile' => '0831112222']);
        foreach (['farid', 'mona'] as $name) {
            $this->assertSame([], $this->send($this->person($name)));
        }
        $this->assertSame([], $this->twilioRequests());
        $this->assertSame(2, NotificationDelivery::query()->where('status', 'suppressed')->where('error_summary', UrgentMessaging::UNVERIFIED)->count());
        $this->assertSame(0, MessagingNumber::query()->count());
    }

    public function test_a_guardian_chooses_sms_whatsapp_both_or_neither_for_each_notice(): void
    {
        $this->optIn(['comms.urgent', 'attendance.absence'], ['comms.urgent']);
        $farid = $this->person('farid');
        $this->verifyThroughApp($farid);
        $state = $this->api($farid, 'GET', '')->assertOk()->json('data.notices');
        $this->assertSame([
            ['type' => 'comms.urgent', 'label' => 'Urgent school notice', 'offered' => ['sms', 'whatsapp'], 'sms' => true, 'whatsapp' => true, 'own_choice' => false],
            ['type' => 'attendance.absence', 'label' => 'Absence alert', 'offered' => ['sms'], 'sms' => true, 'whatsapp' => false, 'own_choice' => false],
            ['type' => 'safety.critical', 'label' => 'Emergency alert', 'offered' => ['sms', 'whatsapp'], 'sms' => true, 'whatsapp' => true, 'own_choice' => false],
        ], $state);
        $this->assertEqualsCanonicalizing(['sms', 'whatsapp'], $this->send($farid));

        // WhatsApp only for urgent notices.
        $this->api($farid, 'PUT', '', ['type' => 'comms.urgent', 'sms' => false, 'whatsapp' => true])->assertOk()
            ->assertJsonPath('data.notices.0.sms', false)->assertJsonPath('data.notices.0.own_choice', true);
        $this->assertSame(['whatsapp'], $this->send($farid));
        $this->assertSame(['sms'], $this->send($farid, 'attendance.absence'), 'A choice for one notice leaves the others as they were.');

        // Neither.
        $this->api($farid, 'PUT', '', ['type' => 'comms.urgent', 'sms' => false, 'whatsapp' => false])->assertOk();
        $this->assertSame([], $this->send($farid));

        // Only within what the school switched on, and only texted notices.
        $before = MessagingChoice::query()->get()->toArray();
        $this->api($farid, 'PUT', '', ['type' => 'attendance.absence', 'sms' => true, 'whatsapp' => true])->assertUnprocessable()->assertJsonValidationErrors('whatsapp', 'data.errors');
        $this->api($farid, 'PUT', '', ['type' => 'comms.notice', 'sms' => true, 'whatsapp' => false])->assertUnprocessable()->assertJsonValidationErrors('type', 'data.errors');
        $this->api($farid, 'PUT', '', ['type' => 'comms.urgent', 'sms' => 'maybe', 'whatsapp' => false])->assertUnprocessable();
        $this->assertSame($before, MessagingChoice::query()->get()->toArray());

        // The school sees that one person chose, not what; saving its own setting again does not override the choice.
        $this->web($this->superAdmin())->get('/urgent-messaging')->assertOk()->assertSee('1 person');
        $this->app['auth']->forgetGuards();
        $this->optIn(['comms.urgent', 'attendance.absence'], ['comms.urgent']);
        $this->assertSame($before, MessagingChoice::query()->get()->toArray());
        $this->assertSame([], $this->send($farid));
        $this->assertSame(2, AuditLog::query()->where('action', 'messaging.choice_changed')->count());
    }

    public function test_staff_are_texted_only_when_the_school_opts_them_in_and_they_verify(): void
    {
        $tara = $this->person('tara');
        $this->optIn(['comms.urgent']);
        $this->assertSame([], $this->send($tara), 'Guardians being opted in does not text staff.');
        $this->assertSame(0, NotificationDelivery::query()->where('recipient_user_id', $tara->id)->whereIn('channel', ['sms', 'whatsapp'])->count());
        $this->api($tara, 'POST', '/verification')->assertUnprocessable()->assertJsonValidationErrors('number', 'data.errors');

        $this->optIn(['comms.urgent'], [], ['comms.urgent'], ['comms.urgent']);
        $this->assertSame([], $this->send($tara));
        $this->assertSame(UrgentMessaging::UNVERIFIED, NotificationDelivery::query()->where('recipient_user_id', $tara->id)->where('channel', 'sms')->value('error_summary'));

        // The number on Tara's own account, verified by Tara in the app.
        $this->api($tara, 'GET', '')->assertOk()->assertJsonPath('data.number.source', 'account')->assertJsonPath('data.number.ending', '789')
            ->assertJsonPath('data.notices.0.offered', ['sms', 'whatsapp'])->assertJsonPath('data.notices.0.whatsapp', false);
        $this->verifyThroughApp($tara);
        $this->assertSame(self::TARA_NUMBER, $this->twilioRequests()[0]['To']);
        $this->assertSame(['sms'], $this->send($tara), 'Staff get WhatsApp only once they choose it.');
        $this->api($tara, 'PUT', '', ['type' => 'comms.urgent', 'sms' => true, 'whatsapp' => true])->assertOk();
        $this->assertEqualsCanonicalizing(['sms', 'whatsapp'], $this->send($tara));

        // Absence alerts are not for staff; safeguarding alerts are not for guardians.
        try {
            $this->optIn(['comms.urgent'], [], ['comms.urgent', 'attendance.absence']);
            $this->fail('An absence alert was opted in for staff.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('staff_sms_types.1', $e->errors());
        }
        try {
            $this->optIn(['comms.urgent', 'safeguarding.alert']);
            $this->fail('A safeguarding alert was opted in for guardians.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('sms_types.1', $e->errors());
        }

        // A new number on Tara's account is not verified at any school.
        $tara->forceFill(['mobile' => '0729998888'])->save();
        $this->assertSame([], $this->send($tara));
        $this->api($tara, 'GET', '')->assertJsonPath('data.number.verified', false);
    }

    public function test_learners_and_staff_without_the_settings_permission_are_refused(): void
    {
        $this->optIn(['comms.urgent']);
        $asha = $this->person('asha');
        foreach ([['GET', '', []], ['PUT', '', ['type' => 'comms.urgent', 'sms' => false, 'whatsapp' => false]], ['POST', '/verification', []], ['POST', '/verification/confirm', ['code' => '123456']]] as [$method, $path, $body]) {
            $this->api($asha, $method, $path, $body)->assertForbidden();
        }
        $this->assertSame(0, MessagingChoice::query()->count() + MessagingNumber::query()->count());
        $this->assertSame([], $this->twilioRequests());

        // A teacher cannot opt staff in.
        $revision = (int) SchoolMessagingSetting::query()->value('revision');
        $this->web($this->person('tara'))->put('/urgent-messaging', ['revision' => $revision, 'sms_enabled' => '1', 'whatsapp_enabled' => '0', 'sms_types' => ['comms.urgent'], 'staff_sms_types' => ['comms.urgent']])->assertForbidden();
        $this->assertSame([], SchoolMessagingSetting::query()->firstOrFail()->staff_sms_types);
        $this->app['auth']->forgetGuards();
        $this->withoutToken()->getJson('/api/v2/me/text-messaging', ['X-School-Id' => '1'])->assertUnauthorized();
    }

    public function test_another_guardian_cannot_verify_someone_elses_number_or_change_their_choice(): void
    {
        $this->optIn(['comms.urgent']);
        $farid = $this->person('farid');
        $mona = $this->person('mona');
        DB::table('parents')->where('school_id', 1)->where('user_id', $mona->id)->update(['mobile' => '0831112222']);

        $this->api($farid, 'POST', '/verification')->assertOk();
        $faridsCode = $this->lastCode();
        // Mona types Farid's code: she has no code waiting, and naming Farid changes nothing.
        $this->api($mona, 'POST', '/verification/confirm', ['code' => $faridsCode, 'user_id' => $farid->id])->assertUnprocessable();
        $this->api($mona, 'POST', '/verification', ['user_id' => $farid->id])->assertOk();
        $this->assertSame('+27831112222', $this->twilioRequests()[1]['To'], 'A code goes only to the number of the person asking.');
        $this->api($mona, 'POST', '/verification/confirm', ['code' => $faridsCode])->assertUnprocessable();
        $this->assertFalse(app(TextNumbers::class)->verified($farid, self::NUMBER));
        $this->assertFalse(app(TextNumbers::class)->verified($mona, '+27831112222'));

        $this->api($farid, 'PUT', '', ['type' => 'comms.urgent', 'sms' => true, 'whatsapp' => false])->assertOk();
        $this->api($mona, 'PUT', '', ['type' => 'comms.urgent', 'sms' => false, 'whatsapp' => false, 'user_id' => $farid->id])->assertOk();
        $this->assertTrue(MessagingChoice::query()->where('user_id', $farid->id)->sole()->sms);
        $this->assertFalse(MessagingChoice::query()->where('user_id', $mona->id)->sole()->sms);
        // Farid's code still verifies Farid.
        $this->api($farid, 'POST', '/verification/confirm', ['code' => $faridsCode])->assertOk()->assertJsonPath('data.number.verified', true);
    }

    public function test_school_b_never_sees_or_uses_school_as_numbers_choices_or_verifications(): void
    {
        $this->optIn(['comms.urgent']);
        $farid = $this->person('farid');
        $this->verifyThroughApp($farid);
        $this->api($farid, 'PUT', '', ['type' => 'comms.urgent', 'sms' => true, 'whatsapp' => false])->assertOk();

        // School B opted in; Farid also has a parent record there with the same number.
        app(CurrentSchool::class)->run(2, fn () => SchoolMessagingSetting::query()->create(['sms_enabled' => true, 'sms_types' => ['comms.urgent'], 'whatsapp_enabled' => false, 'whatsapp_types' => [], 'revision' => 1]));
        DB::table('parents')->insert(['school_id' => 2, 'user_id' => $farid->id, 'first_name' => 'Farid', 'last_name' => 'Baobab', 'gender' => 'Male', 'email' => 'farid.b@example.org', 'mobile' => '082 123 4567', 'occupation' => 'Engineer', 'created_at' => now(), 'updated_at' => now()]);
        app(CurrentSchool::class)->run(2, function () use ($farid) {
            $this->assertSame(0, MessagingNumber::query()->count());
            $this->assertSame(0, MessagingChoice::query()->count());
            $this->assertSame([], app(UrgentMessaging::class)->ownChoices());
            $this->assertSame(0, app(UrgentMessaging::class)->overview()['people']['verified']);
            $this->assertFalse(app(TextNumbers::class)->verified($farid, self::NUMBER), 'A verification at School A does not count at School B.');
        });

        // School B's own guardian, verified at School B, never makes School A text anyone.
        $schoolBGuardian = $this->person('farid', 2);
        app(CurrentSchool::class)->run(2, fn () => MessagingNumber::query()->create(['user_id' => $schoolBGuardian->id, 'source' => 'parent', 'number_hash' => DeliveryLog::hash('+27839990000'), 'verified_at' => now()]));
        $this->assertSame([], app(Notifier::class)->send($schoolBGuardian, 'comms.urgent', 'Storm', 'Closed', '/notifications', $this->notice('comms.urgent')));
        $this->assertSame(1, MessagingNumber::query()->count(), 'School A sees only its own verification.');
        $this->assertSame(1, MessagingChoice::query()->count());

        // School B's guardian cannot use the API at School A.
        $this->api($schoolBGuardian, 'GET', '')->assertStatus(403);
        $this->api($schoolBGuardian, 'POST', '/verification')->assertStatus(403);
    }
}
