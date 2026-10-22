<?php

declare(strict_types=1);

namespace App\Services\Platform\Tenancy;

use App\Models\Platform\School;
use App\Services\Platform\RequestContext;
use App\Services\SettingsConfigService;
use Illuminate\Support\Facades\Context;
use Throwable;

/**
 * Which school the code is working for (the Build Bible, section 3.1,
 * Tenancy). One instance per request or job, from the container; every
 * school-owned query is limited to it by BelongsToSchool.
 *
 * Who sets it:
 * - a signed-in request: the school.context middleware, from the user's
 *   active memberships (an X-School-Id header on the API or the panel's
 *   school switcher picks among them);
 * - console commands and jobs: run($school, fn () => ...); a job queued in a
 *   school carries the school's id in its context and runs in it again;
 * - platform code that must see every school: acrossSchools(fn () => ...);
 * - audited impersonation (stream T4): impersonate($school).
 *
 * With nothing set, the scope never fails open:
 * - a signed-in request without a school (a route missing school.context)
 *   is refused: school-owned queries throw NoCurrentSchool;
 * - a request nobody is signed in to (public pages, sign-in, webhooks) works
 *   in the default school, config('tenancy.default_school_id'), which is
 *   school 1 on a single-school install; webhooks then switch to the school
 *   of the record they settle;
 * - console and queue work without a school read across schools and must
 *   pick a school before they write a school-owned row.
 */
class CurrentSchool
{
    /** The school of a single-school install, and every back-filled row. */
    public const DEFAULT_ID = 1;

    private ?int $id = null;

    private ?School $school = null;

    private bool $acrossSchools = false;

    private bool $impersonating = false;

    private bool $migrating = false;

    /**
     * The school whose settings are applied to config. SettingsConfigService
     * applies the default school's when the app boots (and builds this
     * object while doing so).
     */
    private ?int $settingsAppliedFor;

    public function __construct()
    {
        $this->settingsAppliedFor = self::defaultId();
    }

    /** Work for this school from now on (and use its roles and settings). */
    public function set(School|int $school): void
    {
        $this->id = $school instanceof School ? (int) $school->getKey() : $school;
        $this->school = $school instanceof School ? $school : null;
        $this->acrossSchools = false;
        $this->impersonating = false;
        $this->applied();
    }

    /** Forget the school: back to the rules above for "nothing set". */
    public function forget(): void
    {
        $this->id = null;
        $this->school = null;
        $this->acrossSchools = false;
        $this->impersonating = false;
        $this->applied();
    }

    /**
     * Platform staff reach a school only through audited impersonation
     * (stream T4 records the reason and the audit entry, then calls this).
     */
    public function impersonate(School $school): void
    {
        $this->set($school);
        $this->impersonating = true;
    }

    public function isImpersonating(): bool
    {
        return $this->impersonating;
    }

    /** No scope for the rest of this request or job: platform code that works across schools. */
    public function allowAcrossSchools(): void
    {
        $this->forget();
        $this->acrossSchools = true;
    }

    /**
     * Run the callback in a school, then put back whatever was set before.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(School|int $school, callable $callback): mixed
    {
        $before = $this->snapshot();
        $this->set($school);
        try {
            return $callback();
        } finally {
            $this->restore($before);
        }
    }

    /**
     * Run the callback in the school already set, or else in the default
     * school (the installer's seeders, which a single-school install runs
     * from the console).
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function runInSchoolOrDefault(callable $callback): mixed
    {
        return $this->isSet() ? $callback() : $this->run(self::defaultId(), $callback);
    }

    /**
     * Run the callback with no school scope (every school's rows), then put
     * back whatever was set before.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function acrossSchools(callable $callback): mixed
    {
        $before = $this->snapshot();
        $this->allowAcrossSchools();
        try {
            return $callback();
        } finally {
            $this->restore($before);
        }
    }

    /** Whether a school was set (not merely the default of a public request). */
    public function isSet(): bool
    {
        return $this->id !== null;
    }

