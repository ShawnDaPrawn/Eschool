<?php

declare(strict_types=1);

namespace App\Services\Live;

use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;

class LiveSessions
{
    public function open(Request $request, User $actor): array
    {
        $fresh = app(LiveAuthority::class)->actor($actor);
        abort_unless(app(LiveConfiguration::class)->ready(), 503, 'Live updates are temporarily unavailable.');
        $lease = app(LiveLeases::class)->issue($request, $fresh);

        return ['lease_id' => $lease['id'], 'channel' => LiveChannels::name($lease['school_id'], (int) $fresh->id, $lease['id']),
            'key' => config('broadcasting.connections.reverb.key'), 'host' => config('live.client.host'), 'port' => config('live.client.port'),
            'scheme' => config('live.client.scheme'), 'heartbeat_seconds' => (int) config('live.heartbeat_seconds', 20), 'expires_in' => max(0, $lease['expires'] - now()->getTimestamp())];
    }

    public function authorize(Request $request, User $actor, string $socket, string $channel): array
    {
        $fresh = app(LiveAuthority::class)->actor($actor);
        abort_unless(app(LiveConfiguration::class)->ready(), 503);
        abort_unless(preg_match('/^[0-9]{1,20}[.][0-9]{1,20}$/D', $socket) === 1, 422);
        $parsed = LiveChannels::parse($channel);
        abort_unless($parsed['school'] === app(CurrentSchool::class)->id() && $parsed['user'] === (int) $fresh->id, 403);
        $lease = app(LiveLeases::class)->forRequest($request, $fresh, $parsed['lease']);
        if ($parsed['group'] !== null) {
            app(LiveAuthority::class)->group($fresh, $parsed['group']);
            // Presence is a separate explicit heartbeat, not a subscription side effect.
        }

        return ['auth' => config('broadcasting.connections.reverb.key').':'.hash_hmac('sha256', $socket.':'.$channel, (string) config('broadcasting.connections.reverb.secret'))];
    }

    public function presence(Request $request, User $actor, string $id, int $group, bool $leave): array
    {
        $fresh = app(LiveAuthority::class)->actor($actor);
        app(LiveAuthority::class)->group($fresh, $group);
        $leases = app(LiveLeases::class);
        $lease = $leases->forRequest($request, $fresh, $id);
        if ($leave) {
            $leases->leaveGroup($lease, $group);
        } else {
            $leases->group($lease, $group);
        }

        return app(LivePublisher::class)->presence($group);
    }
}
