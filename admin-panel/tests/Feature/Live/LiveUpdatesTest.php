<?php

declare(strict_types=1);

namespace Tests\Feature\Live;

use App\Events\Consultations\ConsultationCountsChanged;
use App\Jobs\Live\DispatchLiveOutbox;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityStaff;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Live\LiveChannels;
use App\Services\Live\LiveLeases;
use App\Services\Live\LivePublisher;
use App\Services\Platform\Events\Outbox;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class LiveUpdatesTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        config(['live.enabled' => true, 'modules.live_updates' => ['always_on' => true],
            'broadcasting.connections.reverb.key' => 'local-live-key', 'broadcasting.connections.reverb.secret' => str_repeat('x', 32),
            'broadcasting.connections.reverb.app_id' => 'local-live', 'live.client.host' => '127.0.0.1', 'live.client.port' => 8085, 'live.client.scheme' => 'ws']);
        Cache::flush();
    }

    private function token(string $person = 'tara_user'): string
    {
        return User::findOrFail($this->schoolA->ids[$person])->createToken('teacher')->plainTextToken;
    }

    private function api(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($token);
    }

    private function open(string $token): array
    {
        return $this->api($token)->postJson('/api/v2/live/session')->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')->json('data');
    }

    public function test_channels_are_bound_to_exact_account_school_and_credential(): void
    {
        $token = $this->token();
        $session = $this->open($token);
        $this->assertSame($session, $this->open($token));
        $other = $this->open($this->token());
        $this->assertNotSame($session['channel'], $other['channel']);
        $this->api($token)->postJson('/api/v2/live/authorize', ['socket_id' => '123.456', 'channel_name' => $session['channel']])->assertOk()
            ->assertJsonPath('data.auth', 'local-live-key:'.hash_hmac('sha256', '123.456:'.$session['channel'], str_repeat('x', 32)));
        $this->api($token)->postJson('/api/v2/live/authorize', ['socket_id' => '123.456', 'channel_name' => $other['channel']])->assertForbidden();
        $parsed = LiveChannels::parse($session['channel']);
        $this->assertSame(1, $parsed['school']);
        $this->assertSame($this->schoolA->ids['tara_user'], $parsed['user']);
        $this->api($token)->withHeader('X-School-Id', '2')->postJson('/api/v2/live/authorize', ['socket_id' => '123.456', 'channel_name' => $session['channel']])->assertForbidden();
    }

    public function test_revoked_or_expired_token_and_password_change_remove_existing_lease_authority(): void
    {
        $token = $this->token();
        $session = $this->open($token);
        $lease = app(LiveLeases::class)->find($session['lease_id']);
        $this->assertNotNull(app(LiveLeases::class)->actor($lease));
        PersonalAccessToken::findToken($token)->delete();
        $this->assertNull(app(LiveLeases::class)->actor($lease));
        $this->app['auth']->forgetGuards();
        $this->api($token)->postJson('/api/v2/live/session')->assertUnauthorized();
        $session = $this->open($this->token());
        $lease = app(LiveLeases::class)->find($session['lease_id']);
        User::whereKey($this->schoolA->ids['tara_user'])->update(['password_change_required' => true]);
        $this->assertNull(app(LiveLeases::class)->actor($lease));
    }

    public function test_configured_sanctum_lifetime_expires_an_existing_live_lease_before_its_ttl(): void
    {
        config(['sanctum.expiration' => 1]);
        $token = $this->token();
        PersonalAccessToken::findToken($token)->forceFill(['created_at' => now()->subSeconds(59), 'expires_at' => null])->save();
        $session = $this->open($token);
        $lease = app(LiveLeases::class)->find($session['lease_id']);
        $this->assertNotNull(app(LiveLeases::class)->actor($lease));
        $this->travel(2)->seconds();
        $this->assertNotNull(app(LiveLeases::class)->find($session['lease_id']));
        $this->api($token)->postJson('/api/v2/live/session')->assertUnauthorized();
        $this->assertNull(app(LiveLeases::class)->actor($lease));
    }

    public function test_explicit_token_expiry_still_applies_when_global_expiration_is_disabled(): void
    {
        config(['sanctum.expiration' => null]);
        $token = $this->token();
        PersonalAccessToken::findToken($token)->forceFill(['created_at' => now()->subDays(31), 'expires_at' => now()->addSecond()])->save();
        $session = $this->open($token);
        $lease = app(LiveLeases::class)->find($session['lease_id']);
        $this->assertNotNull(app(LiveLeases::class)->actor($lease));
        $this->travel(2)->seconds();
        $this->assertNotNull(app(LiveLeases::class)->find($session['lease_id']));
        $this->assertNull(app(LiveLeases::class)->actor($lease));
    }

    public function test_unauthenticated_and_invalid_requests_have_private_refusals_without_flash(): void
    {
        $this->postJson('/api/v2/live/session')->assertUnauthorized()->assertHeader('Cache-Control', 'no-store, private');
        $token = $this->token();
        $this->open($token);
        $this->withHeaders(['Accept' => 'text/html'])->post('/api/v2/live/authorize', ['socket_id' => 'not-a-socket', 'channel_name' => 'private-other'])
            ->assertUnprocessable()->assertHeader('Cache-Control', 'no-store, private')->assertSessionMissing('_old_input');
        $this->postJson('/api/v2/live/session?unexpected=1')->assertUnprocessable();
    }

    public function test_short_lived_lease_expires_without_credentials_in_cache_indices(): void
    {
        $session = $this->open($this->token());
        $this->assertArrayNotHasKey('credential', $session);
        $index = Cache::get('ne.live.school.1');
        $this->assertSame([$session['lease_id']], array_keys($index));
        $this->travel(46)->seconds();
        $this->assertNull(app(LiveLeases::class)->find($session['lease_id']));
    }

    public function test_group_presence_is_explicit_count_only_and_freshly_scoped(): void
    {
        config(['modules.groups' => ['always_on' => true]]);
        $group = ActivityGroup::create(['session_year_id' => $this->schoolA->ids['session_year'], 'name' => 'Synthetic live group', 'discipline' => 'Sport', 'season' => '2026',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'gender' => 'any', 'active' => true, 'revision' => 1]);
        $user = $this->schoolA->ids['tara_user'];
        $staff = ActivityStaff::create(['activity_group_id' => $group->id, 'user_id' => $user, 'role' => 'facilitator', 'cascade' => false, 'active' => true,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic live assignment', 'revision' => 1]);
        $token = $this->token();
        $first = $this->open($token);
        $secondToken = $this->token();
        $second = $this->open($secondToken);
        $broadcaster = Mockery::mock(Broadcaster::class);
        $broadcaster->shouldReceive('broadcast')->andReturnNull();
        Broadcast::shouldReceive('connection')->with('reverb')->andReturn($broadcaster);
        DB::commit();
        try {
            $channel = LiveChannels::name(1, $user, $first['lease_id'], (int) $group->id);
            $this->api($token)->postJson('/api/v2/live/authorize', ['socket_id' => '123.456', 'channel_name' => $channel])->assertOk();
            $this->assertSame([], app(LiveLeases::class)->active((int) $group->id));
            $path = '/api/v2/live/groups/'.$group->id.'/presence';
            $this->api($token)->postJson($path, ['lease_id' => $first['lease_id']])->assertOk()->assertJsonPath('data.online_count', 1);
            $this->api($secondToken)->postJson($path, ['lease_id' => $second['lease_id']])->assertOk()->assertJsonPath('data.online_count', 1);
            $this->api($token)->deleteJson($path, ['lease_id' => $first['lease_id']])->assertOk()->assertJsonPath('data.online_count', 1);
            $staff->update(['active' => false]);
            $this->api($secondToken)->postJson($path, ['lease_id' => $second['lease_id']])->assertNotFound();
            $this->assertSame(0, app(LivePublisher::class)->presence((int) $group->id)['online_count']);
        } finally {
            DB::beginTransaction();
        }
    }

    public function test_accelerator_waits_for_real_outer_commit_and_outer_rollback_erases_work(): void
    {
        Queue::fake();
        DB::beginTransaction();
        $rolled = Outbox::record(new ConsultationCountsChanged(1, 123, 1));
        DB::rollBack();
        Queue::assertNothingPushed();
        $this->assertDatabaseMissing('domain_events', ['id' => $rolled->id]);
        DB::beginTransaction();
        $committed = Outbox::record(new ConsultationCountsChanged(1, 124, 1));
        DB::commit();
        Queue::assertNothingPushed(); // The fixture's real outer transaction is still open.
        DB::commit();
        Queue::assertPushed(DispatchLiveOutbox::class, fn ($job) => $job->eventId === (int) $committed->id && $job->queue === 'live' && $job->connection === 'database');
        DB::beginTransaction(); // Restore the fixture teardown transaction.
    }
}
