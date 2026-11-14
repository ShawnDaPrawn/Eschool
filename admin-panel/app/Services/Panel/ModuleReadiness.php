<?php

declare(strict_types=1);

namespace App\Services\Panel;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityStaff;
use App\Models\Discipline\DisciplineFramework;
use App\Models\LeaveMaster;
use App\Models\Money\FeePlan;
use App\Models\Recognition\AwardCategory;
use App\Models\Recognition\AwardCategoryAdmin;
use App\Models\Recognition\AwardDefinition;
use App\Models\Recognition\AwardWindow;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Activities\CommunityAuthority;
use App\Services\Discipline\DisciplineAuthority;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Recognition\RecognitionAuthority;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Evidence-backed setup indicators; availability alone never means configured. */
class ModuleReadiness
{
    private function permission(User $actor, string $permission): bool
    {
        return app(ProfilePolicy::class)->permission($actor, $permission);
    }

    private function allowed(callable $check): bool
    {
        try {
            return (bool) $check();
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    private function task(string $label, bool $done, string $detail, ?string $url): array
    {
        return compact('label', 'done', 'detail', 'url');
    }

    public function forUser(User $actor): array
    {
        $fresh = app(ProfilePolicy::class)->actor($actor);
        $year = app(SessionYearContext::class)->selectedId();
        $today = app(AcademicAuthority::class)->today();
        $rows = [];
        $destinations = app(Navigation::class)->items($fresh);
        $definitions = config('modules', []) + ['leave' => ['name' => 'Staff and learner leave']];
        foreach ($definitions as $key => $definition) {
            $state = $key === 'leave' ? ['enabled' => true, 'read_only' => false, 'in_package' => true] : app(ModuleGate::class)->state($key);
            $tasks = [];
            $manager = false;
            $checked = false;
            if ($state['enabled']) {
                if ($key === 'awards') {
                    $manager = $this->allowed(fn () => app(RecognitionAuthority::class)->manages($fresh, 'awards'));
                    if ($manager) {
                        $checked = true;
                        $categories = AwardCategory::where('active', true)->pluck('id');
                        $base = ['module' => 'awards', 'view' => 'configuration', 'session_year_id' => $year];
                        $url = route('recognition.index', $base);
                        $missingDefinitions = AwardCategory::whereIn('id', $categories)->whereNotIn('id', AwardDefinition::where('active', true)->select('award_category_id'))->count();
                        $missingWindows = AwardCategory::whereIn('id', $categories)->whereNotIn('id', AwardWindow::where('session_year_id', $year)->select('award_category_id'))->count();
                        $reviewers = AwardCategoryAdmin::whereIn('award_category_id', $categories)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->count();
                        $tasks = [
                            $this->task('Active award categories', $categories->isNotEmpty(), __('Active categories: :count', ['count' => $categories->count()]), route('recognition.index', ['module' => 'awards', 'view' => 'editor', 'task' => 'category', 'session_year_id' => $year])),
                            $this->task('Award definitions in every active category', $categories->isNotEmpty() && $missingDefinitions === 0, __('Categories without an active award: :count', ['count' => $missingDefinitions]), route('recognition.index', ['module' => 'awards', 'view' => 'catalogue', 'session_year_id' => $year])),
                            $this->task('Nomination windows for the selected year', $categories->isNotEmpty() && $missingWindows === 0, __('Categories without a year window: :count', ['count' => $missingWindows]), $url),
                            $this->task('Award review responsibility', true, __('School management can review awards. Current category reviewer appointments: :count', ['count' => $reviewers]), $url),
                        ];
                    }
                } elseif ($key === 'groups') {
                    $manager = $this->allowed(fn () => app(CommunityAuthority::class)->manages($fresh));
                    if ($manager) {
                        $checked = true;
                        $groups = ActivityGroup::where('session_year_id', $year)->where('active', true)->pluck('id');
                        $unstaffed = ActivityGroup::whereIn('id', $groups)->whereNotIn('id', ActivityStaff::where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('activity_group_id'))->count();
                        $url = route('activity-groups.index', ['view' => 'groups']);
                        $tasks = [$this->task('Active activity groups', $groups->isNotEmpty(), __('Active groups: :count', ['count' => $groups->count()]), $url), $this->task('Dated activity staff appointments', $groups->isNotEmpty() && $unstaffed === 0, __('Groups without a current staff appointment: :count', ['count' => $unstaffed]), $url)];
                    }
                } elseif ($key === 'leave') {
                    $manager = $this->permission($fresh, 'leave-setting-create');
                    if ($manager) {
                        $checked = true;
                        $master = LeaveMaster::where('session_year_id', $year)->first();
                        $tasks = [$this->task('Monthly leave allowance and non-working days', $master !== null && (int) $master->total_leave >= 0 && trim((string) $master->holiday_days) !== '', $master ? __('Monthly allowance: :days; non-working days: :weekdays', ['days' => $master->total_leave, 'weekdays' => $master->holiday_days]) : __('No leave configuration for this year.'), route('leave-master.index'))];
                    }
                } elseif ($key === 'discipline') {
                    $manager = $this->allowed(fn () => app(DisciplineAuthority::class)->manages($fresh));
                    if ($manager) {
                        $checked = true;
                        $configured = DisciplineFramework::where('session_year_id', $year)->exists();
                        $tasks = [$this->task('Year discipline framework', $configured, $configured ? __('The selected year has a retained discipline framework. Review its levels and sanctions before use.') : __('No discipline framework exists for this year.'), route('discipline-panel.index', ['view' => 'configuration']))];
                    }
                } elseif ($key === 'fees') {
                    $manager = $this->permission($fresh, 'fee-plans-manage');
                    if ($manager) {
                        $checked = true;
                        $plans = FeePlan::where('session_year_id', $year)->where('state', 'active')->count();
                        $tasks = [$this->task('Active fee plans', $plans > 0, __('Active plans for this year: :count', ['count' => $plans]), route('fee-plans.index'))];
                    }
                } elseif (in_array($key, ['school', 'people', 'academics'], true)) {
                    $manager = $this->permission($fresh, 'setting-create');
                    if ($manager) {
                        $checked = true;
                        $keys = match ($key) {
                            'school' => ['school_profile', 'year_terms'],'people' => ['staff', 'learners_parents'],default => ['grades_homerooms', 'subjects']
                        };
                        foreach (app(SetupChecklist::class)->items($fresh) as $item) {
                            if (in_array($item['key'], $keys, true)) {
                                $tasks[] = $this->task($item['title'], $item['done'], $item['detail'], $item['can_act'] ? $item['url'] : null);
                            }
                        }
                    }
                }
            }
            $status = ! $state['enabled'] ? 'Unavailable' : (! $checked ? 'Review configuration' : (collect($tasks)->contains('done', false) ? 'Configuration needed' : 'Baseline configured'));
            $rows[] = ['key' => $key, 'name' => $definition['name'] ?? $key, 'availability' => $state, 'manager' => $manager, 'checked' => $checked, 'status' => $status, 'tasks' => $tasks, 'links' => array_values(array_filter($destinations, fn (array $item) => $item['module'] === $key))];
        }

        return $rows;
    }
}
