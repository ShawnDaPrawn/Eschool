<?php

declare(strict_types=1);

namespace App\Services\Platform\Modules;

use InvalidArgumentException;

/**
 * Describes school roles in modules and levels instead of 200 permission
 * names (Action Plan step 1.12). A translation layer over the existing
 * permissions in config/module_access.php: policies keep checking the same
 * names. Specification: engineering/specs/platform/module-access.md.
 */
class ModuleAccess
{
    public const NONE = 'none';

    public const VIEW = 'view';

    public const CONTRIBUTE = 'contribute';

    public const MANAGE = 'manage';

    private const ORDER = [self::NONE => 0, self::VIEW => 1, self::CONTRIBUTE => 2, self::MANAGE => 3];

    /** @return list<string> module keys that have access levels */
    public function modules(): array
    {
        return array_keys((array) config('module_access.modules'));
    }

    /**
     * Permission names a level grants in a module, including the lower levels.
     *
     * @return list<string>
     */
    public function permissionsFor(string $module, string $level): array
    {
        $definition = $this->definition($module);
        if (! array_key_exists($level, self::ORDER)) {
            throw new InvalidArgumentException("Unknown access level {$level}.");
        }
        $permissions = [];
        foreach ([self::VIEW, self::CONTRIBUTE, self::MANAGE] as $step) {
            if (self::ORDER[$step] > self::ORDER[$level]) {
                break;
            }
            $permissions = [...$permissions, ...($definition[$step] ?? [])];
        }

        return array_values(array_unique($permissions));
    }

    /**
     * The highest level whose permissions a set fully covers. A level with no
     * permissions of its own is reached only together with a higher one, so a
     * module whose view level is empty reads as none until contribute or
     * manage is held.
     *
     * @param  list<string>  $held
     */
    public function levelOf(string $module, array $held): string
    {
        $held = array_flip($held);
        $reached = self::NONE;
        foreach ([self::VIEW, self::CONTRIBUTE, self::MANAGE] as $level) {
            $needed = $this->permissionsFor($module, $level);
            if ($needed === [] || array_diff_key(array_flip($needed), $held) !== []) {
                if ($needed !== []) {
                    break;
                }

                continue;
            }
            $reached = $level;
        }

        return $reached;
    }

    /**
     * @return array<string, array{label: string, permissions: list<string>, sensitive: bool}>
     */
    public function abilities(string $module): array
    {
        return (array) ($this->definition($module)['abilities'] ?? []);
    }

    /**
     * The permissions a role should hold for chosen levels and abilities.
     *
     * @param  array<string, string>  $levels  module => level
     * @param  array<string, list<string>>  $abilities  module => ability keys
     * @return list<string>
     */
    public function permissionsForChoices(array $levels, array $abilities = []): array
    {
        $permissions = [];
        foreach ($levels as $module => $level) {
            $permissions = [...$permissions, ...$this->permissionsFor($module, $level)];
        }
        foreach ($abilities as $module => $keys) {
            $known = $this->abilities($module);
            foreach ($keys as $key) {
                if (! isset($known[$key])) {
                    throw new InvalidArgumentException("Unknown ability {$module}.{$key}.");
                }
                $permissions = [...$permissions, ...$known[$key]['permissions']];
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * Every permission the map knows, with the module that owns it.
     *
     * @return array<string, list<string>> permission => modules (one, when the map is sound)
     */
    public function owners(): array
    {
        $owners = [];
        foreach ($this->modules() as $module) {
            $definition = $this->definition($module);
            foreach ([self::VIEW, self::CONTRIBUTE, self::MANAGE] as $level) {
                foreach ($definition[$level] ?? [] as $permission) {
                    $owners[$permission][] = $module;
                }
            }
            foreach ($definition['abilities'] ?? [] as $ability) {
                foreach ($ability['permissions'] as $permission) {
                    $owners[$permission][] = $module;
                }
            }
        }

        return $owners;
    }

    /** @return array<string, mixed> */
    private function definition(string $module): array
    {
        $definition = config('module_access.modules.'.$module);
        if (! is_array($definition)) {
            throw new InvalidArgumentException("Module {$module} has no access levels.");
        }

        return $definition;
    }
}
