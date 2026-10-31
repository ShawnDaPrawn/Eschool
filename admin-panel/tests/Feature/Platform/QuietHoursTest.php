<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\FanoutNotification;
use App\Jobs\Platform\SendPush;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\NotificationQuietHours;
use App\Models\Settings;
use App\Models\User;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Notifications\FcmClient;
use App\Services\Platform\Notifications\QuietHours;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Push quiet hours (Action Plan 1.5). engineering/specs/platform/quiet-hours.md.
 */
class QuietHoursTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        Settings::create(['type' => 'project_id', 'message' => 'demo-project']);
        $this->app->bind(FcmClient::class, fn () => new class extends FcmClient
        {
            public function accessToken(): ?string
            {
                return 'test-access-token';
            }
        });
        Http::fake(['fcm.googleapis.com/*' => Http::response(['name' => 'projects/demo/messages/0:1'])]);
    }

    private function parent(string $platform = 'android'): User
    {
        static $n = 0;
        $n++;
        $user = User::create(['first_name' => 'Quiet', 'last_name' => 'Parent'.$n, 'email' => "quiet{$n}@example.org", 'password' => 'x', 'mobile' => '0',
            'gender' => 'female', 'image' => '', 'fcm_id' => 'token-'.$n, 'device_type' => $platform, 'status' => 1]);
        $user->assignRole('Parent');

        return $user;
    }

    private function quiet(User $user, string $from = '21:00', string $to = '06:30', bool $enabled = true): void
    {
        app(QuietHours::class)->save($user, ['enabled' => $enabled, 'starts_at' => $from, 'ends_at' => $to, 'timezone' => 'Africa/Johannesburg']);
    }

    /** Queues a notice's pushes and returns them, without sending. */
    private function queue(User $user, string $type): array
    {
        Queue::fake();
        sendSimpleNotification([$user->id], 'Hello', 'A notice', $type, null, null);
        Outbox::dispatchPending();
        foreach (Queue::pushed(FanoutNotification::class) as $job) {
            app()->call([$job, 'handle']);
        }

        return Queue::pushed(SendPush::class)->all();
    }

    public function test_a_push_in_quiet_hours_waits_until_they_end_then_is_sent_once(): void
    {
        config(['queue.default' => 'database']);
        $this->travelTo(CarbonImmutable::parse('2026-03-02 22:15', 'Africa/Johannesburg'));
        $user = $this->parent();
        $this->quiet($user);
        [$job] = $this->queue($user, 'notice');

        $job->handle(app(FcmClient::class));
        Http::assertNothingSent();
        $held = Queue::pushed(SendPush::class)->last();
        $this->assertNotSame($job, $held, 'a fresh job holds the same delivery');
        $this->assertSame($job->deliveryKey, $held->deliveryKey);
        $this->assertEquals(CarbonImmutable::parse('2026-03-03 06:30', 'Africa/Johannesburg'), CarbonImmutable::instance($held->delay));
        $this->assertSame('queued', NotificationDelivery::query()->where('delivery_key', $job->deliveryKey)->sole()->status);

        $this->travelTo(CarbonImmutable::parse('2026-03-03 06:30', 'Africa/Johannesburg'));
        $held->handle(app(FcmClient::class));
        Http::assertSentCount(1);
        $this->assertSame('sent', NotificationDelivery::query()->where('delivery_key', $job->deliveryKey)->sole()->status);
    }

    public function test_safety_notices_and_people_without_quiet_hours_are_never_held(): void
    {
        config(['queue.default' => 'database']);
        $this->travelTo(CarbonImmutable::parse('2026-03-02 23:00', 'Africa/Johannesburg'));
        $quiet = $this->parent();
        $this->quiet($quiet);
        // An absence alert (legacy "attendance") is a safety notice.
        [$alert] = $this->queue($quiet, 'attendance');
        $alert->handle(app(FcmClient::class));
        Http::assertSentCount(1);

        $other = $this->parent();
        $this->quiet($other, enabled: false);
        [$job] = $this->queue($other, 'notice');
        $job->handle(app(FcmClient::class));
        Http::assertSentCount(2);
        $this->assertSame(0, NotificationQuietHours::query()->where('user_id', $quiet->id)->where('enabled', false)->count(), 'one person never changes another\'s setting');
    }

    public function test_the_window_is_worked_out_in_the_persons_time_zone(): void
    {
        $user = $this->parent();
        $quiet = app(QuietHours::class);
        $at = fn (string $local) => CarbonImmutable::parse($local, 'Africa/Johannesburg');

        $this->quiet($user, '21:00', '06:30');
        $this->assertNull($quiet->holdUntil($user->id, 'comms.notice', $at('2026-03-02 20:59')));
        $this->assertEquals($at('2026-03-03 06:30'), $quiet->holdUntil($user->id, 'comms.notice', $at('2026-03-02 21:00')));
        $this->assertEquals($at('2026-03-03 06:30'), $quiet->holdUntil($user->id, 'comms.notice', $at('2026-03-03 03:00')));
        $this->assertNull($quiet->holdUntil($user->id, 'comms.notice', $at('2026-03-03 06:30')));
        // UTC 20:00 is 22:00 in Johannesburg.
        $this->assertNotNull($quiet->holdUntil($user->id, 'comms.notice', CarbonImmutable::parse('2026-03-02 20:00', 'UTC')));

        $this->quiet($user, '13:00', '14:00');
        $this->assertEquals($at('2026-03-02 14:00'), $quiet->holdUntil($user->id, 'comms.notice', $at('2026-03-02 13:30')));
        $this->assertNull($quiet->holdUntil($user->id, 'comms.notice', $at('2026-03-02 21:30')));
        foreach (['safeguarding.alert', 'comms.urgent', 'attendance.absence', 'account.new_device'] as $type) {
            $this->assertNull($quiet->holdUntil($user->id, $type, $at('2026-03-02 13:30')), $type);
        }
    }

    public function test_the_app_reads_and_sets_only_its_own_quiet_hours(): void
    {
        $user = $this->parent('ios');
        $other = $this->parent();
        $this->quiet($other, '20:00', '05:00');
        $token = $user->createToken('app')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token, 'X-School-Id' => '1'];

        $this->getJson('/api/v2/me/notification-quiet-hours', $headers)->assertOk()->assertJsonPath('data.enabled', false)
            ->assertJsonPath('data.timezone', 'Africa/Johannesburg')->assertHeader('Cache-Control', 'no-store, private');
        $this->putJson('/api/v2/me/notification-quiet-hours', ['enabled' => true, 'starts_at' => '22:00', 'ends_at' => '06:00'], $headers)
            ->assertOk()->assertJsonPath('data.starts_at', '22:00')->assertJsonPath('data.exempt_types', QuietHours::exemptTypes());
        foreach ([['starts_at' => '25:00'], ['ends_at' => '22:00'], ['timezone' => 'Mars/Olympus'], ['enabled' => 'sometimes']] as $bad) {
            $this->putJson('/api/v2/me/notification-quiet-hours', $bad + ['enabled' => true, 'starts_at' => '22:00', 'ends_at' => '06:00'], $headers)->assertUnprocessable();
        }
        $this->assertSame('22:00:00', NotificationQuietHours::query()->where('user_id', $user->id)->value('starts_at'));
        $this->assertSame('20:00:00', NotificationQuietHours::query()->where('user_id', $other->id)->value('starts_at'));
        $this->app['auth']->forgetGuards();
        $this->withoutToken()->getJson('/api/v2/me/notification-quiet-hours', ['X-School-Id' => '1'])->assertUnauthorized();
    }

    public function test_push_payloads_say_which_channel_and_how_urgent(): void
    {
        $ios = $this->parent('ios');
        [$notice] = $this->queue($ios, 'notice');
        $this->assertSame('comms', $notice->message['message']['data']['channel']);
        $this->assertSame(['interruption-level' => 'active', 'thread-id' => 'comms'], $notice->message['message']['apns']['payload']['aps']);
        [$alert] = $this->queue($ios, 'attendance');
        $this->assertSame('attendance', $alert->message['message']['data']['channel']);
        $this->assertSame('time-sensitive', $alert->message['message']['apns']['payload']['aps']['interruption-level']);
    }

    public function test_a_synchronous_queue_sends_at_once_instead_of_holding_forever(): void
    {
        config(['queue.default' => 'sync']);
        $this->travelTo(CarbonImmutable::parse('2026-03-02 22:15', 'Africa/Johannesburg'));
        $user = $this->parent();
        $this->quiet($user);
        [$job] = $this->queue($user, 'notice');
        $job->handle(app(FcmClient::class));
        Http::assertSentCount(1);
        $this->assertCount(1, Queue::pushed(SendPush::class), 'nothing re-queued');
    }

    public function test_an_overnight_end_is_set_on_the_release_day_across_a_clock_change(): void
    {
        $user = $this->parent();
        app(QuietHours::class)->save($user, ['enabled' => true, 'starts_at' => '21:00', 'ends_at' => '01:30', 'timezone' => 'Europe/London']);
        // 29 March 2026: British clocks go forward at 01:00, so 01:30 does not exist that night's morning, but does on 30 March.
        $release = app(QuietHours::class)->holdUntil($user->id, 'comms.notice', CarbonImmutable::parse('2026-03-29 22:00', 'Europe/London'));
        $this->assertSame('2026-03-30 01:30', $release->setTimezone('Europe/London')->format('Y-m-d H:i'));
    }
}
