<?php

declare(strict_types=1);

namespace App\Services\Panel;

use App\Models\User;
use App\Services\Admissions\IntakeAuthority;
use App\Services\Cards\CardPanelScope;
use App\Services\Communication\CommunicationRules;
use App\Services\Consultations\ConsultationAccess;
use App\Services\Discipline\DisciplineQueries;
use App\Services\Gallery\GalleryPanelScope;
use App\Services\Growth\ProgressQueries;
use App\Services\Growth\RiskQueries;
use App\Services\Leadership\LeadershipAuthority;
use App\Services\Learning\Digests\DigestPanelScope;
use App\Services\Pastoral\CoverAuthority;
use App\Services\Pastoral\SupportAuthority;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Recognition\RecognitionAuthority;
use App\Services\Transfers\TransferAuthority;
use App\Services\Wellbeing\WellbeingAccess;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The admin panel's navigation by job, built from config/navigation.php.
 *
 * Items appear only when their route exists and the person has one of the
 * item's permissions (and role, where one is named). Each item names the module
 * switch enforced by ModuleGate. The sidebar,
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
        if (($item['communication_author'] ?? false) && ! app(CommunicationRules::class)->authorAvailable($user)) {
            return false;
        }
        if (($item['cover_actor'] ?? false) && ! app(CoverAuthority::class)->available($user, (int) schoolSetting('session_year'))) {
            return false;
        }
        if (($item['discipline_actor'] ?? false) && ! app(DisciplineQueries::class)->available($user)) {
            return false;
        }
        if (($item['risk_actor'] ?? false) && ! app(RiskQueries::class)->available($user)) {
            return false;
        }
        if (($item['progress_actor'] ?? false) && ! app(ProgressQueries::class)->available($user)) {
            return false;
        }
        if (($item['support_actor'] ?? false) && ! app(SupportAuthority::class)->available($user)) {
            return false;
        }
        if (($item['wellbeing_actor'] ?? false) && ! app(WellbeingAccess::class)->panelAvailable($user)) {
            return false;
        }
        if (($item['principal_dashboard'] ?? false) && ! app(LeadershipAuthority::class)->available($user)) {
            return false;
        }
        if (($item['transfer_actor'] ?? false) && ! app(TransferAuthority::class)->available($user)) {
            return false;
        }
        if (($item['card_actor'] ?? false) && ! app(CardPanelScope::class)->available($user, (bool) ($item['card_readers'] ?? false))) {
            return false;
        }
        if (($item['gallery_actor'] ?? false) && ! app(GalleryPanelScope::class)->available($user)) {
            return false;
        }
        if (($item['digest_manager'] ?? false) && ! app(DigestPanelScope::class)->available($user)) {
            return false;
        }
        if ($item['consultation_actor'] ?? false) {
            try {
                app(ConsultationAccess::class)->actor($user);
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                return false;
            }
        }
        if ($item['intake_actor'] ?? false) {
            try {
                app(IntakeAuthority::class)->staff($user);
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                return false;
            }
        }
        if (isset($item['recognition_module'])) {
            try {
                app(RecognitionAuthority::class)->staff($user, $item['recognition_module']);
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                return false;
            }
        }
        $permissions = $item['permissions'] ?? [];

        return $permissions === [] || $user->canAny($permissions);
    }

    /**
     * School-specific module availability, also used by routes and app context.
     */
    public function moduleEnabled(?string $module): bool
    {
        return $module === null || app(ModuleGate::class)->allows($module);
    }

    /** @param array<string, mixed> $item */
    public function url(array $item): string
    {
        return isset($item['route']) ? route($item['route'], $item['params'] ?? []) : url($item['url']);
    }

    /** @param array<string, mixed> $item */
    public function isActive(array $item): bool
    {
        $request = request();
        if (isset($item['recognition_module']) && $request->route('module') !== $item['recognition_module']) {
            return false;
        }
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
