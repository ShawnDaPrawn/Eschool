<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\Platform\School;
use App\Models\Platform\StaffInvitation;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Identity\SchoolJoinInvitations;
use App\Services\Platform\Identity\StaffInvitations;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Database\Seeders\EmailSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * What platform staff do with schools from /saas: see them, set one up with
 * its first administrator, choose its package, suspend or restore it, and
 * switch its modules. Every change is audited with the staff member and, for
 * a status change, the reason.
 *
 * Only school-level facts are read here (counts of memberships by role); no
 * learner, guardian or staff record of a school is ever listed or opened,
 * and the staff member gets no role in the school they manage.
 */
class PlatformSchools
{
    /** The roles whose active memberships are counted for each school. */
    public const COUNTED_ROLES = [SchoolRoles::STUDENT, SchoolRoles::PARENT, SchoolRoles::TEACHER];

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /**
     * Every school with its people counts.
     *
     * @return list<array<string, mixed>>
     */
    public function overview(): array
    {
        $counts = $this->counts();
        $stages = $this->stages();

        return DB::table('schools')->orderBy('id')->get()
            ->map(fn (object $school): array => $this->row($school, $counts, $stages))
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $school = DB::table('schools')->where('id', $id)->first();

        return $school === null ? null : $this->row($school, $this->counts($id), $this->stages($id));
    }

    /**
     * The school's modules as the school itself would see them.
     *
     * @return array<string, array<string, mixed>>
     */
    public function modules(int $schoolId): array
    {
        return $this->currentSchool->run($schoolId, function (): array {
            $gate = app(ModuleGate::class);
            $gate->forget();
            $modules = [];
            foreach (config('modules') as $key => $definition) {
                $modules[$key] = $definition + $gate->state($key);
            }

            return $modules;
        });
    }

    /** The package keys platform staff can assign, with the modules each includes. */
    public function packages(): array
    {
        return array_map(fn (array $modules): int => count($modules), (array) config('school_packages'));
    }

    /**
     * Set a school up: the school, its system roles and profile defaults, and
     * its first Super Admin, who is emailed a one-use link to choose their own
     * password (StaffInvitations). Their starting password is random and known
     * to nobody. The link is also returned, to show once if the email is late.
     *
     * When the administrator's address already has an account (a principal
     * moving schools), no second account is made: they are invited to join
     * (SchoolJoinInvitations), sign in with their own password and accept,
     * and only then become the school's Super Admin. An account that may not
     * be one is refused before anything is created.
     *
     * @param  array{name: string, slug: ?string, timezone: string, package_key: string, admin_first_name: string, admin_last_name: string, admin_email: string, admin_mobile: ?string}  $data
     * @return array{school: School, admin: User, invitation_url: string, existing_account: bool}
     */
    public function create(array $data, User $actor): array
    {
        return $this->currentSchool->acrossSchools(function () use ($data, $actor): array {
            return DB::transaction(function () use ($data, $actor): array {
                $joins = app(SchoolJoinInvitations::class);
                $existing = User::acrossSchools()->withTrashed()->where('email', strtolower(trim($data['admin_email'])))->lockForUpdate()->first();
                if ($existing !== null) {
                    // Refused before the school exists, so nothing is created.
                    $joins->assertMayBeFirstAdministrator($existing, $data['admin_email']);
                }
                $school = School::query()->create([
                    'name' => $data['name'],
                    'slug' => ($data['slug'] ?? null) ?: $this->uniqueSlug($data['name']),
                    'status' => School::ACTIVE,
                    'timezone' => $data['timezone'],
                    'package_key' => $data['package_key'],
                ]);
                app(SchoolRoles::class)->seed($school);
                $this->currentSchool->run($school, fn () => $this->firstRunDefaults($school));

                if ($existing !== null) {
                    // Nothing is attached to the account until its owner accepts.
                    $admin = $existing;
                    $typed = trim($data['admin_first_name'].' '.$data['admin_last_name']);
                    $invitation = $this->currentSchool->run($school, fn () => $joins->inviteFirstAdministrator($existing, $actor, $typed, $data['admin_email']));
                } else {
                    $admin = User::query()->create([
                        'first_name' => $data['admin_first_name'],
                        'last_name' => $data['admin_last_name'],
                        'email' => strtolower(trim($data['admin_email'])),
                        'mobile' => $data['admin_mobile'] ?? '',
                        'password' => Str::password(32),
                        'status' => 1,
                    ]);
                    $admin->forceFill(['password_change_required' => true])->save();
                    // The role grant creates the membership (MembershipService).
                    $this->currentSchool->run($school, fn () => $admin->assignRole(SchoolRoles::SUPER_ADMIN));
                    $invitation = $this->currentSchool->run($school, fn () => app(StaffInvitations::class)->issue($admin, $actor, StaffInvitation::SCHOOL_ADMIN));
                }

                Audit::record('platform_school_created', $school, [], [
                    'package_key' => $school->package_key,
                    'first_admin_user_id' => $admin->id,
                    'first_admin_existing_account' => $existing !== null,
                    'created_by' => $actor->id,
                ], (int) $school->id);

                return ['school' => $school, 'admin' => $admin, 'invitation_url' => $invitation['url'], 'existing_account' => $existing !== null];
            });
        });
    }

