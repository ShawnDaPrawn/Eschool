<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\SendPush;
use App\Models\Platform\UserDevice;
use App\Models\User;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PushDevices;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * One push registration per signed-in device, not per account.
 * Specification: engineering/specs/platform/push-devices.md.
 */
class PushDevicesTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
    }

    private function parent(): User
    {
        $user = User::findOrFail($this->schoolA->ids['farid_user']);
        $user->forceFill(['fcm_id' => null, 'device_type' => null, 'email_verified_at' => now()])->save();

        return $user;
    }

    private function signedInDevice(User $user, string $token, string $platform = 'android'): int
    {
        $access = $user->createToken(AppTokens::PARENT)->accessToken->id;
        app(PushDevices::class)->register($user, $token, $platform, AppTokens::PARENT, (int) $access);

        return (int) $access;
    }

    public function test_every_signed_in_device_receives_the_notification(): void
    {
        $parent = $this->parent();
        $this->signedInDevice($parent, 'phone-token', 'android');
        $this->signedInDevice($parent, 'tablet-token', 'ios');

        app(Notifier::class)->send($parent->fresh(), 'attendance.absence', 'Register', 'Absent today');

        Queue::assertPushed(SendPush::class, 2);
        $tokens = collect(Queue::pushed(SendPush::class))->map(fn ($job) => $job->message['message']['token'])->sort()->values()->all();
        $this->assertSame(['phone-token', 'tablet-token'], $tokens);
        Queue::assertPushed(SendPush::class, fn ($job) => $job->message['message']['token'] === 'tablet-token' && isset($job->message['message']['notification']));
        Queue::assertPushed(SendPush::class, fn ($job) => $job->message['message']['token'] === 'phone-token' && ! isset($job->message['message']['notification']));
        $this->assertSame(2, DB::table('notification_deliveries')->where('channel', 'push')->count());
    }

    public function test_ending_one_sign_in_stops_only_that_devices_notifications(): void
    {
        $parent = $this->parent();
        $phone = $this->signedInDevice($parent, 'phone-token');
        $this->signedInDevice($parent, 'tablet-token');

        app(PushDevices::class)->revokeForAccessToken($phone);

        $this->assertSame(['tablet-token'], array_column(app(PushDevices::class)->destinations($parent->fresh()), 'token'));
    }

    public function test_a_deleted_sign_in_token_ends_its_device_without_bookkeeping(): void
    {
        $parent = $this->parent();
        $phone = $this->signedInDevice($parent, 'phone-token');
        $tablet = $this->signedInDevice($parent, 'tablet-token');

        // As a password change or account revocation does. users.fcm_id still
        // holds the tablet, the latest registration: it must not bring it back.
        $parent->tokens()->whereKey($tablet)->delete();

        $this->assertSame('tablet-token', $parent->fresh()->fcm_id);
        $this->assertSame(['phone-token'], array_column(app(PushDevices::class)->destinations($parent->fresh()), 'token'));
        $this->assertFalse(app(PushDevices::class)->isCurrent($parent->fresh(), 'tablet-token'));
    }

    public function test_signing_out_of_a_sign_in_from_before_registrations_ends_its_device(): void
    {
        $parent = $this->parent();
        // As the migration leaves an existing client: registered, but bound to no sign-in.
        $parent->forceFill(['fcm_id' => 'old-client', 'device_type' => 'android'])->save();
        UserDevice::query()->create(['user_id' => $parent->id, 'token_hash' => hash('sha256', 'old-client'), 'token' => 'old-client', 'platform' => 'android', 'last_seen_at' => now()]);
        $token = $parent->createToken(AppTokens::PARENT)->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertSame([], app(PushDevices::class)->destinations($parent->fresh()));
        $this->assertSame('', (string) $parent->fresh()->fcm_id);
    }

    public function test_a_shared_phone_moves_to_the_account_that_signed_in_last(): void
    {
        $first = $this->parent();
        $second = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->assertNotSame($first->id, $second->id);

        $this->signedInDevice($first, 'family-phone');
        app(PushDevices::class)->register($second, 'family-phone', 'android');

        $this->assertSame([], array_column(app(PushDevices::class)->destinations($first->fresh()), 'token'));
        $this->assertContains('family-phone', array_column(app(PushDevices::class)->destinations($second->fresh()), 'token'));
        $this->assertSame(1, UserDevice::query()->where('token_hash', hash('sha256', 'family-phone'))->count());
    }

    public function test_a_queued_message_for_a_device_that_signed_out_is_suppressed(): void
    {
        $parent = $this->parent();
        $phone = $this->signedInDevice($parent, 'phone-token');
        app(Notifier::class)->send($parent->fresh(), 'attendance.absence', 'Register', 'Absent today');
        app(PushDevices::class)->revokeForAccessToken($phone);
        Http::fake();

        app()->call([Queue::pushed(SendPush::class)->first(), 'handle']);

        Http::assertNothingSent();
        $this->assertSame(['suppressed'], DB::table('notification_deliveries')->where('channel', 'push')->pluck('status')->unique()->all());
    }

    public function test_an_unregistered_token_is_forgotten_for_that_account(): void
    {
        $parent = $this->parent();
        $this->signedInDevice($parent, 'stale-token');
        $this->signedInDevice($parent, 'fresh-token');

        app(PushDevices::class)->forget((int) $parent->id, 'stale-token');

        $this->assertSame(['fresh-token'], array_column(app(PushDevices::class)->destinations($parent->fresh()), 'token'));
        $this->assertNotNull(UserDevice::query()->where('token_hash', hash('sha256', 'stale-token'))->value('revoked_at'));
    }

    public function test_tokens_are_stored_encrypted_and_never_serialised(): void
    {
        $parent = $this->parent();
        $this->signedInDevice($parent, 'secret-device-token');

        $raw = DB::table('user_devices')->value('token');
        $this->assertStringNotContainsString('secret-device-token', (string) $raw);
        $this->assertArrayNotHasKey('token', UserDevice::query()->firstOrFail()->toArray());
    }

    public function test_only_the_most_recent_devices_are_kept(): void
    {
        $parent = $this->parent();
        foreach (range(1, PushDevices::MAX_ACTIVE + 2) as $n) {
            app(PushDevices::class)->register($parent, 'device-'.$n, 'android');
            $this->travel(1)->minutes();
        }

        $this->assertCount(PushDevices::MAX_ACTIVE, app(PushDevices::class)->destinations($parent->fresh()));
    }

    public function test_an_account_without_a_device_registration_still_uses_its_legacy_token(): void
    {
        $parent = $this->parent();
        $parent->forceFill(['fcm_id' => 'installed-client', 'device_type' => 'android'])->save();

        $this->assertSame(['installed-client'], array_column(app(PushDevices::class)->destinations($parent->fresh()), 'token'));
    }

    public function test_unknown_platforms_and_empty_tokens_are_not_registered(): void
    {
        $parent = $this->parent();

        $this->assertNull(app(PushDevices::class)->register($parent, '', 'android'));
        $this->assertNull(app(PushDevices::class)->register($parent, 'web-token', 'web'));
        $this->assertSame(0, UserDevice::query()->count());
    }
}
