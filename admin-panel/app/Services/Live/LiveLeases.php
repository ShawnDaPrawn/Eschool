<?php

declare(strict_types=1);

namespace App\Services\Live;

use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

/** Short-lived encrypted credentials; indices contain opaque lease IDs only. */
class LiveLeases
{
    private function ttl(): int
    {
        return max(30, min(60, (int) config('live.presence_seconds', 45)));
    }

    private function key(string $lease): string
    {
        return 'ne.live.lease.'.$lease;
    }

    private function index(int $school, ?int $group = null): string
    {
        return 'ne.live.school.'.$school.($group === null ? '' : '.group.'.$group);
    }

    private function store(array $lease): void
    {
        Cache::put($this->key($lease['id']), Crypt::encryptString(json_encode($lease, JSON_THROW_ON_ERROR)), $this->ttl());
    }

    public function find(string $id): ?array
    {
        if (! Str::isUuid($id)) {
            return null;
        }
        $cipher = Cache::get($this->key($id));
        if (! is_string($cipher)) {
            return null;
        }
        try {
            $lease = json_decode(Crypt::decryptString($cipher), true, 32, JSON_THROW_ON_ERROR);
            if (! is_array($lease) || ($lease['id'] ?? null) !== $id || ! is_int($lease['school_id'] ?? null)
                || ! is_int($lease['user_id'] ?? null) || ! is_array($lease['credential'] ?? null)
                || ! is_int($lease['expires'] ?? null) || $lease['expires'] <= now()->getTimestamp()) {
                return null;
            }

            return $lease;
        } catch (Throwable) {
            return null;
        }
    }

    private function register(string $index, string $id): void
    {
        Cache::lock($index.'.lock', 5)->block(2, function () use ($index, $id) {
            $rows = Cache::get($index, []);
            $rows = is_array($rows) ? array_filter($rows, fn ($expires) => is_int($expires) && $expires > now()->getTimestamp()) : [];
            abort_if(! isset($rows[$id]) && count($rows) >= 10000, 503, 'Live updates are temporarily unavailable.');
            $rows[$id] = now()->getTimestamp() + $this->ttl();
            Cache::put($index, $rows, $this->ttl());
        });
    }

    public function issue(Request $request, User $actor): array
    {
        $school = app(CurrentSchool::class)->id();
        $credential = app(LiveCredentials::class)->current($request, $actor);
        $binding = app(LiveCredentials::class)->binding($credential, $school);
        $pointer = 'ne.live.binding.'.$binding;
        $id = Cache::get($pointer);
        $lease = is_string($id) ? $this->find($id) : null;
        if ($lease === null || ($lease['binding'] ?? null) !== $binding || $lease['school_id'] !== $school || $lease['user_id'] !== (int) $actor->id) {
            $lease = ['id' => (string) Str::uuid(), 'school_id' => $school, 'user_id' => (int) $actor->id, 'binding' => $binding, 'credential' => $credential];
        }
        $lease['expires'] = now()->getTimestamp() + $this->ttl();
        $this->store($lease);
        Cache::put($pointer, $lease['id'], $this->ttl());
        $this->register($this->index($school), $lease['id']);

        return $lease;
    }

    public function forRequest(Request $request, User $actor, string $id): array
    {
        $lease = $this->find($id);
        $school = app(CurrentSchool::class)->id();
        $credential = app(LiveCredentials::class)->current($request, $actor);
        $binding = app(LiveCredentials::class)->binding($credential, $school);
        abort_unless($lease !== null && $lease['school_id'] === $school && $lease['user_id'] === (int) $actor->id
            && is_string($lease['binding'] ?? null) && hash_equals($binding, $lease['binding']), 403);

        return $lease;
    }

    public function group(array $lease, int $group): void
    {
        abort_unless($lease['school_id'] === app(CurrentSchool::class)->id() && $group > 0, 403);
        $this->register($this->index($lease['school_id'], $group), $lease['id']);
    }

    public function leaveGroup(array $lease, int $group): void
    {
        $index = $this->index($lease['school_id'], $group);
        Cache::lock($index.'.lock', 5)->block(2, function () use ($index, $lease) {
            $rows = Cache::get($index, []);
            if (is_array($rows)) {
                unset($rows[$lease['id']]);
                Cache::put($index, $rows, $this->ttl());
            }
        });
    }

    /** @return list<array> */
    public function active(?int $group = null): array
    {
        $school = app(CurrentSchool::class)->id();
        $index = $this->index($school, $group);
        $rows = Cache::get($index, []);
        if (! is_array($rows)) {
            return [];
        }
        $leases = [];
        foreach ($rows as $id => $expires) {
            if (! is_string($id) || ! is_int($expires) || $expires <= now()->getTimestamp()) {
                continue;
            }
            $lease = $this->find($id);
            if ($lease !== null && $lease['school_id'] === $school) {
                $leases[] = $lease;
            }
        }

        return $leases;
    }

    public function actor(array $lease): ?User
    {
        if (($lease['school_id'] ?? null) !== app(CurrentSchool::class)->id()) {
            return null;
        }
        try {
            $user = app(LiveAuthority::class)->actor(User::findOrFail($lease['user_id']));

            return app(LiveCredentials::class)->valid($lease['credential'], $user) ? $user : null;
        } catch (Throwable) {
            return null;
        }
    }
}
