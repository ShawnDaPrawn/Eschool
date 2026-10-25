<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Registered private types are filtered in SQL before pagination/count/body load. */
class PrivateNoticeScopes
{
    /** Existing domain guards remain authoritative for registrations without this extension. */
    public function permitsDelivery(User $actor, string $type, array $payload): bool
    {
        $providers = config('private_notices.scopes', []);
        if (! is_array($providers)) {
            throw new RuntimeException('Private notice scope configuration must be a map.');
        }
        foreach ($providers as $prefix => $class) {
            if (! is_string($prefix) || preg_match('/^[a-z]+(?:[._][a-z]+)*[._]?$/D', $prefix) !== 1 || ! is_string($class)) {
                throw new RuntimeException('Private notice scope needs a literal dotted prefix and provider class.');
            }
            if (! str_starts_with($type, $prefix)) {
                continue;
            }
            $school = app(CurrentSchool::class);
            if (! $school->isSet() || $school->isAcrossSchools() || $school->isImpersonating() || ! School::active()->whereKey($school->id())->exists()) {
                return false;
            }
            $schoolId = $school->id();
            try {
                $fresh = app(ProfilePolicy::class)->actor(User::findOrFail($actor->id));
                if ($fresh->password_change_required || PlatformStaffIdentity::where('user_id', $fresh->id)->where('active', true)->exists()) {
                    return false;
                }
                $allowed = $school->run($schoolId, function () use ($class, $fresh, $type, $payload, $schoolId) {
                    $provider = app($class);
                    if (! $provider instanceof PrivateNoticeScope) {
                        throw new RuntimeException('Private notice providers must implement PrivateNoticeScope.');
                    }
                    $allowed = ! $provider instanceof PrivateNoticeDelivery || $provider->allows($fresh, $type, $payload);
                    $current = app(CurrentSchool::class);

                    return $allowed && $current->isSet() && $current->id() === $schoolId && ! $current->isAcrossSchools() && ! $current->isImpersonating();
                });
                if (! $allowed) {
                    return false;
                }
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                return false;
            }
        }

        return true;
    }

    public function apply(Builder $notifications, int $actorId): Builder
    {
        $providers = config('private_notices.scopes', []);
        if ($providers === []) {
            return $notifications;
        }
        if (! is_array($providers)) {
            throw new RuntimeException('Private notice scope configuration must be a map.');
        }
        $school = app(CurrentSchool::class);
        $schoolId = $school->isSet() ? $school->id() : null;
        $actor = null;
        if ($schoolId !== null && ! $school->isAcrossSchools() && ! $school->isImpersonating() && School::active()->whereKey($schoolId)->exists()) {
            try {
                $actor = app(ProfilePolicy::class)->actor(User::findOrFail($actorId));
                if ($actor->password_change_required || PlatformStaffIdentity::where('user_id', $actorId)->where('active', true)->exists()) {
                    $actor = null;
                }
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                $actor = null;
            }
            // A later context change cannot turn this constructed query into a
            // different school's inbox when tenant global scopes are applied.
            $notifications->where($notifications->qualifyColumn('school_id'), $schoolId);
        }
        foreach ($providers as $prefix => $class) {
            if (! is_string($prefix) || preg_match('/^[a-z]+(?:[._][a-z]+)*[._]?$/D', $prefix) !== 1 || ! is_string($class)) {
                throw new RuntimeException('Private notice scope needs a literal dotted prefix and provider class.');
            }
            $provider = app($class);
            if (! $provider instanceof PrivateNoticeScope) {
                throw new RuntimeException('Private notice providers must implement PrivateNoticeScope.');
            }
            $type = $notifications->qualifyColumn('type');
            $notifications->where(function (Builder $types) use ($prefix, $provider, $actor, $schoolId, $type) {
                $types->whereRaw("LEFT(COALESCE($type, ''), ?) <> ?", [strlen($prefix), $prefix])->orWhere(function (Builder $private) use ($prefix, $provider, $actor, $schoolId, $type) {
                    // Keep type/recipient/school guards OUTSIDE the provider's
                    // nested clause: its OR predicates cannot widen siblings.
                    $private->whereRaw("LEFT(COALESCE($type, ''), ?) = ?", [strlen($prefix), $prefix])->where(function (Builder $source) use ($provider, $actor, $schoolId, $private) {
                        if ($actor === null || $schoolId === null) {
                            $source->whereRaw('1 = 0');

                            return;
                        }
                        try {
                            app(CurrentSchool::class)->run($schoolId, function () use ($provider, $actor, $source, $schoolId) {
                                $provider->apply($source, $actor);
                                $current = app(CurrentSchool::class);
                                abort_unless($current->isSet() && $current->id() === $schoolId && ! $current->isAcrossSchools() && ! $current->isImpersonating(), 403);
                            });
                        } catch (HttpExceptionInterface|ModelNotFoundException) {
                            $private->whereRaw('1 = 0');
                        }
                    });
                });
            });
        }

        return $notifications;
    }
}