    public function changePackage(int $schoolId, string $package, User $actor): void
    {
        if (! array_key_exists($package, (array) config('school_packages'))) {
            throw ValidationException::withMessages(['package_key' => 'Choose one of the listed packages.']);
        }

        $this->currentSchool->acrossSchools(function () use ($schoolId, $package, $actor): void {
            DB::transaction(function () use ($schoolId, $package, $actor): void {
                $school = DB::table('schools')->where('id', $schoolId)->lockForUpdate()->first();
                abort_if($school === null, 404);
                if ($school->package_key === $package) {
                    return;
                }
                DB::table('schools')->where('id', $schoolId)->update(['package_key' => $package, 'updated_at' => now()]);
                Audit::record('platform_school_package_changed', School::query()->findOrFail($schoolId), ['package_key' => $school->package_key], ['package_key' => $package, 'changed_by' => $actor->id], $schoolId);
                // A money module the new package leaves out stays readable (read-only) while the school has its records.
                $this->currentSchool->run($schoolId, fn () => app(ModuleGate::class)->keepRetainedMoneyReadable($actor));
                // Entitlements follow the package, so the apps refresh their menus.
                Outbox::record(new ContextChanged($schoolId, 'modules'));
            });
        });
    }

    /**
     * Suspend, archive or restore a school. A school that is not active has no
     * active memberships (MembershipService), so its people are refused at the
     * next request; nothing is deleted. The install's default school cannot be
     * taken offline: public pages and sign-in run in it.
     */
    public function changeStatus(int $schoolId, string $status, string $reason, User $actor): void
    {
        if (! in_array($status, [School::ACTIVE, School::SUSPENDED, School::ARCHIVED], true)) {
            throw ValidationException::withMessages(['status' => 'Choose active, suspended or archived.']);
        }
        if ($status !== School::ACTIVE && $schoolId === CurrentSchool::defaultId()) {
            throw ValidationException::withMessages(['status' => 'The install\'s default school cannot be suspended or archived: public pages and sign-in run in it.']);
        }

        $this->currentSchool->acrossSchools(function () use ($schoolId, $status, $reason, $actor): void {
            DB::transaction(function () use ($schoolId, $status, $reason, $actor): void {
                $school = DB::table('schools')->where('id', $schoolId)->lockForUpdate()->first();
                abort_if($school === null, 404);
                if ($school->status === $status) {
                    return;
                }
                DB::table('schools')->where('id', $schoolId)->update(['status' => $status, 'updated_at' => now()]);
                Audit::record('platform_school_status_changed', School::query()->findOrFail($schoolId), ['status' => $school->status], ['status' => $status, 'reason' => $reason, 'changed_by' => $actor->id], $schoolId);
            });
        });
    }

    /** Switch one module of a school, with the same rules as the school's own admin. */
    public function changeModule(int $schoolId, string $module, bool $enabled, User $actor): void
    {
        abort_unless(DB::table('schools')->where('id', $schoolId)->exists(), 404);

        $this->currentSchool->run($schoolId, fn () => app(ModuleGate::class)->change($module, $enabled, $actor));
        Audit::record('platform_school_module_changed', School::query()->findOrFail($schoolId), [], ['module' => $module, 'enabled' => $enabled, 'changed_by' => $actor->id], $schoolId);
    }

