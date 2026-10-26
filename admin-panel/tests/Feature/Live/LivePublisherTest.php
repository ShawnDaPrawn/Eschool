<?php

declare(strict_types=1);

namespace Tests\Feature\Live;

use App\Models\Consultations\Consultation;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Live\LivePublisher;
use App\Services\Live\LiveSessions;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class LivePublisherTest extends TestCase
{
    use UsesTwoSchools;

    public function test_each_fanout_rechecks_source_and_individual_credential_before_metadata_delivery(): void
    {
        $this->setUpTwoSchools();
        config(['live.enabled' => true, 'modules.live_updates' => ['always_on' => true], 'modules.consultations' => ['always_on' => true],
            'broadcasting.connections.reverb.key' => 'local-live-key', 'broadcasting.connections.reverb.secret' => str_repeat('x', 32),
            'broadcasting.connections.reverb.app_id' => 'local-live', 'live.client.host' => '127.0.0.1', 'live.client.port' => 8085, 'live.client.scheme' => 'ws']);
        Cache::flush();
        $actor = User::findOrFail($this->schoolA->ids['tara_user']);
        SchoolMembership::where('user_id', $actor->id)->where('school_id', 1)->update(['starts_on' => '2026-01-01']);
        $leases = [];
        $tokens = [];
        foreach ([1, 2] as $_) {
            $token = $actor->createToken('teacher');
            $tokens[] = $token->plainTextToken;
            $actor->withAccessToken($token->accessToken);
            $request = Request::create('/api/v2/live/session', 'POST');
            $request->setUserResolver(fn () => $actor);
            $leases[] = app(LiveSessions::class)->open($request, $actor);
        }
        $year = $this->schoolA->ids['session_year'];
        $row = Consultation::create(['session_year_id' => $year, 'created_by' => $this->superAdmin()->id, 'type' => 'poll', 'title' => 'Private source title must not travel',
            'audience' => ['kind' => 'school', 'parents' => false, 'include_staff' => true, 'session_year_id' => $year, 'target_id' => null], 'anonymous' => true,
            'results_visibility' => 'public_live', 'status' => 'open', 'opens_at' => now()->utc()->subHour(), 'closes_at' => now()->utc()->addHour(), 'revision' => 1]);
        $event = OutboxEvent::create(['school_id' => 1, 'type' => 'consultations.counts_changed', 'payload' => ['consultation_id' => (int) $row->id, 'revision' => 1, 'title' => 'Untrusted extra source text'], 'occurred_at' => now(), 'attempts' => 0]);
        $sent = [];
        $broadcaster = Mockery::mock(Broadcaster::class);
        $broadcaster->shouldReceive('broadcast')->andReturnUsing(function ($channels, $name, $payload) use (&$sent): void {
            $sent[] = [$channels, $name, $payload];
        });
        Broadcast::shouldReceive('connection')->with('reverb')->andReturn($broadcaster);
        DB::commit();
        try {
            $publisher = app(LivePublisher::class);
            $publisher->event((int) $event->id);
            $this->assertCount(2, $sent);
            $this->assertSame([$leases[0]['channel']], $sent[0][0]);
            $this->assertSame([$leases[1]['channel']], $sent[1][0]);
            $this->assertSame('navigateed.invalidate', $sent[0][1]);
            $this->assertSame(['type' => 'consultations.counts_changed', 'school_id' => 1, 'consultation_id' => (int) $row->id, 'revision' => 1], $sent[0][2]);
            PersonalAccessToken::findToken($tokens[0])->delete();
            $sent = [];
            $publisher->event((int) $event->id);
            $this->assertCount(1, $sent);
            $this->assertSame([$leases[1]['channel']], $sent[0][0]);
            SchoolMembership::where('user_id', $actor->id)->where('school_id', 1)->update(['status' => 'ended']);
            $sent = [];
            $publisher->event((int) $event->id);
            $this->assertSame([], $sent);
        } finally {
            SchoolMembership::where('user_id', $actor->id)->where('school_id', 1)->update(['status' => 'active']);
            DB::beginTransaction();
        }
    }
}
