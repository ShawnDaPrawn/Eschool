<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SupportAccessGrant;
use App\Models\Platform\SupportAccessView;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Audited support access (Action Plan SA.9). A Navigate Ed staff member with
 * support.access asks one school for time-limited, read-only access with a
 * reason; the school's Super Admin approves or declines it in the school
 * panel, or an Owner records an emergency override that opens it at once and
 * tells the school's Super Admins. While the grant is open, the requester
 * alone opens the school in a support session (EnsureSupportSession), which
 * never uses a school membership or role, refuses every write and every
 * restricted record, and logs each page viewed (route name and record id,
 * never content) for the school and HQ to see. It ends by itself at its
 * expiry, or earlier when the school or the staff member ends it.
 * engineering/specs/platform/support-access.md.
 */
class SupportAccess
{
    /** HQ works in South African time; rows are stored in the app's timezone. */
    public const TIMEZONE = 'Africa/Johannesburg';

    public const REQUESTED = 'support_access.requested';

    public const EMERGENCY = 'support_access.emergency';

    /** Where the notices lead: the school panel's support access page. */
    public const LINK = '/support-access';

    public const VIEWS_PER_PAGE = 100;

    public const STATES = [
        'pending' => 'Waiting for the school', 'lapsed' => 'Lapsed without a decision', 'active' => 'Open',
        'expired' => 'Expired', 'declined' => 'Declined', 'withdrawn' => 'Withdrawn', 'ended' => 'Ended early',
    ];

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    public static function requestRules(): array
    {
        return [
            'school_id' => ['required', 'integer', Rule::exists('schools', 'id')->where('status', School::ACTIVE)],
            'reason' => ['required', 'string', 'min:20', 'max:500'],
            'minutes' => ['required', 'integer', Rule::in(self::durations())],
            'scope' => ['required', 'string', Rule::in(array_keys((array) config('support_access.scopes', [])))],
        ];
    }

    public static function emergencyRules(): array
    {
        return ['emergency_reason' => ['required', 'string', 'min:20', 'max:500']];
    }

    /** @return list<int> the lengths a request may ask for, none beyond the configured maximum */
    public static function durations(): array
    {
        $max = (int) config('support_access.max_minutes', 240);

        return array_values(array_filter(array_map('intval', (array) config('support_access.durations', [])), fn (int $m) => $m > 0 && $m <= $max));
    }

    // ---------------------------------------------------------------- HQ

    /** The signed-in platform staff member holding the ability, from fresh rows; 403 for anyone else. */
    public function staff(?User $user, string $ability = 'support.access'): User
    {
        $access = app(PlatformStaffAccess::class);
        abort_unless($user !== null && $access->can($user, $ability), 403);

        return $access->actor($user);
    }

    /** A request to one school: pending until its Super Admin decides; they are told now. */
    public function request(User $actor, array $input): SupportAccessGrant
    {
        $actor = $this->staff($actor);
        $data = Validator::make($input, self::requestRules())->validate();
        $schoolId = (int) $data['school_id'];
        $role = (string) app(PlatformStaffAccess::class)->role($actor);

        return $this->currentSchool->acrossSchools(fn (): SupportAccessGrant => DB::transaction(function () use ($actor, $data, $schoolId, $role): SupportAccessGrant {
            // One open request or session per person and school.
            $open = SupportAccessGrant::acrossSchools()->where('school_id', $schoolId)->where('requested_by', $actor->id)
                ->whereIn('status', [SupportAccessGrant::PENDING, SupportAccessGrant::ACTIVE])->lockForUpdate()->get()
                ->contains(fn (SupportAccessGrant $grant) => in_array($grant->state(), ['pending', 'active'], true));
            abort_if($open, 409, __('You already have an open request or session for this school.'));

            $grant = new SupportAccessGrant;
            $grant->forceFill([
                'school_id' => $schoolId, 'requested_by' => $actor->id, 'requester_role' => $role, 'reason' => $data['reason'],
                'scope' => $data['scope'], 'minutes' => (int) $data['minutes'], 'status' => SupportAccessGrant::PENDING, 'requested_at' => now(),
            ])->save();
            Audit::record('platform_support_access_requested', $grant, [], [
                'grant_id' => $grant->id, 'scope' => $grant->scope, 'minutes' => $grant->minutes, 'reason' => $grant->reason, 'changed_by' => $actor->id,
            ], $schoolId);
            $this->notify($schoolId, self::REQUESTED, __('Navigate Ed asks for support access'), __('Open Support access to approve or decline the request.'));

            return $grant;
        }, 3));
    }

