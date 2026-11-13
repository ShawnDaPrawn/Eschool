<?php

declare(strict_types=1);

namespace App\Services\Panel;

use App\Http\Middleware\FoundationStaffContext;
use App\Models\User;
use App\Services\Admissions\IntakeAuthority;
use App\Services\Ai\Operations\AiSchoolControls;
use App\Services\Ai\Quarantine\AiReviewAuthority;
use App\Services\Assistance\AssistanceQueries;
use App\Services\Cards\CardPanelScope;
use App\Services\Commerce\VendorPaymentReaderAccess;
use App\Services\Communication\CommunicationRules;
use App\Services\Consultations\ConsultationAccess;
use App\Services\Discipline\DisciplineQueries;
use App\Services\EarlyYears\EarlyYearsAuthority;
use App\Services\Emergency\EmergencyAccess;
use App\Services\FamilyForms\FamilyPanelScope;
use App\Services\FamilyVisits\VisitPanelScope;
use App\Services\Gallery\GalleryPanelScope;
use App\Services\Growth\ProgressQueries;
use App\Services\Growth\RiskQueries;
use App\Services\Leadership\LeadershipAuthority;
use App\Services\Leadership\MasteryImpact;
use App\Services\Learning\Digests\DigestPanelScope;
use App\Services\Learning\ExamCoachAccess;
use App\Services\Leave\FacilitatorLeave;
use App\Services\Passport\PassportAuthority;
use App\Services\Pastoral\CoverAuthority;
use App\Services\Pastoral\SupportAuthority;
use App\Services\Pathways\CareerGuidance;
use App\Services\Pathways\PathwayPanelScope;
use App\Services\PeerTutoring\PeerPanelScope;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Offboarding\SchoolExit;
use App\Services\Platform\SignIn\ProviderLinks;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Reading\ReadingAccess;
use App\Services\Recognition\RecognitionAuthority;
use App\Services\Transfers\TransferAuthority;
use App\Services\Wellbeing\CheckIns;
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
        $renderKey = $this->renderKey($user);
        $rendered = request()->attributes->get('panel.navigation.rendered');
        if ($renderKey !== null && is_array($rendered) && ($rendered['key'] ?? null) === $renderKey) {
            return $rendered['sections'];
        }

        $sections = [];
        $checks = [];
        foreach (config('navigation.sections', []) as $sectionKey => $section) {
            $groups = [];
            foreach ($section['groups'] ?? [] as $groupKey => $group) {
                $items = [];
                foreach ($group['items'] ?? [] as $item) {
                    if ($renderKey === null ? $this->visible($item, $user) : $this->renderVisible($item, $user, $checks)) {
                        $items[] = $this->resolve($item, (string) $sectionKey, (string) $groupKey);
                    }
                }
                if ($items !== []) {
                    $groups[] = [
                        'key' => (string) $groupKey, 'label' => __($group['label']), 'items' => $items,
                        'url' => $this->groupDestination((string) $sectionKey, (string) $groupKey, $items),
                        'active' => in_array(true, array_column($items, 'active'), true)
                            || (request()->routeIs('panel.workspace') && request()->route('section') === $sectionKey && request()->route('group') === $groupKey)
                            || (request()->routeIs('student.report') && $this->reportOwner()[0] === $sectionKey && $this->reportOwner()[1] === $groupKey),
                    ];
                }
            }
            if ($groups === []) {
                continue;
            }

            $items = array_merge(...array_column($groups, 'items'));
            $single = ! in_array($sectionKey, ['people', 'community'], true);
            $sections[] = [
                'key' => (string) $sectionKey,
                'label' => __($section['label']),
                'icon' => $section['icon'] ?? 'circle',
                'active' => in_array(true, array_column($groups, 'active'), true)
                    || (request()->routeIs('panel.workspace') && request()->route('section') === $sectionKey),
                'single' => $single,
                'url' => $sectionKey === 'home' ? route('home') : $this->workspaceUrl((string) $sectionKey),
                'groups' => $groups,
            ];
        }

        if ($renderKey !== null) {
            request()->attributes->set('panel.navigation.rendered', ['key' => $renderKey, 'sections' => $sections]);
        }

        return $sections;
    }

    /**
     * Reuse one document's menu across its workspace, sidebar and breadcrumbs.
     * This lives only on the current GET Request, never in a session or shared
     * cache. Endpoint authority and explicit visible() checks still run fresh.
     * Other-person previews and write requests must not reuse rendered menus.
     */
    private function renderKey(?User $user): ?string
    {
        $request = request();
        if (! $user || ! $request->isMethod('GET') || $request->user() !== $user) {
            return null;
        }
        $school = app(CurrentSchool::class)->snapshot();

        return hash('sha256', serialize([
            $user->getKey(), spl_object_id($user),
            $school['id'], $school['across'], $school['impersonating'], $school['support'] ?? null,
            $request->hasSession() ? $request->session()->get('session_year') : null,
            app()->getLocale(), $request->fullUrl(), $request->query(), $request->route()?->getName(),
            config('navigation.sections', []),
        ]));
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

    private function reportOwner(): array
    {
        return match (request()->query('tab', 'personal')) {
            'attendance' => ['attendance', 'reports', 'Learner attendance history'],
            'leave' => ['leave', 'reports', 'Learner leave history'],
            default => ['people', 'learners', 'Learner report'],
        };
    }

    /** The visible item for the current page, if any. */
    public function current(?User $user): ?array
    {
        if (request()->routeIs('student.report') && $user?->can('student-report')) {
            [$section, $group, $label] = $this->reportOwner();

            return ['key' => 'learner-report-context', 'label' => __($label), 'section' => $section, 'group' => $group,
                'section_label' => __(config('navigation.sections.'.$section.'.label')), 'group_label' => __(config('navigation.sections.'.$section.'.groups.'.$group.'.label')),
                'url' => request()->fullUrl(), 'active' => true];
        }
        if (request()->routeIs('panel.workspace')) {
            foreach ($this->sections($user) as $section) {
                if ($section['key'] !== request()->route('section')) {
                    continue;
                }
                $group = collect($section['groups'])->firstWhere('key', request()->route('group'));

                return ['key' => 'workspace', 'label' => $group['label'] ?? $section['label'],
                    'section' => $section['key'], 'group' => $group['key'] ?? '',
                    'section_label' => $section['label'], 'group_label' => $group['label'] ?? __('Overview'),
                    'url' => $this->workspaceUrl($section['key'], $group['key'] ?? null), 'active' => true];
            }
        }
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

    /** A module/group home retains the same permission-filtered registry as search. */
    public function workspaceUrl(string $section, ?string $group = null): string
    {
        return route('panel.workspace', array_filter(['section' => $section, 'group' => $group]));
    }

    /** Dropdowns open a permitted directory; group overviews retain every task. */
    private function groupDestination(string $section, string $group, array $items): string
    {
        $primary = [
            'people' => ['learners' => 'learners', 'parents' => 'parents', 'teachers' => 'teachers',
                'facilitators' => 'facilitators', 'admin-staff' => 'staff', 'alumni' => 'alumni-directory'],
            'community' => ['gallery' => 'gallery', 'newsletter' => 'school-messages',
                'events' => 'events', 'voting' => 'consultations'],
        ];
        foreach ($items as $item) {
            if ($item['key'] === ($primary[$section][$group] ?? null)) {
                return $section === 'community' && $group === 'newsletter' ? route('communication-centre.index', ['view' => 'newsletter']) : $item['url'];
            }
        }

        return $this->workspaceUrl($section, $group);
    }

    /** @param array<string, mixed> $item */
    public function visible(array $item, ?User $user): bool
    {
        if (! $user || ! $this->routeExists($item) || ! $this->moduleEnabled($item['module'] ?? null)) {
            return false;
        }

        return $this->authorityAvailable($item, $user)
            && (($item['permissions'] ?? []) === [] || $user->canAny($item['permissions']));
    }

    /**
     * Repeated menu links often share the exact same domain authority and
     * module. Evaluate each combination once while building this GET menu.
     * The local array cannot escape sections(); visible() never uses it.
     */
    private function renderVisible(array $item, User $user, array &$checks): bool
    {
        if (! $this->routeExists($item)) {
            return false;
        }
        $module = $item['module'] ?? null;
        $moduleKey = 'module:'.serialize($module);
        if (! ($checks[$moduleKey] ??= $this->moduleEnabled($module))) {
            return false;
        }

        // Keep all unknown keys in this signature so future authority flags
        // cannot accidentally share a result with an unrestricted entry.
        $authority = array_diff_key($item, array_flip([
            'key', 'label', 'route', 'url', 'params', 'active', 'keywords',
            'configuration', 'report_tab', 'attendance_destination', 'module', 'permissions',
        ]));
        ksort($authority);
        $authorityKey = 'authority:'.serialize($authority);
        if (! ($checks[$authorityKey] ??= $this->authorityAvailable($item, $user))) {
            return false;
        }
        $permissions = $item['permissions'] ?? [];
        $permissionKey = 'permissions:'.serialize($permissions);

        return $checks[$permissionKey] ??= ($permissions === [] || $user->canAny($permissions));
    }

    /** Exact source-service authority checks, also used by uncached previews. */
    protected function authorityAvailable(array $item, User $user): bool
    {
        if (($item['leave_calendar_actor'] ?? false) && ! $user->canAny(['leave-create', 'leave-approve', 'staff-leave-list', 'student-leave-approve']) && ! app(FacilitatorLeave::class)->available($user)) {
            return false;
        }
        if (($item['facilitator_leave_actor'] ?? false) && ! app(FacilitatorLeave::class)->available($user)) {
            return false;
        }
        if (($item['school_actor'] ?? false) && app(CurrentSchool::class)->idOrNull() === null) {
            return false;
        }
        if (($item['vendor_payment_reader_actor'] ?? false) && ! app(VendorPaymentReaderAccess::class)->available($user)) {
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
        if (($item['check_in_actor'] ?? false) && ! app(CheckIns::class)->available($user)) {
            return false;
        }
        if (($item['principal_dashboard'] ?? false) && ! app(LeadershipAuthority::class)->available($user)) {
            return false;
        }
        if (($item['mastery_impact_actor'] ?? false) && ! app(MasteryImpact::class)->available($user)) {
            return false;
        }
        if (($item['transfer_actor'] ?? false) && ! app(TransferAuthority::class)->available($user)) {
            return false;
        }
        if (($item['foundation_classroom_actor'] ?? false) && ! app(FoundationStaffContext::class)->available($user)) {
            return false;
        }
        if (($item['card_actor'] ?? false) && ! app(CardPanelScope::class)->available($user, (bool) ($item['card_readers'] ?? false))) {
            return false;
        }
        if (($item['passport_actor'] ?? false) && ! app(PassportAuthority::class)->available($user)) {
            return false;
        }
        if (($item['pathway_manager'] ?? false) && ! app(PathwayPanelScope::class)->available($user)) {
            return false;
        }
        if (($item['pathway_guide'] ?? false) && ! app(CareerGuidance::class)->available($user)) {
            return false;
        }
        if (($item['assistance_office'] ?? false) && ! app(AssistanceQueries::class)->available($user)) {
            return false;
        }
        if (($item['peer_actor'] ?? false) && ! app(PeerPanelScope::class)->available($user)) {
            return false;
        }
        if (($item['family_visit_actor'] ?? false) && ! app(VisitPanelScope::class)->available($user)) {
            return false;
        }
        if (($item['emergency_actor'] ?? false) && ! app(EmergencyAccess::class)->available($user)) {
            return false;
        }
        if (($item['family_form_actor'] ?? false) && ! app(FamilyPanelScope::class)->available($user)) {
            return false;
        }
        if (($item['gallery_actor'] ?? false) && ! app(GalleryPanelScope::class)->available($user)) {
            return false;
        }
        if (($item['early_years_actor'] ?? false) && ! app(EarlyYearsAuthority::class)->available($user)) {
            return false;
        }
        if (($item['reading_leader'] ?? false) && ! app(ReadingAccess::class)->mayLead($user)) {
            return false;
        }
        // Only while the school is leaving and has an export to collect (SA.11).
        if (($item['school_exit'] ?? false) && (! app(SchoolExit::class)->allowed($user) || app(SchoolExit::class)->current() === null)) {
            return false;
        }
        // Only where the school offers Google or Microsoft sign-in to this person, or they have a link (Action Plan 5.9).
        if (($item['sign_in_links'] ?? false) && ($user === null || ! app(ProviderLinks::class)->available($user))) {
            return false;
        }
        if (($item['ai_manager'] ?? false) && ! app(AiSchoolControls::class)->available($user)) {
            return false;
        }
        if (($item['ai_reviewer'] ?? false) && ! app(AiReviewAuthority::class)->available($user)) {
            return false;
        }
        if (($item['digest_manager'] ?? false) && ! app(DigestPanelScope::class)->available($user)) {
            return false;
        }
        if (($item['exam_coach_staff'] ?? false) && ! app(ExamCoachAccess::class)->availableStaff($user)) {
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
        if (($item['report_actor'] ?? false) && (! $user->can('student-list') || ! $user->can('student-report'))) {
            return false;
        }

        return true;
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
        $facilitatorCreate = $request->routeIs('staff.index') && $request->query('view') === 'create' && $request->query('role') === 'Facilitator';
        if ($item['key'] === 'facilitator-create') {
            return $facilitatorCreate;
        }
        if ($item['key'] === 'staff' && $facilitatorCreate) {
            return false;
        }
        if (isset($item['report_tab'])) {
            return ($request->routeIs('student.report') && $request->query('tab') === $item['report_tab'])
                || ($request->routeIs('students.report') && $request->query('report_tab') === $item['report_tab']);
        }
        if ($item['key'] === 'learner-report' && $request->routeIs('students.report') && in_array($request->query('report_tab'), ['attendance', 'leave'], true)) {
            return false;
        }
        $attendanceView = $request->query('view') === 'attendance'
            || ($request->routeIs('activity-groups.*') && $request->filled('session_id'));
        if (in_array($item['key'], ['activity-groups', 'discipline', 'activity-attendance', 'detention-attendance'], true)) {
            if (($item['attendance_destination'] ?? false) !== $attendanceView) {
                return false;
            }
        }
        if (in_array($item['key'], ['emergency', 'emergency-attendance'], true)
            && (($item['key'] === 'emergency-attendance') !== ($request->query('view') === 'roll-call'))) {
            return false;
        }
        if ($item['key'] === 'academic-registers' && $request->routeIs('academic-registers.configuration')) {
            return false;
        }
        if (isset($item['recognition_module']) && $request->route('module') !== $item['recognition_module']) {
            return false;
        }
        $patterns = $item['active'] ?? null;
        if ($patterns === null) {
            $patterns = isset($item['route'])
                ? [$item['route'], str_ends_with($item['route'], '.index') ? substr($item['route'], 0, -6).'.*' : $item['route']]
                : [$item['url'], $item['url'].'/*'];
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
            'configuration' => (bool) ($item['configuration'] ?? false),
        ];
    }
}
