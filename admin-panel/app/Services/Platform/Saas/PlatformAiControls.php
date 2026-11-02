<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\Platform\School;
use App\Models\User;
use App\Services\Ai\Operations\AiPlatformCosts;
use App\Services\Ai\Operations\PlatformAiCaps;
use App\Services\Ai\Quarantine\AiQuarantine;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The HQ AI control centre (Action Plan SA.15): what config/ai.php says about
 * each AI feature and provider, each school's metered spend against its caps,
 * how many safeguarding flags wait in each school's queue, and which AI
 * features each school has switched on. Aggregates and counts only: no
 * prompt, answer, flag content or learner is ever read here.
 * engineering/specs/platform/hq-ai.md.
 */
class PlatformAiControls
{
    public const SCHOOLS_PER_PAGE = 50;

    /** The flag states still waiting for a school's safeguarding reviewer. */
    private const WAITING = ['open', 'escalated'];

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /** @return array<string, mixed> */
    public function overview(User $actor, int $page = 1): array
    {
        abort_unless(app(PlatformStaffAccess::class)->can($actor, 'ai.view'), 403);
        $features = $this->features();
        $page = max(1, min(100000, $page));

        $data = $this->currentSchool->acrossSchools(function () use ($features, $page): array {
            $total = DB::table('schools')->count();
            $schools = DB::table('schools')->orderBy('name')->orderBy('id')->offset(($page - 1) * self::SCHOOLS_PER_PAGE)->limit(self::SCHOOLS_PER_PAGE)
                ->get(['id', 'name', 'status', 'timezone']);

            return ['total' => $total, 'schools' => $this->schoolRows($schools->all(), $features),
                'choices' => DB::table('schools')->orderBy('name')->pluck('name', 'id')->all()];
        });
        // Module states are read inside each school, as the school's own pages read them.
        foreach ($data['schools'] as &$school) {
            $school['modules'] = $this->currentSchool->run($school['id'], fn (): array => $this->moduleStates());
        }
        unset($school);

        return [
            'providers' => $this->providers($features),
            'features' => $features,
            'modules' => $this->modules($features),
            'thresholds' => array_map(fn ($minimum) => is_int($minimum) ? $minimum / 100 : null, (array) config('ai.evaluation.minimum', [])),
            'platformOn' => config('ai.inference.enabled') === true,
            'currency' => (string) config('ai.billing_currency'),
            'schools' => $data['schools'],
            'schoolChoices' => $data['choices'],
            'page' => $page,
            'lastPage' => max(1, (int) ceil($data['total'] / self::SCHOOLS_PER_PAGE)),
            'canManage' => app(PlatformStaffAccess::class)->can($actor, 'ai.manage'),
            'canEditCaps' => app(PlatformStaffAccess::class)->can($actor, 'ai.caps'),
            'capMaximum' => PlatformAiCaps::MAXIMUM,
            'capHistory' => app(PlatformAiCaps::class)->history(),
            'costReport' => $this->holdsCostGrant($actor),
        ];
    }

    /**
     * Counts across every school for the HQ home page, from the same rows the
     * control centre shows: schools at or over today's cap, schools whose
     * own AI is paused, and safeguarding flags still waiting. Never a name
     * of a person, a prompt or a flag's content.
     *
     * @return array{schools: int, over_cap: int, paused: int, flags_open: int, flags_escalated: int, platform_on: bool}
     */
    public function summary(User $actor): array
    {
        abort_unless(app(PlatformStaffAccess::class)->can($actor, 'ai.view'), 403);
        $features = $this->features();
        $rows = $this->currentSchool->acrossSchools(fn (): array => $this->schoolRows(
            DB::table('schools')->orderBy('id')->get(['id', 'name', 'status', 'timezone'])->all(), $features));

        return [
            'schools' => count($rows),
            'over_cap' => count(array_filter($rows, fn (array $row): bool => $row['over'])),
            'paused' => count(array_filter($rows, fn (array $row): bool => $row['paused'])),
            'flags_open' => array_sum(array_map(fn (array $row): int => $row['queue']['open'], $rows)),
            'flags_escalated' => array_sum(array_map(fn (array $row): int => $row['queue']['escalated'], $rows)),
            'platform_on' => config('ai.inference.enabled') === true,
        ];
    }