    public function isAcrossSchools(): bool
    {
        return $this->acrossSchools;
    }

    /**
     * Migrations run across schools: old migrations use today's models on
     * tables that have no school_id yet. PlatformServiceProvider turns this
     * on and off around every migration run.
     */
    public function migrating(bool $migrating): void
    {
        $this->migrating = $migrating;
    }

    public function isMigrating(): bool
    {
        return $this->migrating;
    }

    /**
     * The school's id. Throws when there is none: a signed-in request without
     * school.context, or console or queue work that did not pick one.
     */
    public function id(): int
    {
        return $this->scopeId() ?? throw NoCurrentSchool::forWrite();
    }

    /** The school's id, or null when there is none (never throws). */
    public function idOrNull(): ?int
    {
        try {
            return $this->scopeId();
        } catch (NoCurrentSchool) {
            return null;
        }
    }

    /** The school, or null when there is none. */
    public function school(): ?School
    {
        $id = $this->idOrNull();
        if ($id === null) {
            return null;
        }
        if ($this->school?->getKey() !== $id) {
            $this->school = School::query()->find($id);
        }

        return $this->school;
    }

    /**
     * What BelongsToSchool limits queries to: the school, null for no limit
     * (across schools, or console and queue work without a school), and an
     * exception for a signed-in request without a school.
     */
    public function scopeId(): ?int
    {
        if ($this->id !== null) {
            return $this->id;
        }
        if ($this->acrossSchools || ! RequestContext::inHttpRequest()) {
            return null;
        }
        if ($this->signedIn()) {
            throw NoCurrentSchool::forSignedInRequest();
        }

        return self::defaultId();
    }

    /**
     * Whose settings getSettings() reads, beneath the platform's: the school;
     * none (platform rows only) for a signed-in request without a school;
     * the default school everywhere else (console, queue, public pages).
     */
    public function settingsSchoolId(): ?int
    {
        if ($this->id !== null) {
            return $this->id;
        }
        if (RequestContext::inHttpRequest() && ! $this->acrossSchools && $this->signedIn()) {
            return null;
        }

        return self::defaultId();
    }

    /** Where this school's files live on a disk: schools/{id}/{path}. */
    public function storagePath(string $path = ''): string
    {
        $path = trim($path, '/');

        return 'schools/'.($this->idOrNull() ?? self::defaultId()).($path === '' ? '' : '/'.$path);
    }

    public static function defaultId(): int
    {
        return (int) config('tenancy.default_school_id', self::DEFAULT_ID);
    }

    /** @return array{id: ?int, school: ?School, across: bool, impersonating: bool} */
    public function snapshot(): array
    {
        return ['id' => $this->id, 'school' => $this->school, 'across' => $this->acrossSchools, 'impersonating' => $this->impersonating];
    }

    /** @param  array{id: ?int, school: ?School, across: bool, impersonating: bool}  $snapshot */
    public function restore(array $snapshot): void
    {
        $this->id = $snapshot['id'];
        $this->school = $snapshot['school'];
        $this->acrossSchools = $snapshot['across'];
        $this->impersonating = $snapshot['impersonating'];
        $this->applied();
    }

    /**
     * Everything that follows the school: the log context and queued jobs
     * (Laravel's Context), Spatie's team, and the settings applied to config
     * (school name, timezone).
     */
    private function applied(): void
    {
        if ($this->id === null) {
            Context::forget('school_id');
        } else {
            Context::add('school_id', $this->id);
        }
        setPermissionsTeamId($this->id);

        $settingsFor = $this->settingsSchoolId();
        if ($settingsFor !== $this->settingsAppliedFor) {
            $this->settingsAppliedFor = $settingsFor;
            SettingsConfigService::apply();
        }
    }

    private function signedIn(): bool
    {
        try {
            return app()->resolved('auth') && app('auth')->guard()->hasUser();
        } catch (Throwable) {
            return false;
        }
    }
}