    /**
     * An Owner's emergency override of a pending request: it opens at once,
     * for the length asked, with a mandatory reason, and the school's Super
     * Admins are told now. A declined, lapsed or withdrawn request cannot be
     * overridden: the school's refusal stands.
     */
    public function emergency(User $actor, int $grantId, array $input): SupportAccessGrant
    {
        $actor = $this->staff($actor, 'support.emergency');
        $data = Validator::make($input, self::emergencyRules())->validate();

        return $this->currentSchool->acrossSchools(fn (): SupportAccessGrant => DB::transaction(function () use ($actor, $grantId, $data): SupportAccessGrant {
            $grant = $this->lockAcross($grantId);
            abort_unless($grant->state() === 'pending', 409, __('Only a request still waiting for the school can be opened in an emergency.'));
            $this->open($grant, $actor, ['emergency' => true, 'emergency_reason' => $data['emergency_reason']]);
            Audit::record('platform_support_access_emergency', $grant, ['status' => SupportAccessGrant::PENDING], [
                'grant_id' => $grant->id, 'status' => SupportAccessGrant::ACTIVE, 'expires_at' => $grant->expires_at?->toIso8601String(),
                'requested_by' => $grant->requested_by, 'reason' => $grant->emergency_reason, 'changed_by' => $actor->id,
            ], $grant->school_id);
            $this->notify($grant->school_id, self::EMERGENCY, __('Navigate Ed opened emergency support access'), __('Open Support access to see who, why and until when, or to end it now.'));

            return $grant;
        }, 3));
    }

    /** The requester (or an Owner) withdraws a pending request or ends an open session. */
    public function endByStaff(User $actor, int $grantId): SupportAccessGrant
    {
        $actor = $this->staff($actor);
        $owner = app(PlatformStaffAccess::class)->can($actor, 'support.emergency');

        return $this->currentSchool->acrossSchools(fn (): SupportAccessGrant => DB::transaction(function () use ($actor, $grantId, $owner): SupportAccessGrant {
            $grant = $this->lockAcross($grantId);
            abort_unless($grant->requested_by === $actor->id || $owner, 403);

            return $this->close($grant, $actor, 'staff');
        }, 3));
    }

    /**
     * Requests still waiting and sessions still open, from everyone, then the
     * most recent others.
     *
     * @return array{open: list<array<string, mixed>>, recent: list<array<string, mixed>>}
     */
    public function hqList(User $actor): array
    {
        $this->staff($actor);

        return $this->currentSchool->acrossSchools(function (): array {
            $candidates = SupportAccessGrant::acrossSchools()->whereIn('status', [SupportAccessGrant::PENDING, SupportAccessGrant::ACTIVE])->orderBy('requested_at')->limit(500)->get();
            $open = $candidates->filter(fn (SupportAccessGrant $g) => in_array($g->state(), ['pending', 'active'], true));
            $recent = SupportAccessGrant::acrossSchools()->whereNotIn('id', $open->pluck('id'))->orderByDesc('requested_at')->orderByDesc('id')->limit(50)->get();
            $names = $this->names($open->merge($recent));

            return [
                'open' => $open->map(fn (SupportAccessGrant $g) => $this->present($g, $names, self::TIMEZONE))->values()->all(),
                'recent' => $recent->map(fn (SupportAccessGrant $g) => $this->present($g, $names, self::TIMEZONE))->values()->all(),
            ];
        });
    }

    /** One grant and the pages viewed under it, for HQ. */
    public function hqShow(User $actor, int $grantId, int $page = 1): array
    {
        $this->staff($actor);

        return $this->currentSchool->acrossSchools(function () use ($grantId, $page): array {
            $grant = SupportAccessGrant::acrossSchools()->find($grantId);
            abort_if($grant === null, 404);
            $views = SupportAccessView::acrossSchools()->where('grant_id', $grant->id);

            return $this->detail($grant, $views, $page, self::TIMEZONE, route('saas.support.show', $grant->id));
        });
    }

