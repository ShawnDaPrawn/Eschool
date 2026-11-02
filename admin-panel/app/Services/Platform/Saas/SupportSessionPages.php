<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\StaffInvitation;
use App\Models\SessionYear;
use App\Models\Subject;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Database\Eloquent\Builder;

/**
 * What a support session shows (Action Plan SA.9): a fixed set of read-only
 * pages built to diagnose set-up and sign-in problems. It reads only the
 * granted school (the school scope does the rest) and never a restricted
 * record: no wellbeing, counselling, health, safeguarding, discipline, risk,
 * support plan, learner note or profile field is queried here, and the
 * authorities for those refuse support sessions as well.
 * engineering/specs/platform/support-access.md.
 */
class SupportSessionPages
{
    /** Account rows a search returns at most. */
    public const SEARCH_LIMIT = 25;

    /** Staff accounts the people page lists at most. */
    public const STAFF_LIMIT = 200;

    /** The request attribute that carries the ids a page listed to the session log. */
    public const LISTED = 'support_session.listed';

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /** The school's set-up: status, year, modules, structure and people counts. */
    public function overview(): array
    {
        $this->inSession();
        $school = $this->currentSchool->school();
        $gate = app(ModuleGate::class);
        $gate->forget();
        $modules = [];
        foreach ((array) config('modules') as $key => $definition) {
            $state = $gate->state((string) $key);
            $modules[] = ['name' => (string) ($definition['name'] ?? $key), 'state' => $state['enabled'] ? ($state['read_only'] ? 'read_only' : 'on') : 'off'];
        }
        $roles = SchoolMembership::query()->active()->where('school_id', $this->currentSchool->id())
            ->selectRaw('role, COUNT(DISTINCT user_id) AS people')->groupBy('role')->orderBy('role')->pluck('people', 'role')
            ->map(fn ($n) => (int) $n)->all();

        return [
            'school' => ['name' => (string) $school?->name, 'status' => (string) $school?->status, 'timezone' => (string) $school?->timezone, 'package' => $school?->package_key],
            'year' => SessionYear::query()->find((int) schoolSetting('session_year'))?->name,
            'roles' => $roles,
            'structure' => [
                'classes' => ClassSchool::query()->count(),
                'sections' => ClassSection::query()->count(),
                'subjects' => Subject::query()->count(),
                'invitations' => StaffInvitation::query()->whereNull('accepted_at')->whereNull('used_at')->where('expires_at', '>', now())->count(),
            ],
            'modules' => $modules,
        ];
    }

    /**
     * Staff accounts at the school (any role but Parent and Student) when
     * there is no search. With a search of at least three characters: staff
     * whose name or email contains it, and any account at the school whose
     * email is exactly it. A learner or parent is never found by a fragment.
     * The ids shown are handed to the session log (LISTED); the words are not.
     *
     * @return array{query: string, people: list<array<string, mixed>>, searched: bool}
     */
    public function people(?string $search): array
    {
        $this->inSession();
        $search = trim((string) $search);
        $searched = mb_strlen($search) >= 3;
        $schoolId = $this->currentSchool->id();
        $staff = fn (Builder $q) => $q->where('school_id', $schoolId)->whereNotIn('role', [SchoolRoles::PARENT, SchoolRoles::STUDENT]);
        $query = User::query()->withTrashed()->whereHas('memberships', fn (Builder $q) => $q->where('school_id', $schoolId))->orderBy('first_name')->orderBy('last_name');
        if ($searched) {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(fn (Builder $q) => $q
                ->where(fn (Builder $staffMatch) => $staffMatch->whereHas('memberships', $staff)
                    ->where(fn (Builder $words) => $words->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)->orWhere('email', 'like', $like)))
                ->orWhereRaw('LOWER(email) = ?', [mb_strtolower($search)]))
                ->limit(self::SEARCH_LIMIT);
        } else {
            $query->whereHas('memberships', $staff)->limit(self::STAFF_LIMIT);
        }
        $people = $query->get(['id', 'first_name', 'last_name', 'email', 'status', 'deleted_at']);
        $roles = SchoolMembership::query()->where('school_id', $schoolId)->whereIn('user_id', $people->pluck('id'))->get(['user_id', 'role', 'status'])->groupBy('user_id');
        request()->attributes->set(self::LISTED, $people->pluck('id')->map(fn ($id) => (int) $id)->values()->all());

        return [
            'query' => $searched ? $search : '',
            'searched' => $searched,
            'people' => $people->map(fn (User $user) => [
                'id' => (int) $user->id, 'name' => trim($user->first_name.' '.$user->last_name), 'email' => (string) $user->email,
                'roles' => ($roles[$user->id] ?? collect())->map(fn ($m) => $m->role.($m->status === SchoolMembership::ACTIVE ? '' : ' ('.$m->status.')'))->unique()->values()->all(),
                'account' => $user->trashed() ? 'deleted' : ((int) $user->status === 1 ? 'active' : 'disabled'),
            ])->values()->all(),
        ];
    }

    /**
     * One account at the school, for sign-in problems: account state, its
     * roles here with their dates, and its staff invitation here. No profile
     * field, record or message. Someone with no role at this school: 404.
     */
    public function person(int $id): array
    {
        $this->inSession();
        $schoolId = $this->currentSchool->id();
        $memberships = SchoolMembership::query()->where('school_id', $schoolId)->where('user_id', $id)->orderBy('role')->get();
        abort_if($memberships->isEmpty(), 404);
        $user = User::query()->withTrashed()->findOrFail($id);
        $invitation = StaffInvitation::query()->where('user_id', $id)->latest('id')->first();

        return [
            'id' => (int) $user->id, 'name' => trim($user->first_name.' '.$user->last_name), 'email' => (string) $user->email,
            'account' => $user->trashed() ? 'deleted' : ((int) $user->status === 1 ? 'active' : 'disabled'),
            'email_verified' => $user->email_verified_at !== null,
            'password_change_required' => (bool) $user->password_change_required,
            'memberships' => $memberships->map(fn (SchoolMembership $m) => [
                'role' => $m->role, 'status' => $m->status, 'starts_on' => $m->starts_on?->toDateString(), 'ends_on' => $m->ends_on?->toDateString(),
            ])->values()->all(),
            'invitation' => $invitation === null ? null : [
                'sent' => $invitation->created_at?->toDateString(),
                'state' => $invitation->accepted_at !== null ? 'accepted' : ($invitation->used_at !== null ? 'used' : ($invitation->expires_at?->isPast() ? 'expired' : 'waiting')),
            ],
        ];
    }

    /** Every page runs only inside an open support session for the current school. */
    private function inSession(): void
    {
        abort_unless($this->currentSchool->isSupportSession() && $this->currentSchool->isSet(), 403);
    }
}
