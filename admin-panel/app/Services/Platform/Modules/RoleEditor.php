<?php

declare(strict_types=1);

namespace App\Services\Platform\Modules;

use App\Models\Platform\Permission;
use App\Models\Platform\Role;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

/**
 * The role editor (Action Plan 1.13): a custom role is described as a level
 * per module (none, view, contribute, manage) and named abilities, and saved as
 * the mapped permissions (ModuleAccess). Modules the school has switched off
 * are hidden and keep whatever the role already holds there. Permissions a
 * role holds that no level or ability explains (from the old checkbox editor)
 * are kept unless the editor is asked to drop them. Sensitive abilities need an
 * explicit confirmation and every grant or removal of one is audited.
 * Specification: engineering/specs/platform/role-editor.md.
 */
class RoleEditor
{
    /** Roles the application relies on; never edited here. */
    private const BUILT_IN = ['Super Admin', 'Teacher', 'Parent', 'Student'];

    public function __construct(private readonly ModuleAccess $access) {}

    /** Every permission a custom role may hold: the Super Admin set, less retired ones. */
    public function assignable(): array
    {
        $retired = (array) config('module_access.retired', []);

        return array_values(array_diff(SchoolRoles::PERMISSIONS[SchoolRoles::SUPER_ADMIN], $retired));
    }

    /**
     * The editor's view of a role (or of a new one): per visible module its
     * level and abilities, the sensitive abilities apart, and leftovers.
     */
    public function describe(?Role $role): array
    {
        $held = $role === null ? [] : $role->permissions()->pluck('name')->all();
        $gate = app(ModuleGate::class);
        $modules = [];
        $covered = [];
        foreach ($this->access->modules() as $key) {
            $level = $this->access->levelOf($key, $held);
            $covered = [...$covered, ...$this->access->permissionsFor($key, $level)];
            $abilities = [];
            foreach ($this->access->abilities($key) as $ability => $definition) {
                $has = array_diff($definition['permissions'], $held) === [];
                if ($has) {
                    $covered = [...$covered, ...$definition['permissions']];
                }
                $abilities[$ability] = ['label' => $definition['label'], 'sensitive' => (bool) $definition['sensitive'], 'held' => $has];
            }
            $modules[$key] = ['name' => (string) config('modules.'.$key.'.name', $key), 'level' => $level,
                'visible' => $gate->allows($key), 'abilities' => $abilities];
        }
        $extras = array_values(array_diff(array_intersect($held, $this->assignable()), $covered));
        sort($extras);

        return ['role' => $role === null ? null : ['id' => (int) $role->id, 'name' => $role->name], 'modules' => $modules, 'extras' => $extras];
    }