    /** @return array<int, string> active schools, for the request form */
    public function schoolChoices(): array
    {
        return $this->currentSchool->acrossSchools(fn (): array => School::query()->where('status', School::ACTIVE)->orderBy('name')->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => [(int) $id => (string) $name])->all());
    }

    // ----------------------------------------------------------- Session

    /**
     * The open grant a support session runs under, checked on fresh rows on
     * every request before the school is set: the requester alone, still
     * holding support.access, an open window, the read scope and an active
     * school. Writes nothing.
     */
    public function session(?User $user, int $grantId): SupportAccessGrant
    {
        $this->staff($user);

        return $this->currentSchool->acrossSchools(function () use ($user, $grantId): SupportAccessGrant {
            $grant = SupportAccessGrant::acrossSchools()->find($grantId);
            // Another person's grant is the same as no grant: nothing about it is revealed.
            abort_if($grant === null || $grant->requested_by !== (int) $user?->id, 404);
            abort_unless($grant->isOpen(), 403, __('This support session is not open: it was not approved, it was ended, or it expired.'));
            abort_unless($grant->scope === 'read', 403);
            abort_unless(School::query()->whereKey($grant->school_id)->where('status', School::ACTIVE)->exists(), 403);

            return $grant;
        });
    }

    /** A page viewed in the session: its route name and the record id from its route, never its content. */
    public function recordView(SupportAccessGrant $grant, User $user, string $route, ?string $recordType, ?int $recordId): void
    {
        abort_unless($this->currentSchool->supportGrantId() === $grant->id && $this->currentSchool->id() === $grant->school_id, 403);
        $view = new SupportAccessView;
        $view->forceFill([
            'school_id' => $grant->school_id, 'grant_id' => $grant->id, 'user_id' => $user->id, 'route_name' => mb_substr($route, 0, 191),
            'record_type' => $recordType === null ? null : mb_substr($recordType, 0, 32), 'record_id' => $recordId, 'viewed_at' => now(),
        ])->save();
        Audit::record('platform_support_page_viewed', null, [], [
            'grant_id' => $grant->id, 'route' => $view->route_name, 'record' => $view->record_type, 'record_id' => $recordId, 'changed_by' => $user->id,
        ], $grant->school_id);
    }

    // ------------------------------------------------------------ School

    /**
     * The school's Super Admin, from fresh rows, in their own school: never in
     * a support session, impersonation or across schools, and never a platform
     * staff account that also holds a school role.
     */
    public function schoolAdmin(?User $user): User
    {
        $school = $this->currentSchool;
        abort_unless($user !== null && $school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && ! $school->isSupportSession(), 403);
        abort_if(PlatformStaffIdentity::query()->where('user_id', $user->id)->where('active', true)->exists(), 403);
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($user);
        abort_if($fresh->password_change_required, 403);
        abort_unless(in_array(SchoolRoles::SUPER_ADMIN, $policy->roles($fresh), true), 403);

        return $fresh;
    }

    /**
     * This school's requests waiting for a decision, sessions open now and
     * the history, newest first.
     *
     * @return array{pending: list<array<string, mixed>>, open: list<array<string, mixed>>, history: list<array<string, mixed>>}
     */
    public function schoolList(User $actor): array
    {
        $this->schoolAdmin($actor);
        $grants = SupportAccessGrant::query()->orderByDesc('requested_at')->orderByDesc('id')->limit(200)->get();
        $names = $this->names($grants);
        $timezone = $this->schoolTimezone();
        $present = fn (SupportAccessGrant $g) => $this->present($g, $names, $timezone);

        return [
            'pending' => $grants->filter(fn (SupportAccessGrant $g) => $g->state() === 'pending')->map($present)->values()->all(),
            'open' => $grants->filter(fn (SupportAccessGrant $g) => $g->state() === 'active')->map($present)->values()->all(),
            'history' => $grants->filter(fn (SupportAccessGrant $g) => ! in_array($g->state(), ['pending', 'active'], true))->map($present)->values()->all(),
        ];
    }

    /** One of this school's grants and the pages viewed under it (another school's id: 404). */
    public function schoolShow(User $actor, int $grantId, int $page = 1): array
    {
        $this->schoolAdmin($actor);
        $grant = SupportAccessGrant::query()->find($grantId);
        abort_if($grant === null, 404);

        return $this->detail($grant, SupportAccessView::query()->where('grant_id', $grant->id), $page, $this->schoolTimezone(), route('support-access.show', $grant->id));
    }

    /** The school approves: the session window starts now, for the length asked. */
    public function approve(User $actor, int $grantId): SupportAccessGrant
    {
        $actor = $this->schoolAdmin($actor);

        return DB::transaction(function () use ($actor, $grantId): SupportAccessGrant {
            $grant = $this->lockOwn($grantId);
            abort_unless($grant->state() === 'pending', 409, __('This request is no longer waiting for a decision.'));
            $this->open($grant, $actor, []);
            Audit::record('platform_support_access_approved', $grant, ['status' => SupportAccessGrant::PENDING], [
                'grant_id' => $grant->id, 'status' => SupportAccessGrant::ACTIVE, 'expires_at' => $grant->expires_at?->toIso8601String(), 'changed_by' => $actor->id,
            ], $grant->school_id);

            return $grant;
        }, 3);
    }

    public function decline(User $actor, int $grantId): SupportAccessGrant
    {
        $actor = $this->schoolAdmin($actor);

        return DB::transaction(function () use ($actor, $grantId): SupportAccessGrant {
            $grant = $this->lockOwn($grantId);
            abort_unless($grant->state() === 'pending', 409, __('This request is no longer waiting for a decision.'));
            $grant->forceFill(['status' => SupportAccessGrant::DECLINED, 'decided_by' => $actor->id, 'decided_at' => now()])->save();
            Audit::record('platform_support_access_declined', $grant, ['status' => SupportAccessGrant::PENDING], [
                'grant_id' => $grant->id, 'status' => SupportAccessGrant::DECLINED, 'changed_by' => $actor->id,
            ], $grant->school_id);

            return $grant;
        }, 3);
    }

    /** The school ends an open session, or declines a pending request, now. */
    public function endBySchool(User $actor, int $grantId): SupportAccessGrant
    {
        $actor = $this->schoolAdmin($actor);

        return DB::transaction(fn (): SupportAccessGrant => $this->close($this->lockOwn($grantId), $actor, 'school'), 3);
    }

    // ----------------------------------------------------------- Helpers

    private function open(SupportAccessGrant $grant, User $actor, array $extra): void
    {
        $minutes = min($grant->minutes, (int) config('support_access.max_minutes', 240));
        $start = now();
        $grant->forceFill($extra + [
            'status' => SupportAccessGrant::ACTIVE, 'decided_by' => $actor->id, 'decided_at' => $start,
            'starts_at' => $start, 'expires_at' => $start->copy()->addMinutes($minutes),
        ])->save();
    }

    /** Pending: withdrawn (by staff) or declined (by the school). Open: ended. Anything else: 409. */
    private function close(SupportAccessGrant $grant, User $actor, string $side): SupportAccessGrant
    {
        $state = $grant->state();
        if ($state === 'pending') {
            $status = $side === 'staff' ? SupportAccessGrant::WITHDRAWN : SupportAccessGrant::DECLINED;
            $grant->forceFill($side === 'staff' ? ['status' => $status, 'ended_at' => now(), 'ended_by' => $actor->id, 'ended_side' => $side]
                : ['status' => $status, 'decided_by' => $actor->id, 'decided_at' => now()])->save();
        } elseif ($state === 'active') {
            $status = SupportAccessGrant::ENDED;
            $grant->forceFill(['status' => $status, 'ended_at' => now(), 'ended_by' => $actor->id, 'ended_side' => $side])->save();
        } else {
            abort(409, __('This request or session is already closed.'));
        }
        Audit::record($status === SupportAccessGrant::DECLINED ? 'platform_support_access_declined' : 'platform_support_access_ended', $grant,
            ['status' => $state], ['grant_id' => $grant->id, 'status' => $status, 'side' => $side, 'changed_by' => $actor->id], $grant->school_id);

        return $grant;
    }

    private function lockAcross(int $grantId): SupportAccessGrant
    {
        $grant = SupportAccessGrant::acrossSchools()->whereKey($grantId)->lockForUpdate()->first();
        abort_if($grant === null, 404);

        return $grant;
    }

    /** One of the current school's grants (the school scope makes another school's id a 404). */
    private function lockOwn(int $grantId): SupportAccessGrant
    {
        $grant = SupportAccessGrant::query()->whereKey($grantId)->lockForUpdate()->first();
        abort_if($grant === null, 404);

        return $grant;
    }

    /** The school's Super Admins are told in the school, as the school's own notices are. */
    private function notify(int $schoolId, string $type, string $title, string $body): void
    {
        $this->currentSchool->run($schoolId, function () use ($schoolId, $type, $title, $body): void {
            $admins = SchoolMembership::query()->active()->where('school_id', $schoolId)->where('role', SchoolRoles::SUPER_ADMIN)
                ->whereHas('user', fn ($user) => $user->where('status', 1))
                ->whereNotIn('user_id', PlatformStaffIdentity::query()->where('active', true)->select('user_id'))
                ->pluck('user_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
            if ($admins === []) {
                return;
            }
            $notifier = app(Notifier::class);
            $notifier->assign($notifier->makeNotice($type, $title, $body, self::LINK), $admins);
        });
    }

    private function detail(SupportAccessGrant $grant, mixed $views, int $page, string $timezone, string $path): array
    {
        /** @var LengthAwarePaginator $paginator */
        $paginator = $views->orderByDesc('viewed_at')->orderByDesc('id')->paginate(self::VIEWS_PER_PAGE, ['*'], 'page', max(1, $page))->withPath($path);
        $names = $this->names(collect([$grant]));

        return [
            'grant' => $this->present($grant, $names, $timezone) + [
                'reason' => $grant->reason, 'emergency_reason' => $grant->emergency_reason,
                'decided_by' => $names[$grant->decided_by] ?? null, 'ended_by' => $names[$grant->ended_by] ?? null, 'ended_side' => $grant->ended_side,
                'decided_at' => $this->local($grant->decided_at, $timezone), 'ended_at' => $this->local($grant->ended_at, $timezone),
            ],
            'views' => $paginator->through(fn (SupportAccessView $view) => [
                'at' => $this->local($view->viewed_at, $timezone, 'Y-m-d H:i:s'), 'page' => self::pageLabel($view->route_name),
                'route' => $view->route_name, 'record' => $view->record_type === null ? null : $view->record_type.' #'.$view->record_id,
            ]),
            'timezone' => $timezone,
        ];
    }

    /** @param  array<int, string>  $names */
    private function present(SupportAccessGrant $grant, array $names, string $timezone): array
    {
        return [
            'id' => (int) $grant->id, 'school_id' => (int) $grant->school_id, 'school' => $names['school:'.$grant->school_id] ?? '',
            'requester' => $names[$grant->requested_by] ?? __('Unknown'), 'requester_id' => (int) $grant->requested_by,
            'role' => (string) (config('platform_roles.'.$grant->requester_role.'.label') ?? $grant->requester_role),
            'reason' => $grant->reason, 'scope' => (string) (config('support_access.scopes.'.$grant->scope) ?? $grant->scope),
            'minutes' => (int) $grant->minutes, 'state' => $grant->state(), 'state_label' => __(self::STATES[$grant->state()] ?? $grant->state()),
            'emergency' => (bool) $grant->emergency,
            'requested_at' => $this->local($grant->requested_at, $timezone), 'starts_at' => $this->local($grant->starts_at, $timezone),
            'expires_at' => $this->local($grant->expires_at, $timezone),
            'views' => (int) SupportAccessView::acrossSchools()->where('grant_id', $grant->id)->count(),
        ];
    }

    /**
     * Names of the people and schools on these grants: staff by name, never
     * their email, to the school.
     *
     * @param  iterable<SupportAccessGrant>  $grants
     * @return array<int|string, string>
     */
    private function names(iterable $grants): array
    {
        $grants = collect($grants);
        $ids = $grants->flatMap(fn (SupportAccessGrant $g) => [$g->requested_by, $g->decided_by, $g->ended_by])->filter()->unique()->values();
        $names = User::acrossSchools()->withTrashed()->whereIn('id', $ids)->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (User $u) => [(int) $u->id => trim($u->first_name.' '.$u->last_name)])->all();
        foreach (School::query()->whereIn('id', $grants->pluck('school_id')->unique())->pluck('name', 'id') as $id => $name) {
            $names['school:'.$id] = (string) $name;
        }

        return $names;
    }

    /** A plain name for each support session page. */
    public static function pageLabel(string $route): string
    {
        return match ($route) {
            'support-session.show' => __('School overview'),
            'support-session.people' => __('People search'),
            'support-session.person' => __('One account'),
            default => $route,
        };
    }

    private function schoolTimezone(): string
    {
        return (string) ($this->currentSchool->school()?->timezone ?: self::TIMEZONE);
    }

    private function local(mixed $value, string $timezone, string $format = 'Y-m-d H:i'): ?string
    {
        return $value === null ? null : CarbonImmutable::parse($value)->setTimezone($timezone)->format($format);
    }
}
