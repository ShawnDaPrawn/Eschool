<?php

declare(strict_types=1);

namespace App\Services\Panel;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * The admin panel's navigation by job, built from config/navigation.php.
 *
 * Items appear only when their route exists and the person has one of the
 * item's permissions (and role, where one is named). Each item names the module
 * switch Phase 1 will check; until then every module counts as on. The sidebar,
 * the global search's page results and page headers all read from here.
 */
class Navigation
{
    /**
     * Visible sections, each with its visible groups and items, for the sidebar.
     *
     * @return list<array{key:string, label:string, icon:string, active:bool, single:bool, url:?string, groups:list<array{key:string, label:string, items:list<array<string,mixed>>}>}>
     */
    public function sections(?User $user): array
    {
        $sections = [];
        foreach (config('navigation.sections', []) as $sectionKey => $section) {
            $groups = [];
            foreach ($section['groups'] ?? [] as $groupKey => $group) {
                $items = [];
                foreach ($group['items'] ?? [] as $item) {
                    if ($this->visible($item, $user)) {
                        $items[] = $this->resolve($item, (string) $sectionKey, (string) $groupKey);
                    }
                }
                if ($items !== []) {
                    $groups[] = ['key' => (string) $groupKey, 'label' => __($group['label']), 'items' => $items];
                }
            }
            if ($groups === []) {
                continue;
            }

            $items = array_merge(...array_column($groups, 'items'));
            $single = count($items) === 1 && count($groups) === 1 && $sectionKey === 'home';
            $sections[] = [
                'key' => (string) $sectionKey,
                'label' => __($section['label']),
                'icon' => $section['icon'] ?? 'circle',
                'active' => in_array(true, array_column($items, 'active'), true),
                'single' => $single,
                'url' => $single ? $items[0]['url'] : null,
                'groups' => $groups,
            ];
        }

        return $sections;
    }

    /**
     * Every visible item in one list, with its section and group labels.
     *
     * @return list<array<string, mixed>>
     */
    public function items(?User $user): array
    {
        $items = [];
        foreach ($this->sections($user) as $section) {
            foreach ($section['groups'] as $group) {
                foreach ($group['items'] as $item) {
                    $items[] = $item + ['section_label' => $section['label'], 'group_label' => $group['label']];
                }
            }
        }

        return $items;
    }

    /** The visible item for the current page, if any. */
    public function current(?User $user): ?array
    {
        foreach ($this->items($user) as $item) {
            if ($item['active']) {
                return $item;
            }
        }

        return null;
    }

    /**
     * The registry as written, without filtering (for tests and tooling).
     *
     * @return list<array<string, mixed>>
     */
    public function definitions(): array
    {
        $items = [];
        foreach (config('navigation.sections', []) as $sectionKey => $section) {
            foreach ($section['groups'] ?? [] as $groupKey => $group) {
                foreach ($group['items'] ?? [] as $item) {
                    $items[] = $item + ['section' => (string) $sectionKey, 'group' => (string) $groupKey];
                }
            }
        }

        return $items;
    }

    /** @param array<string, mixed> $item */
    public function visible(array $item, ?User $user): bool
    {
        if (! $user || ! $this->routeExists($item) || ! $this->moduleEnabled($item['module'] ?? null)) {
            return false;
        }
        if (! empty($item['roles']) && ! $user->hasAnyRole($item['roles'])) {
            return false;
        }
        $permissions = $item['permissions'] ?? [];

        return $permissions === [] || $user->canAny($permissions);
    }

    /**
     * Phase 1 switches modules per school (config/modules.php, school_modules).
     * Until then every module is on.
     */
    public function moduleEnabled(?string $module): bool
    {
        return true;
    }

    /** @param array<string, mixed> $item */
    public function url(array $item): string
    {
        return isset($item['route']) ? route($item['route']) : url($item['url']);
    }

    /** @param array<string, mixed> $item */
    public function isActive(array $item): bool
    {
        $request = request();
        $patterns = $item['active'] ?? null;
        if ($patterns === null) {
            $patterns = isset($item['route']) ? [$item['route']] : [$item['url'], $item['url'].'/*'];
        }
        foreach ($patterns as $pattern) {
            if ($request->routeIs($pattern) || $request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $item */
    private function routeExists(array $item): bool
    {
        if (isset($item['route'])) {
            return Route::has($item['route']);
        }
        if (! isset($item['url'])) {
            return false;
        }

        foreach (Route::getRoutes()->get('GET') as $route) {
            if ($route->uri() === ltrim($item['url'], '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function resolve(array $item, string $section, string $group): array
    {
        return [
            'key' => $item['key'],
            'label' => __($item['label']),
            'url' => $this->url($item),
            'active' => $this->isActive($item),
            'section' => $section,
            'group' => $group,
            'keywords' => $item['keywords'] ?? [],
            'module' => $item['module'] ?? null,
        ];
    }
}
