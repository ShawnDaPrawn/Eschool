<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Platform\Role;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Saas\PlatformPackages;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolAwarePresenceVerifier;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Http\Request;
use Illuminate\Log\Context\Repository as ContextRepository;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;

/**
 * Wiring for the platform kernel (app/Services/Platform): tenancy, queued
 * delivery, mail webhooks, the outbox, the audit log and money. Settings are
 * in config/platform.php and config/tenancy.php.
 */
class PlatformServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One per request or job: the queue worker forgets scoped instances
        // between jobs, and ResetSchoolContext starts every request afresh.
        $this->app->scoped(CurrentSchool::class);
        $this->app->scoped(MembershipService::class);
        $this->app->scoped(ModuleGate::class);
        $this->app->scoped(PlatformPackages::class);
        $this->app->scoped(NotificationRules::class);

        // exists: and unique: rules see only the current school's rows.
        $this->app->extend('validation.presence', fn ($verifier, $app) => new SchoolAwarePresenceVerifier($app['db']));
    }

    public function boot(): void
    {
        Gate::define('profile.view', [ProfilePolicy::class, 'view']);
        Gate::define('profile.update', [ProfilePolicy::class, 'update']);
        Blade::if('module', fn (string $key) => app(ModuleGate::class)->allows($key));
        // SNS posts bounce, complaint and delivery notices from many addresses;
        // a burst after a bulk send stays well under this.
        RateLimiter::for('mail-webhook', fn (Request $request) => Limit::perMinute(600)->by('mail-webhook:'.$request->ip()));

        $this->bootTenancy();
    }

    private function bootTenancy(): void
    {
        // A job queued in a school runs in that school (CurrentSchool puts the
        // school's id in Laravel's Context, which every queued job carries);
        // a job queued without one runs without one.
        Context::hydrated(function (ContextRepository $context) {
            $schoolId = $context->get('school_id');
            $currentSchool = $this->app->make(CurrentSchool::class);
            is_numeric($schoolId) ? $currentSchool->set((int) $schoolId) : $currentSchool->forget();
        });

        // Migrations work across schools (old ones use today's models on
        // tables that have no school_id yet).
        $before = null;
        Event::listen(MigrationsStarted::class, function () use (&$before) {
            $currentSchool = $this->app->make(CurrentSchool::class);
            $before = $currentSchool->snapshot();
            $currentSchool->allowAcrossSchools();
            $currentSchool->migrating(true);
        });
        Event::listen(MigrationsEnded::class, function () use (&$before) {
            $currentSchool = $this->app->make(CurrentSchool::class);
            $currentSchool->migrating(false);
            if ($before !== null) {
                $currentSchool->restore($before);
            }
        });

        // Memberships follow role grants at the current school.
        Event::listen(RoleAttachedEvent::class, function (RoleAttachedEvent $event) {
            $user = $event->model;
            if (! $user instanceof User) {
                return;
            }
            $schoolId = $this->app->make(CurrentSchool::class)->id();
            $grant = fn () => $this->withRoleNames($event->rolesOrIds, fn (string $role) => $this->app->make(MembershipService::class)->grant($user, $schoolId, $role));
            if ($user->exists) {
                $grant();

                return;
            }
            // assignRole() on a user not saved yet: Spatie attaches the role
            // once it is saved, and so does this.
            $done = false;
            User::saved(function (User $saved) use ($user, $grant, &$done) {
                if (! $done && $saved === $user) {
                    $done = true;
                    $grant();
                }
            });
        });
        Event::listen(RoleDetachedEvent::class, function (RoleDetachedEvent $event) {
            if ($event->model instanceof User) {
                $this->withRoleNames($event->rolesOrIds, fn (string $role) => $this->app->make(MembershipService::class)->end($event->model, $this->app->make(CurrentSchool::class)->id(), $role));
            }
        });
    }

    /**
     * @param  callable(string): mixed  $callback
     */
    private function withRoleNames(mixed $rolesOrIds, callable $callback): void
    {
        $ids = collect(is_iterable($rolesOrIds) ? $rolesOrIds : [$rolesOrIds])
            ->map(fn ($role) => $role instanceof Role ? $role->getKey() : $role)
            ->all();
        foreach (Role::query()->whereIn('id', $ids)->pluck('name') as $name) {
            $callback((string) $name);
        }
    }
}
