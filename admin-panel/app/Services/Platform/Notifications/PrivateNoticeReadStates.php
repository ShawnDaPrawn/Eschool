<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Read-state bridges cannot widen a recipient, source, school or literal prefix. */
class PrivateNoticeReadStates
{
    private function providers(): array
    {
        $states = config('private_notices.read_states', []);
        $scopes = config('private_notices.scopes', []);
        if (! is_array($states) || ! is_array($scopes)) {
            throw new RuntimeException('Private notice state configuration must be a map.');
        }
        $providers = [];
        foreach ($states as $prefix => $class) {
            if (! is_string($prefix) || ! preg_match('/^[a-z]+(?:[._][a-z]+)*[._]?$/D', $prefix) || ! array_key_exists($prefix, $scopes) || ! is_string($class)) {
                throw new RuntimeException('A read-state bridge requires its registered literal private prefix.');
            }
            $provider = app($class);
            if (! $provider instanceof PrivateNoticeReadState) {
                throw new RuntimeException('Private notice read-state bridges must implement their contract.');
            }
            $providers[$prefix] = $provider;
        }

        return $providers;
    }

    private function scoped(callable $callback): mixed
    {
        $current = app(CurrentSchool::class);
        abort_unless($current->isSet() && ! $current->isAcrossSchools() && ! $current->isImpersonating(), 403);
        $school = $current->id();

        return $current->run($school, function () use ($callback, $school) {
            $result = $callback();
            $after = app(CurrentSchool::class);
            abort_unless($after->isSet() && $after->id() === $school && ! $after->isAcrossSchools() && ! $after->isImpersonating(), 403);

            return $result;
        });
    }

    public function applyUnread(Builder $notifications, User $actor): Builder
    {
        $type = $notifications->qualifyColumn('type');
        foreach ($this->providers() as $prefix => $provider) {
            $notifications->where(function (Builder $types) use ($prefix, $provider, $actor, $type) {
                $types->whereRaw("LEFT(COALESCE($type, ''), ?) <> ?", [strlen($prefix), $prefix])->orWhere(function (Builder $private) use ($prefix, $provider, $actor, $type) {
                    $private->whereRaw("LEFT(COALESCE($type, ''), ?) = ?", [strlen($prefix), $prefix])->where(function (Builder $source) use ($provider, $actor, $private) {
                        try {
                            $this->scoped(fn () => $provider->applyUnread($source, $actor));
                        } catch (HttpExceptionInterface|ModelNotFoundException) {
                            $private->whereRaw('1 = 0');
                        }
                    });
                });
            });
        }

        return $notifications;
    }

    /** @param array<int,array{type:string,payload:array}> $notices */
    public function markers(User $actor, array $notices): array
    {
        $markers = [];
        foreach ($this->providers() as $prefix => $provider) {
            $payloads = [];
            foreach ($notices as $id => $notice) {
                if (str_starts_with($notice['type'], $prefix)) {
                    $payloads[$id] = $notice['payload'];
                }
            }
            if ($payloads === []) {
                continue;
            }
            $result = $this->scoped(fn () => $provider->readStates($actor, $payloads));
            foreach ($result as $id => $instant) {
                if (! array_key_exists($id, $payloads) || ($instant !== null && (! is_string($instant) || strlen($instant) > 100))) {
                    throw new RuntimeException('A read-state bridge returned an unrelated marker.');
                }
                $markers[$id] = $instant;
            }
        }

        return $markers;
    }

    public function record(User $actor, int $id, string $type, array $payload): void
    {
        foreach ($this->providers() as $prefix => $provider) {
            if (str_starts_with($type, $prefix)) {
                $this->scoped(fn () => $provider->recordRead($actor, $id, $payload));
            }
        }
    }
}