    /** @return array<string, mixed> */
    public static function switchRules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'min:1'],
            'module' => ['required', 'string', Rule::in(array_values(array_unique((array) config('ai.inference.school_modules', []))))],
            'enabled' => ['required', 'boolean'],
            // Kept in the audit log with who switched it, as for a school's status.
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    /**
     * Switch an AI feature's module on or off for one school, through the same
     * school_modules switch the school's own admin and HQ's module controls
     * use. Switching on is refused while any AI feature in the module is not
     * qualified in config/ai.php. Returns whether anything changed.
     */
    public function switch(array $data, User $actor): bool
    {
        abort_unless(app(PlatformStaffAccess::class)->can($actor, 'ai.manage'), 403);
        $data = Validator::make($data, self::switchRules())->validate();
        $schoolId = (int) $data['school_id'];
        if (! $this->currentSchool->acrossSchools(fn (): bool => DB::table('schools')->where('id', $schoolId)->exists())) {
            throw ValidationException::withMessages(['school_id' => 'Choose a school.']);
        }
        $module = (string) $data['module'];
        $enabled = filter_var($data['enabled'], FILTER_VALIDATE_BOOLEAN);
        $features = array_keys(array_filter((array) config('ai.inference.school_modules', []), fn ($key) => $key === $module));
        if ($enabled) {
            $unqualified = array_values(array_filter($features, fn (string $feature): bool => ! $this->qualified($feature)));
            if ($unqualified !== []) {
                throw ValidationException::withMessages(['module' => 'This AI feature is not qualified, so it cannot be switched on for a school: '.implode(', ', array_map(fn ($f) => Str::headline($f), $unqualified)).'.']);
            }
        }

        return DB::transaction(function () use ($schoolId, $module, $enabled, $features, $data, $actor): bool {
            $before = $this->currentSchool->run($schoolId, function () use ($module): bool {
                app(ModuleGate::class)->forget();

                return app(ModuleGate::class)->state($module)['enabled'];
            });
            if ($before === $enabled) {
                return false;
            }
            $this->currentSchool->run($schoolId, fn () => app(ModuleGate::class)->change($module, $enabled, $actor));
            // Ids, states and the reason only: never a prompt, an answer or a learner.
            Audit::record('platform_ai_feature_switched', School::query()->findOrFail($schoolId), ['enabled' => $before],
                ['module' => $module, 'features' => $features, 'enabled' => $enabled, 'reason' => (string) $data['reason'], 'changed_by' => $actor->id], $schoolId);

            return true;
        });
    }

    /** Whether config/ai.php records the feature's provider as qualified. Nothing else qualifies it. */
    public function qualified(string $feature): bool
    {
        $provider = config('ai.inference.features.'.$feature.'.provider');

        return is_string($provider) && $provider !== '' && config('ai.providers.'.$provider.'.qualified') === true;
    }

    /** @return array<string, array<string, mixed>> */
    private function features(): array
    {
        $modules = (array) config('ai.inference.school_modules', []);
        $participants = (array) config('ai.quarantine.participants', []);
        // The caps in force: HQ's saved value, else the config/ai.php default.
        $caps = app(PlatformAiCaps::class)->caps();
        $answer = [];
        foreach ((array) config('ai.inference.features', []) as $name => $settings) {
            if (! is_string($name) || ! is_array($settings)) {
                continue;
            }
            $int = fn (string $key): ?int => is_int($settings[$key] ?? null) ? $settings[$key] : null;
            $personal = PlatformAiCaps::personal($name);
            $cents = fn (string $key): ?int => $caps[$name][$key]['effective'] ?? null;
            $answer[$name] = [
                'name' => $name,
                'label' => Str::headline($name),
                'provider' => is_string($settings['provider'] ?? null) ? $settings['provider'] : '',
                'model' => is_string($settings['model'] ?? null) ? $settings['model'] : '',
                'qualified' => $this->qualified($name),
                'switched_on' => config('ai.inference.enabled') === true && ($settings['enabled'] ?? false) === true,
                'priced' => is_int($settings['cents_per_million_tokens'] ?? null) && $settings['cents_per_million_tokens'] > 0,
                // A personal service has no school cap: its spend is not part of the school's day.
                'personal' => $personal,
                'caps' => ['school_cents' => $personal ? null : $cents('school_daily_cents'), 'actor_cents' => $cents('actor_daily_cents'), 'learner_cents' => $cents('learner_daily_cents'),
                    'school_tokens' => $personal ? null : $int('school_daily_tokens'), 'actor_tokens' => $int('actor_daily_tokens'), 'learner_tokens' => $int('learner_daily_tokens')],
                'cap_settings' => $caps[$name] ?? [],
                'module' => is_string($modules[$name] ?? null) ? $modules[$name] : null,
                'evaluation_cases' => $this->evaluationCases($name),
                'safeguarding_queue' => array_key_exists($name, $participants),
            ];
        }

        return $answer;
    }

    /** How many cases the feature's evaluation corpus holds; the cases themselves are never read out. */
    private function evaluationCases(string $feature): ?int
    {
        if (! preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $feature)) {
            return null;
        }
        $path = resource_path('ai/evals/'.$feature.'.jsonl');
        if (! is_file($path) || filesize($path) > 1048576) {
            return null;
        }

        return count(file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []);
    }

    /** @return list<array<string, mixed>> */
    private function providers(array $features): array
    {
        $answer = [];
        foreach ((array) config('ai.providers', []) as $name => $settings) {
            if (! is_string($name) || ! is_array($settings)) {
                continue;
            }
            $answer[] = [
                'name' => $name,
                'qualified' => ($settings['qualified'] ?? null) === true,
                // Only whether a key is set: the key itself never leaves the config.
                'key_set' => is_string($settings['key'] ?? null) && $settings['key'] !== '',
                'moderation_model' => is_string($settings['moderation_model'] ?? null) ? $settings['moderation_model'] : null,
                'features' => array_values(array_map(fn ($f) => $f['label'], array_filter($features, fn ($f) => $f['provider'] === $name))),
            ];
        }

        return $answer;
    }

    /** @return array<string, array{key: string, name: string, features: list<string>, qualified: bool}> */
    private function modules(array $features): array
    {
        $answer = [];
        foreach ($features as $feature) {
            if ($feature['module'] === null) {
                continue;
            }
            $key = $feature['module'];
            $answer[$key] ??= ['key' => $key, 'name' => (string) config('modules.'.$key.'.name', Str::headline($key)), 'features' => [], 'qualified' => true];
            $answer[$key]['features'][] = $feature['label'];
            $answer[$key]['qualified'] = $answer[$key]['qualified'] && $feature['qualified'];
        }

        return $answer;
    }

    /** @return array<string, array{enabled: bool, in_package: bool}> */
    private function moduleStates(): array
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        $answer = [];
        foreach (array_unique(array_values((array) config('ai.inference.school_modules', []))) as $module) {
            $state = $gate->state((string) $module);
            $answer[(string) $module] = ['enabled' => $state['enabled'], 'in_package' => $state['in_package']];
        }

        return $answer;
    }

    /**
     * Each school's month and day by its own clock, as the gateway meters
     * them (ai_usage.usage_date is the school's local date).
     *
     * @param  list<object>  $schools
     * @return list<array<string, mixed>>
     */
    private function schoolRows(array $schools, array $features): array
    {
        if ($schools === []) {
            return [];
        }
        $ids = array_map(fn ($school) => (int) $school->id, $schools);
        $days = [];
        foreach ($schools as $school) {
            $today = CarbonImmutable::now($school->timezone ?: 'Africa/Johannesburg');
            $days[(int) $school->id] = ['today' => $today->toDateString(), 'month' => $today->startOfMonth()->toDateString()];
        }
        $currency = (string) config('ai.billing_currency');

        // Aggregate ledger columns only, as /platform/ai-costs: no output, source, user or learner column.
        $spend = DB::table('ai_usage')->whereIn('school_id', $ids)
            ->whereBetween('usage_date', [min(array_column($days, 'month')), max(array_column($days, 'today'))])
            ->groupBy('school_id', 'usage_date', 'billing_currency', 'personal')
            ->selectRaw('school_id, usage_date, billing_currency, '.$this->personalCase().' AS personal, SUM(cost_cents) AS cost_cents, SUM(reserved_cost_cents) AS reserved_cost_cents', PlatformAiCaps::personalFeatures())
            ->get();
        $limits = DB::table('ai_school_limits')->whereIn('school_id', $ids)->get(['school_id', 'paused', 'limits'])->keyBy('school_id');

        $cutoff = now()->subDays(app(AiQuarantine::class)->retentionDays());
        // The same flags a school's reviewer can still open (AiFlagReviews), counted, never read.
        $queue = DB::table('ai_flags')->join('ai_usage', fn ($join) => $join->on('ai_usage.id', '=', 'ai_flags.ai_usage_id')->on('ai_usage.school_id', '=', 'ai_flags.school_id'))
            ->whereIn('ai_flags.school_id', $ids)->whereIn('ai_flags.review_status', self::WAITING)
            ->whereIn('ai_usage.feature', array_keys((array) config('ai.quarantine.participants', [])))
            ->where(function ($visible) use ($cutoff): void {
                $visible->where(fn ($old) => $old->whereNull('ai_flags.quarantine_snapshot_id')->where('ai_flags.created_at', '>', $cutoff))
                    ->orWhereExists(fn ($snapshots) => $snapshots->selectRaw('1')->from('ai_quarantine_snapshots')
                        ->whereColumn('ai_quarantine_snapshots.id', 'ai_flags.quarantine_snapshot_id')->whereColumn('ai_quarantine_snapshots.school_id', 'ai_flags.school_id')
                        ->where('ai_quarantine_snapshots.expires_at', '>', now())->where('ai_quarantine_snapshots.created_at', '>', $cutoff));
            })
            ->groupBy('ai_flags.school_id', 'ai_flags.review_status')
            ->selectRaw('ai_flags.school_id AS school_id, ai_flags.review_status AS review_status, COUNT(*) AS flags')
            ->get();

        $schoolFeatures = array_filter($features, fn ($f) => ! $f['personal']);
        $personalFeatures = array_filter($features, fn ($f) => $f['personal']);
        $ceilings = fn (string $key, array $among): array => array_values(array_filter(array_map(fn ($f) => $f['caps'][$key], $among), 'is_int'));
        $rows = [];
        foreach ($schools as $school) {
            $id = (int) $school->id;
            $limit = $limits->get($id);
            $own = $limit === null ? [] : (array) json_decode((string) $limit->limits, true);
            // [school spend, personal (tutor) spend], kept apart as the gateway keeps them.
            $month = [[], []];
            $today = [0, 0];
            foreach ($spend->where('school_id', $id) as $row) {
                if ((string) $row->usage_date < $days[$id]['month'] || (string) $row->usage_date > $days[$id]['today']) {
                    continue;
                }
                $pool = (int) $row->personal === 1 ? 1 : 0;
                $key = $row->billing_currency ?? '';
                $month[$pool][$key] = ($month[$pool][$key] ?? 0) + (int) $row->cost_cents + (int) $row->reserved_cost_cents;
                if ((string) $row->usage_date === $days[$id]['today'] && $row->billing_currency === $currency) {
                    $today[$pool] += (int) $row->cost_cents + (int) $row->reserved_cost_cents;
                }
            }
            ksort($month[0]);
            ksort($month[1]);
            $listed = fn (array $amounts): array => array_map(fn ($cents, $code) => self::money((int) $cents, $code === '' ? null : (string) $code), $amounts, array_keys($amounts));
            $cap = $this->cap($ceilings('school_cents', $schoolFeatures), $own['school_daily_cents'] ?? null);
            $tutorCap = $this->cap($ceilings('learner_cents', $personalFeatures), $own['learner_daily_cents'] ?? null);
            $rows[] = [
                'id' => $id,
                'name' => (string) $school->name,
                'status' => (string) $school->status,
                'paused' => (bool) ($limit->paused ?? false),
                'month' => $listed($month[0]),
                'today' => self::money($today[0], $currency),
                'cap' => $cap === null ? null : self::money($cap, $currency),
                'used_percent' => $cap === null || $cap === 0 ? null : (int) floor($today[0] * 100 / $cap),
                'over' => $cap !== null && $today[0] >= $cap,
                'learner_cap' => ($learner = $this->cap($ceilings('learner_cents', $schoolFeatures), $own['learner_daily_cents'] ?? null)) === null ? null : self::money($learner, $currency),
                // Personal services (the Mastery tutor): shown apart, never against the school's cap.
                'personal' => ['month' => $listed($month[1]), 'today' => self::money($today[1], $currency),
                    'learner_cap' => $tutorCap === null ? null : self::money($tutorCap, $currency)],
                'own_limits' => array_key_exists('school_daily_cents', $own) || array_key_exists('learner_daily_cents', $own),
                'queue' => ['open' => (int) $queue->where('school_id', $id)->where('review_status', 'open')->sum('flags'),
                    'escalated' => (int) $queue->where('school_id', $id)->where('review_status', 'escalated')->sum('flags')],
            ];
        }

        return $rows;
    }

    /** SQL marking a personal-service receipt 1 and a school receipt 0. */
    private function personalCase(): string
    {
        $personal = PlatformAiCaps::personalFeatures();

        return $personal === [] ? '0' : 'CASE WHEN feature IN ('.implode(',', array_fill(0, count($personal), '?')).') THEN 1 ELSE 0 END';
    }

    /**
     * The most the gateway lets a school spend in a day: the highest school
     * feature cap in force (it checks the day's school spend against the cap
     * of the feature in use), lowered by the school's own limit when it set
     * one. Personal services are left out on both sides.
     *
     * @param  list<int>  $ceilings
     */
    private function cap(array $ceilings, mixed $own): ?int
    {
        if ($ceilings === []) {
            return null;
        }

        return is_int($own) ? min(max($ceilings), $own) : max($ceilings);
    }

    /** Integer cents shown in the metering currency; no exchange rate is ever applied. */
    public static function money(int $cents, ?string $currency): string
    {
        if ($currency === null || ! preg_match('/^[A-Z]{3}$/D', $currency)) {
            return ltrim(Money::ofCents($cents, 'XXX')->format('')).' (currency not recorded)';
        }

        return Money::ofCents($cents, $currency)->format($currency === 'ZAR' ? 'R' : ($currency === 'USD' ? 'US$' : null));
    }

    /** Whether the actor still holds the separate /platform/ai-costs grant, to offer that dated report. */
    private function holdsCostGrant(User $actor): bool
    {
        try {
            app(AiPlatformCosts::class)->actor($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