    /**
     * Creates or updates a custom role from levels and abilities.
     *
     * @param  array{name: string, levels?: array<string, string>, abilities?: array<string, list<string>>, keep_extras?: bool, confirm_sensitive?: bool}  $input
     */
    public function save(User $actor, ?Role $role, array $input): Role
    {
        abort_unless($actor->can($role === null ? 'role-create' : 'role-edit'), 403);
        abort_if($role !== null && ! $this->editable($role), 403);
        $modules = $this->access->modules();
        $data = Validator::make($input, [
            'name' => ['required', 'string', 'max:125', Rule::notIn(self::BUILT_IN),
                Rule::unique('roles', 'name')->where('school_id', app(CurrentSchool::class)->id())->ignore($role?->id)],
            'levels' => ['sometimes', 'array'], 'levels.*' => ['string', Rule::in([ModuleAccess::NONE, ModuleAccess::VIEW, ModuleAccess::CONTRIBUTE, ModuleAccess::MANAGE])],
            'abilities' => ['sometimes', 'array'], 'abilities.*' => ['array'], 'abilities.*.*' => ['string', 'max:64'],
            'keep_extras' => ['sometimes', 'boolean'], 'confirm_sensitive' => ['sometimes', 'boolean'],
        ])->validate();
        foreach (array_keys(($data['levels'] ?? []) + ($data['abilities'] ?? [])) as $module) {
            if (! in_array($module, $modules, true)) {
                throw ValidationException::withMessages(['levels' => __('Choose access for a known module.')]);
            }
        }

        return DB::transaction(function () use ($actor, $role, $data, $modules) {
            $before = $role === null ? $this->describe(null) : $this->describe($role);
            $gate = app(ModuleGate::class);
            $levels = [];
            $abilities = [];
            foreach ($modules as $module) {
                if (! $gate->allows($module)) {
                    // A module that is off is not shown, so the role keeps what it had there.
                    $levels[$module] = $before['modules'][$module]['level'];
                    $abilities[$module] = array_keys(array_filter($before['modules'][$module]['abilities'], fn ($a) => $a['held']));

                    continue;
                }
                $levels[$module] = $data['levels'][$module] ?? ModuleAccess::NONE;
                $abilities[$module] = array_values(array_unique($data['abilities'][$module] ?? []));
            }
            try {
                $permissions = $this->access->permissionsForChoices($levels, $abilities);
            } catch (\InvalidArgumentException) {
                throw ValidationException::withMessages(['abilities' => __('Choose a known ability.')]);
            }
            $sensitive = $this->sensitiveChanges($before, $abilities);
            if ($sensitive['granted'] !== [] && ! ($data['confirm_sensitive'] ?? false)) {
                throw ValidationException::withMessages(['confirm_sensitive' => __('Confirm that this role may open sensitive records before granting: :abilities', ['abilities' => implode(', ', $sensitive['granted'])])]);
            }
            if ($data['keep_extras'] ?? true) {
                $permissions = [...$permissions, ...$before['extras']];
            }
            $permissions = array_values(array_intersect(array_unique($permissions), $this->assignable()));

            $role ??= Role::query()->create(['name' => $data['name'], 'custom_role' => 1, 'guard_name' => 'web']);
            $oldNames = $role->permissions()->pluck('name')->sort()->values()->all();
            $role->name = $data['name'];
            $role->save();
            $role->syncPermissions(Permission::query()->whereIn('name', $permissions)->get());
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $newNames = $role->permissions()->pluck('name')->sort()->values()->all();
            Audit::record('role_access_saved', $role, ['permissions' => $oldNames], ['permissions' => $newNames, 'levels' => $levels]);
            foreach (['granted', 'removed'] as $change) {
                foreach ($sensitive[$change] as $ability) {
                    Audit::record('role_sensitive_ability_'.$change, $role, [], ['ability' => $ability, 'by' => $actor->id]);
                }
            }

            return $role;
        }, 3);
    }

    /** @return array<string, array{label: string, description: string}> */
    public function templates(): array
    {
        return array_map(fn ($template) => ['label' => $template['label'], 'description' => $template['description']], (array) config('role_templates'));
    }

    /** The editor for a new role started from a template: its levels and abilities ticked, nothing saved. */
    public function fromTemplate(string $key): array
    {
        $template = config('role_templates.'.$key);
        abort_unless(is_array($template), 404);
        $editor = $this->describe(null);
        foreach ($template['levels'] as $module => $level) {
            if (isset($editor['modules'][$module])) {
                $editor['modules'][$module]['level'] = $level;
            }
        }
        foreach ($template['abilities'] as $module => $keys) {
            foreach ($keys as $ability) {
                if (isset($editor['modules'][$module]['abilities'][$ability])) {
                    $editor['modules'][$module]['abilities'][$ability]['held'] = true;
                }
            }
        }
        $editor['template'] = ['key' => $key, 'label' => $template['label']];

        return $editor;
    }

    public function editable(Role $role): bool
    {
        return (int) $role->custom_role === 1 && ! in_array($role->name, self::BUILT_IN, true);
    }

    /** @return array{granted: list<string>, removed: list<string>} module.ability keys */
    private function sensitiveChanges(array $before, array $abilities): array
    {
        $changes = ['granted' => [], 'removed' => []];
        foreach ($before['modules'] as $module => $state) {
            foreach ($state['abilities'] as $key => $ability) {
                if (! $ability['sensitive']) {
                    continue;
                }
                $now = in_array($key, $abilities[$module] ?? [], true);
                if ($now && ! $ability['held']) {
                    $changes['granted'][] = $module.'.'.$key;
                } elseif (! $now && $ability['held']) {
                    $changes['removed'][] = $module.'.'.$key;
                }
            }
        }

        return $changes;
    }
}