    /**
     * What the school's own panel needs before anyone signs in, as the
     * installer does for the first school: its name and time zone as
     * settings, the e-mail templates, and a current school year. The year
     * is made current here directly: SessionYearService::makeCurrent() also
     * writes the year into the browser session, which here is the platform
     * staff member's, not the school's. Runs inside the school.
     */
    private function firstRunDefaults(School $school): void
    {
        Settings::query()->updateOrCreate(['type' => 'school_name'], ['message' => $school->name]);
        Settings::query()->updateOrCreate(['type' => 'time_zone'], ['message' => $school->timezone]);
        (new EmailSeeder)->run();

        $year = now($school->timezone)->year;
        $sessionYear = new SessionYear(['name' => (string) $year, 'start_date' => $year.'-01-01', 'end_date' => $year.'-12-31']);
        $sessionYear->default = 1;
        $sessionYear->save();
        Settings::query()->updateOrCreate(['type' => 'session_year'], ['message' => (string) $sessionYear->id]);
    }

    /**
     * Active people by role for one school or all, without reading any person.
     *
     * @return array<int, array{people: int, roles: array<string, int>}>
     */
    private function counts(?int $schoolId = null): array
    {
        $dates = DB::table('schools')->where('status', School::ACTIVE)->when($schoolId !== null, fn ($query) => $query->where('id', $schoolId))
            ->pluck('timezone', 'id')->map(fn ($timezone) => now($timezone)->toDateString());
        if ($dates->isEmpty()) {
            return [];
        }
        // Aggregate metadata only: ended/future memberships and disabled or
        // deleted accounts are not active people. Each school owns its day.
        $query = DB::table('school_memberships')->join('users', 'users.id', '=', 'school_memberships.user_id')
            ->where('school_memberships.status', 'active')->where('users.status', 1)->whereNull('users.deleted_at')
            ->where(function ($active) use ($dates): void {
                foreach ($dates as $id => $day) {
                    $active->orWhere(fn ($school) => $school->where('school_id', $id)
                        ->where(fn ($starts) => $starts->whereNull('starts_on')->orWhere('starts_on', '<=', $day))
                        ->where(fn ($ends) => $ends->whereNull('ends_on')->orWhere('ends_on', '>=', $day)));
                }
            });
        $perRole = (clone $query)->select('school_id', 'role', DB::raw('COUNT(DISTINCT user_id) AS people'))->groupBy('school_id', 'role')->get();
        $totals = (clone $query)->select('school_id', DB::raw('COUNT(DISTINCT user_id) AS people'))->groupBy('school_id')->pluck('people', 'school_id');

        $counts = [];
        foreach ($totals as $id => $people) {
            $counts[(int) $id] = ['people' => (int) $people, 'roles' => []];
        }
        foreach ($perRole as $row) {
            $counts[(int) $row->school_id]['roles'][(string) $row->role] = (int) $row->people;
        }

        return $counts;
    }

    /**
     * @param  array<int, array{people: int, roles: array<string, int>}>  $counts
     * @return array<string, mixed>
     */
    private function row(object $school, array $counts, array $stages = []): array
    {
        $own = $counts[(int) $school->id] ?? ['people' => 0, 'roles' => []];

        return [
            'id' => (int) $school->id,
            'name' => (string) $school->name,
            'slug' => (string) $school->slug,
            'status' => (string) $school->status,
            'package_key' => $school->package_key,
            'timezone' => (string) $school->timezone,
            'created_at' => $school->created_at,
            'people' => $own['people'],
            'roles' => array_merge(array_fill_keys(self::COUNTED_ROLES, 0), array_intersect_key($own['roles'], array_flip(self::COUNTED_ROLES))),
            'is_default' => (int) $school->id === CurrentSchool::defaultId(),
            // HQ's lifecycle stage (PlatformSchoolAccounts); a school without a record is onboarding.
            'stage' => $stages[(int) $school->id] ?? 'onboarding',
        ];
    }

    /** @return array<int, string> stage per school id */
    private function stages(?int $schoolId = null): array
    {
        return DB::table('school_accounts')->when($schoolId !== null, fn ($query) => $query->where('school_id', $schoolId))
            ->pluck('stage', 'school_id')->mapWithKeys(fn ($stage, $id) => [(int) $id => (string) $stage])->all();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::limit(Str::slug($name), 56, '') ?: 'school';
        $slug = $base;
        for ($n = 2; DB::table('schools')->where('slug', $slug)->exists(); $n++) {
            $slug = $base.'-'.$n;
        }

        return $slug;
    }
}
