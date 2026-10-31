<?php

declare(strict_types=1);

namespace App\Services\Platform\Modules;

use App\Models\Platform\Role;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Panel\Navigation;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * "Who can see what" (Action Plan 1.15): for one person, or one role, the level
 * held in each module, the abilities (sensitive ones apart), any permissions
 * no module explains, and the panel pages they would see. For a person the
 * pages are worked out exactly as their own sidebar is (Navigation::visible),
 * appointments and allocations included; for a role, from its permissions
 * alone, with pages that also depend on an appointment marked as such.
 * Read-only. Specification: engineering/specs/platform/access-review.md.
 */
class AccessReview
{
    /** Item flags whose visibility also depends on an appointment, allocation or caseload. */
    private const ACTOR_FLAGS = ['vendor_payment_reader_actor', 'communication_author', 'cover_actor', 'discipline_actor', 'risk_actor', 'progress_actor',
        'support_actor', 'wellbeing_actor', 'principal_dashboard', 'transfer_actor', 'foundation_classroom_actor', 'card_actor', 'pathway_manager',
        'assistance_office', 'peer_actor', 'family_visit_actor', 'family_form_actor', 'gallery_actor', 'ai_manager', 'ai_reviewer', 'digest_manager',
        'consultation_actor', 'intake_actor', 'recognition_module'];

    public function __construct(private readonly ModuleAccess $access, private readonly Navigation $navigation) {}

    public function authorise(User $actor): void
    {
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_unless($fresh->hasRole('Super Admin') || (! $fresh->hasAnyRole(['Student', 'Parent']) && $fresh->can('role-list')), 403);
    }

    /** People and roles to choose from: current staff (not learners or parents) and every role of the school. */
    public function choices(User $actor): array
    {
        $this->authorise($actor);
        $staff = SchoolMembership::query()->active()->where('school_id', app(CurrentSchool::class)->id())->whereNotIn('role', ['Student', 'Parent'])
            ->distinct()->pluck('user_id');

        return [
            'people' => User::query()->whereIn('id', $staff)->where('status', 1)->orderBy('first_name')->orderBy('last_name')->limit(1000)->get()
                ->mapWithKeys(fn (User $user) => [(int) $user->id => trim($user->first_name.' '.$user->last_name)])->all(),
            'roles' => Role::query()->orderBy('name')->pluck('name', 'id')->map(fn ($name) => (string) $name)->all(),
        ];
    }

    public function person(User $actor, int $userId): array
    {
        $this->authorise($actor);
        $person = User::query()->findOrFail($userId);
        $roles = app(ProfilePolicy::class)->roles($person);
        abort_if($roles === [], 404);
        $held = $person->getAllPermissions()->pluck('name')->all();
        $pages = [];
        foreach ($this->navigation->definitions() as $item) {
            try {
                $visible = $this->navigation->visible($item, $person);
            } catch (Throwable) {
                $visible = false;
            }
            if ($visible) {
                $pages[] = ['label' => __($item['label']), 'section' => $item['section'], 'conditional' => false];
            }
        }

        return ['subject' => ['kind' => 'person', 'name' => trim($person->first_name.' '.$person->last_name), 'roles' => $roles]] + $this->summary($held) + ['pages' => $pages];
    }

    public function role(User $actor, int $roleId): array
    {
        $this->authorise($actor);
        $role = Role::query()->findOrFail($roleId);
        $held = $role->permissions()->pluck('name')->all();
        $pages = [];
        foreach ($this->navigation->definitions() as $item) {
            if (! $this->navigation->moduleEnabled($item['module'] ?? null) || (isset($item['route']) && ! Route::has($item['route']))) {
                continue;
            }
            if (! empty($item['roles']) && ! in_array($role->name, $item['roles'], true)) {
                continue;
            }
            $permissions = $item['permissions'] ?? [];
            if ($permissions !== [] && array_intersect($permissions, $held) === []) {
                continue;
            }
            $pages[] = ['label' => __($item['label']), 'section' => $item['section'],
                'conditional' => array_intersect(self::ACTOR_FLAGS, array_keys(array_filter($item, fn ($value) => $value !== false && $value !== null))) !== []];
        }

        return ['subject' => ['kind' => 'role', 'name' => $role->name, 'roles' => [$role->name]]] + $this->summary($held) + ['pages' => $pages];
    }

    /** Levels, abilities and unexplained permissions for a set of permission names. */
    private function summary(array $held): array
    {
        $gate = app(ModuleGate::class);
        $modules = [];
        $explained = [];
        foreach ($this->access->modules() as $key) {
            $level = $this->access->levelOf($key, $held);
            $explained = [...$explained, ...$this->access->permissionsFor($key, $level)];
            $abilities = [];
            foreach ($this->access->abilities($key) as $ability => $definition) {
                if (array_diff($definition['permissions'], $held) === []) {
                    $explained = [...$explained, ...$definition['permissions']];
                    $abilities[] = ['label' => __($definition['label']), 'sensitive' => (bool) $definition['sensitive']];
                }
            }
            $modules[$key] = ['name' => __((string) config('modules.'.$key.'.name', $key)), 'level' => $level, 'enabled' => $gate->allows($key), 'abilities' => $abilities];
        }
        $other = array_values(array_diff($held, $explained));
        sort($other);

        return ['modules' => $modules, 'other' => $other];
    }
}
