<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\RequestContext;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Packages and their prices (Action Plan SA.7), the one place that says which
 * modules a package includes and what it costs. config/school_packages.php is
 * only the seed of the 2026_11_01_140000 migration.
 *
 * A package is built from whole product modules (config/product_modules.php,
 * Core always included). Its module set is versioned: while a package is a
 * draft no school can be on it and the set is edited in place; once active,
 * a change adds a new version. A school keeps the version it was given
 * (schools.package_version, null meaning 1) until HQ re-applies the package
 * to it, an audited action. So editing a package never silently changes what
 * a school is entitled to.
 *
 * Prices are per learner per month in integer cents, excluding VAT, and
 * effective-dated: a package's base price, an add-on price per product module
 * and a discount off the base. A price in force or past is never edited; a new
 * price ends the open-ended one the day before it starts, and overlapping
 * ranges are refused. A price that has not started yet may be withdrawn.
 *
 * Only packages.manage (Owner, Finance) changes anything; packages.view reads.
 * engineering/specs/platform/package-editor.md.
 */
class PlatformPackages
{
    /** HQ works in South African time: "today" for prices is a Johannesburg date. */
    public const TIMEZONE = 'Africa/Johannesburg';

    /** The package of a school whose package_key is null (the upgraded seed school). */
    public const LEGACY = 'legacy';

    public const STATUSES = ['draft' => 'Draft', 'active' => 'Active', 'retired' => 'Retired'];

    public const KINDS = [
        'base' => 'Base price',
        'module' => 'Add-on product module',
        'discount' => 'Discount off the base price',
    ];

    public const MAX_TRIAL_DAYS = 365;

    /** @var array<string, object|null> package rows by key, for this request or job */
    private array $byKey = [];

    /** @var array<string, list<string>> product modules by "package id:version" */
    private array $versions = [];

    /** @var array<string, list<string>> a school's technical modules by "key@version", outside HTTP (one job) */
    private array $jobSchools = [];

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    // ---- rules (the FormRequest and the service use the same ones) ----

    public static function createRules(): array
    {
        return [
            'package_key' => ['required', 'string', 'regex:/^[a-z][a-z0-9-]{1,31}$/D', Rule::unique('platform_packages', 'package_key')],
        ] + self::detailRules() + self::moduleRules();
    }

    public static function detailRules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:'.self::MAX_TRIAL_DAYS],
        ];
    }

    public static function moduleRules(): array
    {
        return [
            'product_modules' => ['nullable', 'array', 'max:'.count((array) config('product_modules'))],
            'product_modules.*' => ['string', 'distinct', Rule::in(array_keys((array) config('product_modules')))],
        ];
    }

    public static function statusRules(): array
    {
        return ['status' => ['required', Rule::in(['active', 'retired'])]];
    }

    public static function priceRules(): array
    {
        return [
            'kind' => ['required', Rule::in(array_keys(self::KINDS))],
            'product_module' => ['nullable', 'required_if:kind,module', 'prohibited_unless:kind,module', Rule::in(self::addOnModules())],
            // Rands as typed: digits and up to two decimals, never a float.
            'amount' => ['required', 'string', 'regex:/^\d{1,6}(\.\d{1,2})?$/D'],
            'effective_from' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.self::today()],
            'effective_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_from'],
            'note' => ['nullable', 'string', 'max:191'],
        ];
    }

    public static function reapplyRules(): array
    {
        return ['reason' => ['required', 'string', 'min:5', 'max:500']];
    }

    /** Product modules that can be priced as an add-on: all but the always-included ones. */
    public static function addOnModules(): array
    {
        return array_keys(array_filter((array) config('product_modules'), fn (array $product): bool => ! ($product['always_included'] ?? false)));
    }

    public static function today(): string
    {
        return CarbonImmutable::now(self::TIMEZONE)->toDateString();
    }

    /**
     * Rands typed as text ("120", "89.5", "0.07") to integer cents, without
     * floating point. Anything else is refused.
     */
    public static function cents(string $rands): int
    {
        if (preg_match('/^\d{1,6}(\.\d{1,2})?$/D', $rands) !== 1) {
            throw ValidationException::withMessages(['amount' => __('Enter an amount in rands, such as 89 or 89.50.')]);
        }

        return Money::fromDecimalString($rands, 'ZAR')->cents;
    }

    public static function rands(int $cents): string
    {
        return Money::ofCents($cents, 'ZAR')->format('R');
    }

    // ---- reading ----

    /** The package row for a key, or null. */
    public function package(string $key): ?object
    {
        if (! array_key_exists($key, $this->byKey)) {
            $this->byKey[$key] = DB::table('platform_packages')->where('package_key', $key)->first();
        }

        return $this->byKey[$key];
    }

    /** @return list<string> every package key, oldest first */
    public function keys(): array
    {
        return DB::table('platform_packages')->orderBy('id')->pluck('package_key')->map(fn ($key) => (string) $key)->all();
    }

    /** @return array<string, string> active package key => name: what a school can be put on */
    public function assignable(): array
    {
        return DB::table('platform_packages')->where('status', 'active')->orderBy('id')->pluck('name', 'package_key')
            ->mapWithKeys(fn ($name, $key) => [(string) $key => (string) $name])->all();
    }

    /** @return list<string> product module keys in one version of a package's module set */
    public function productModules(int $packageId, int $version): array
    {
        $memo = $packageId.':'.$version;
        if (! isset($this->versions[$memo])) {
            $included = DB::table('platform_package_modules')->where('package_id', $packageId)->where('version', $version)->pluck('product_module')->all();
            // In catalogue order, whatever order they were stored in.
            $this->versions[$memo] = array_values(array_filter(array_keys((array) config('product_modules')), fn (string $key): bool => in_array($key, $included, true)));
        }

        return $this->versions[$memo];
    }

    /**
     * The technical modules (config/modules.php) a package includes, in the
     * given version of its module set or, without one, the current version.
     *
     * @return list<string>
     */
    public function technicalModules(string $key, ?int $version = null): array
    {
        $package = $this->package($key);
        if ($package === null) {
            return [];
        }

        return self::expand($this->productModules((int) $package->id, $version ?? (int) $package->modules_version));
    }

    /**
     * What a school's package entitles it to: the version of the module set
     * applied to the school, not the package's latest. The ModuleGate reads it.
     *
     * @return list<string>
     */
    public function forSchool(?object $school): array
    {
        $key = (string) ($school?->package_key ?? self::LEGACY);
        $version = ($school?->package_version ?? null) === null ? 1 : (int) $school->package_version;
        // One query per request: the gate asks for every module of /me/context.
        $memo = 'platform.packages.school.'.$key.'@'.$version;
        $http = RequestContext::inHttpRequest();
        if ($http && request()->attributes->has($memo)) {
            return request()->attributes->get($memo);
        }
        if (! $http && isset($this->jobSchools[$memo])) {
            return $this->jobSchools[$memo];
        }
        $included = DB::table('platform_packages as pp')->join('platform_package_modules as ppm', 'ppm.package_id', '=', 'pp.id')
            ->where('pp.package_key', $key)->where('ppm.version', $version)->pluck('ppm.product_module')->all();
        $modules = self::expand(array_values(array_filter(array_keys((array) config('product_modules')), fn (string $product): bool => in_array($product, $included, true))));
        if ($http) {
            request()->attributes->set($memo, $modules);
        }

        return $http ? $modules : $this->jobSchools[$memo] = $modules;
    }

    /**
     * Narrow a query on schools (aliased $schools) to those whose applied
     * package version includes a technical module, in SQL, so a prepared
     * query rechecks it when it runs.
     */
    public function whereSchoolPackageIncludes(Builder $query, string $schools, string $module): Builder
    {
        abort_unless(preg_match('/^[a-z_]+$/D', $schools) === 1, 500);
        $products = array_keys(array_filter((array) config('product_modules'), fn (array $product): bool => in_array($module, $product['modules'], true)));

        return $query->whereExists(fn (Builder $exists) => $exists->selectRaw('1')->from('platform_packages as pp')
            ->join('platform_package_modules as ppm', 'ppm.package_id', '=', 'pp.id')
            ->whereRaw("pp.package_key = COALESCE({$schools}.package_key, ?)", [self::LEGACY])
            ->whereRaw("ppm.version = COALESCE({$schools}.package_version, 1)")
            ->whereIn('ppm.product_module', $products === [] ? [''] : $products));
    }

    /**
     * The price per learner per month, in cents excluding VAT, on a date: for
     * the package itself (the base price less any discount, never below
     * zero), or for one product module as an add-on to it. Null when no such
     * price is in force on that date. For billing (SA.8).
     */
    public function priceOn(string $key, CarbonInterface|string $date, ?string $productModule = null): ?int
    {
        $quote = $this->quote($key, $date);
        if ($quote === null) {
            return null;
        }

        return $productModule === null ? $quote['net_cents'] : ($quote['modules'][$productModule] ?? null);
    }

    /**
     * Every price of a package in force on a date.
     *
     * @return array{date: string, base_cents: ?int, discount_cents: int, net_cents: ?int, modules: array<string, int>}|null
     */
    public function quote(string $key, CarbonInterface|string $date): ?array
    {
        $package = $this->package($key);
        if ($package === null) {
            return null;
        }
        $day = $date instanceof CarbonInterface ? $date->toDateString() : CarbonImmutable::createFromFormat('!Y-m-d', $date)->toDateString();
        $rows = DB::table('platform_prices')->where('package_id', $package->id)->whereNull('withdrawn_at')
            ->where('effective_from', '<=', $day)->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $day))
            ->get(['kind', 'product_module', 'amount_cents']);
        $base = $rows->firstWhere('kind', 'base');
        $discount = (int) ($rows->firstWhere('kind', 'discount')->amount_cents ?? 0);
        $modules = [];
        foreach ($rows->where('kind', 'module') as $row) {
            $modules[(string) $row->product_module] = (int) $row->amount_cents;
        }

        return [
            'date' => $day,
            'base_cents' => $base === null ? null : (int) $base->amount_cents,
            'discount_cents' => $base === null ? 0 : min($discount, (int) $base->amount_cents),
            'net_cents' => $base === null ? null : max(0, (int) $base->amount_cents - $discount),
            'modules' => $modules,
        ];
    }

    /**
     * Every package for the catalogue page: status, trial, module set, how
     * many schools are on it and the price today.
     *
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        $today = self::today();
        $schools = $this->schoolCounts();

        return DB::table('platform_packages')->orderBy('id')->get()->map(fn (object $row): array => [
            'id' => (int) $row->id, 'key' => (string) $row->package_key, 'name' => (string) $row->name, 'description' => $row->description,
            'status' => (string) $row->status, 'trial_days' => (int) $row->trial_days, 'version' => (int) $row->modules_version,
            'product_modules' => $this->productModules((int) $row->id, (int) $row->modules_version),
            'schools' => $schools[(string) $row->package_key]['all'] ?? 0,
            'quote' => $this->quote((string) $row->package_key, $today),
        ])->all();
    }

    /** One package with its module set history, schools, prices and the price on a date. */
    public function show(int $id, ?string $on = null): array
    {
        $row = DB::table('platform_packages')->where('id', $id)->first();
        abort_if($row === null, 404);
        $on = is_string($on) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $on) === 1 && checkdate((int) substr($on, 5, 2), (int) substr($on, 8, 2), (int) substr($on, 0, 4)) ? $on : self::today();
        $counts = $this->schoolCounts()[(string) $row->package_key] ?? ['all' => 0, 'behind' => 0];
        $versions = DB::table('platform_package_modules')->where('package_id', $id)->groupBy('version')
            ->selectRaw('version, MIN(created_at) AS created_at')->orderByDesc('version')->get()
            ->map(fn (object $v): array => ['version' => (int) $v->version, 'created_at' => $v->created_at, 'product_modules' => $this->productModules($id, (int) $v->version)])->all();
        $prices = DB::table('platform_prices')->where('package_id', $id)->orderBy('kind')->orderBy('product_module')->orderByDesc('effective_from')->orderByDesc('id')->get();
        $today = self::today();

        return [
            'package' => [
                'id' => (int) $row->id, 'key' => (string) $row->package_key, 'name' => (string) $row->name, 'description' => $row->description,
                'status' => (string) $row->status, 'trial_days' => (int) $row->trial_days, 'version' => (int) $row->modules_version,
                'product_modules' => $this->productModules($id, (int) $row->modules_version),
                'activated_at' => $row->activated_at, 'retired_at' => $row->retired_at,
                'schools' => $counts['all'], 'schools_behind' => $counts['behind'],
            ],
            'versions' => $versions,
            'prices' => $prices->map(fn (object $p): array => [
                'id' => (int) $p->id, 'kind' => (string) $p->kind, 'product_module' => $p->product_module, 'amount_cents' => (int) $p->amount_cents,
                'effective_from' => (string) $p->effective_from, 'effective_to' => $p->effective_to, 'note' => $p->note,
                'withdrawn' => $p->withdrawn_at !== null,
                'state' => $p->withdrawn_at !== null ? 'withdrawn' : ($p->effective_from > $today ? 'scheduled' : ($p->effective_to !== null && $p->effective_to < $today ? 'ended' : 'in_force')),
            ])->all(),
            'on' => $on,
            'quote' => $this->quote((string) $row->package_key, $on),
        ];
    }

    // ---- writing: packages.manage only, each change audited ----

    public function create(array $input, User $actor): int
    {
        $this->authorise($actor);
        $data = Validator::make($input, self::createRules())->validate();
        $products = self::withAlwaysIncluded($data['product_modules'] ?? []);

        return $this->write(function () use ($data, $products, $actor): int {
            $id = (int) DB::table('platform_packages')->insertGetId([
                'package_key' => $data['package_key'], 'name' => $data['name'], 'description' => $data['description'] ?? null,
                'status' => 'draft', 'trial_days' => (int) $data['trial_days'], 'modules_version' => 1,
                'created_by' => $actor->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->storeVersion($id, 1, $products, $actor);
            Audit::record('platform_package_created', null, [], ['package_id' => $id, 'package_key' => $data['package_key'], 'name' => $data['name'],
                'description' => $data['description'] ?? null, 'status' => 'draft', 'trial_days' => (int) $data['trial_days'], 'product_modules' => $products]);

            return $id;
        });
    }

    /** Name, description and trial days, while the package is a draft or active. */
    public function update(int $id, array $input, User $actor): void
    {
        $this->authorise($actor);
        $data = Validator::make($input, self::detailRules())->validate();
        $this->write(function () use ($id, $data): void {
            $row = $this->lock($id);
            $this->refuseRetired($row);
            $after = ['name' => $data['name'], 'description' => ($data['description'] ?? null) ?: null, 'trial_days' => (int) $data['trial_days']];
            $before = ['name' => $row->name, 'description' => $row->description, 'trial_days' => (int) $row->trial_days];
            $changed = array_keys(array_diff_assoc(array_map('strval', $after), array_map('strval', $before)));
            if ($changed === []) {
                return;
            }
            DB::table('platform_packages')->where('id', $id)->update($after + ['updated_at' => now()]);
            Audit::record('platform_package_updated', null, ['package_id' => $id] + array_intersect_key($before, array_flip($changed)), ['package_id' => $id] + array_intersect_key($after, array_flip($changed)));
        });
    }

    /**
     * The product modules a package includes. A draft's set is changed in
     * place; an active package gets a new version, which schools already on
     * the package receive only when HQ re-applies it (reapply()).
     */
    public function setModules(int $id, array $input, User $actor): void
    {
        $this->authorise($actor);
        $data = Validator::make($input, self::moduleRules())->validate();
        $products = self::withAlwaysIncluded($data['product_modules'] ?? []);
        $this->write(function () use ($id, $products, $actor): void {
            $row = $this->lock($id);
            $this->refuseRetired($row);
            $current = (int) $row->modules_version;
            $before = $this->productModules($id, $current);
            if ($before === $products) {
                return;
            }
            $version = $row->status === 'draft' ? $current : $current + 1;
            if ($version === $current) {
                DB::table('platform_package_modules')->where('package_id', $id)->where('version', $current)->delete();
            }
            $this->storeVersion($id, $version, $products, $actor);
            DB::table('platform_packages')->where('id', $id)->update(['modules_version' => $version, 'updated_at' => now()]);
            Audit::record('platform_package_modules_changed', null, ['package_id' => $id, 'version' => $current, 'product_modules' => $before],
                ['package_id' => $id, 'version' => $version, 'product_modules' => $products, 'schools_on_earlier_version' => $this->schoolsBehind($row, $version)]);
        });
    }

    /** Draft to active (schools can then be put on it), or active to retired (no new schools; those on it keep it). */
    public function changeStatus(int $id, array $input, User $actor): void
    {
        $this->authorise($actor);
        $status = Validator::make($input, self::statusRules())->validate()['status'];
        $this->write(function () use ($id, $status): void {
            $row = $this->lock($id);
            $allowed = ['draft' => 'active', 'active' => 'retired'];
            if (($allowed[$row->status] ?? null) !== $status) {
                throw ValidationException::withMessages(['status' => $status === 'active'
                    ? __('Only a draft package can be activated.')
                    : __('Only an active package can be retired.')]);
            }
            DB::table('platform_packages')->where('id', $id)->update(['status' => $status, $status === 'active' ? 'activated_at' : 'retired_at' => now(), 'updated_at' => now()]);
            Audit::record($status === 'active' ? 'platform_package_activated' : 'platform_package_retired', null,
                ['package_id' => $id, 'status' => $row->status], ['package_id' => $id, 'status' => $status]);
        });
    }

    /**
     * Add a price from a date (today or later). An open-ended price in force
     * before that date ends the day before; any other overlap is refused.
     */
    public function addPrice(int $id, array $input, User $actor): int
    {
        $this->authorise($actor);
        $data = Validator::make($input, self::priceRules())->validate();
        $cents = self::cents($data['amount']);
        if ($data['kind'] === 'discount' && $cents === 0) {
            throw ValidationException::withMessages(['amount' => __('A discount must be more than R 0.00.')]);
        }
        $module = $data['kind'] === 'module' ? $data['product_module'] : null;
        $from = $data['effective_from'];
        $to = ($data['effective_to'] ?? null) ?: null;

        return $this->write(function () use ($id, $data, $module, $cents, $from, $to, $actor): int {
            $this->lock($id);
            $series = $this->series($id, $data['kind'], $module)->lockForUpdate()->get();
            $open = $series->first(fn (object $p): bool => $p->effective_to === null && $p->effective_from < $from);
            if ($open !== null && $to !== null) {
                throw ValidationException::withMessages(['effective_to' => __('The price from :date has no end date. A new price ends it the day before the new one starts, so leave the end date empty.', ['date' => $open->effective_from])]);
            }
            foreach ($series as $price) {
                if ($price !== $open && $price->effective_from <= ($to ?? '9999-12-31') && ($price->effective_to ?? '9999-12-31') >= $from) {
                    throw ValidationException::withMessages(['effective_from' => __('These dates overlap the price from :from:to. Choose dates that do not overlap, or withdraw that price if it has not started.', [
                        'from' => $price->effective_from, 'to' => $price->effective_to === null ? '' : ' to '.$price->effective_to])]);
                }
            }
            if ($open !== null) {
                $end = CarbonImmutable::createFromFormat('!Y-m-d', $from)->subDay()->toDateString();
                DB::table('platform_prices')->where('id', $open->id)->update(['effective_to' => $end, 'updated_at' => now()]);
                Audit::record('platform_package_price_ended', null, ['package_id' => $id, 'price_id' => (int) $open->id, 'effective_to' => null],
                    ['package_id' => $id, 'price_id' => (int) $open->id, 'effective_to' => $end]);
            }
            $after = ['package_id' => $id, 'kind' => $data['kind'], 'product_module' => $module, 'amount_cents' => $cents, 'vat_excluded' => true,
                'effective_from' => $from, 'effective_to' => $to, 'note' => ($data['note'] ?? null) ?: null, 'closes_price_id' => $open?->id];
            $priceId = (int) DB::table('platform_prices')->insertGetId($after + ['created_by' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);
            Audit::record('platform_package_price_added', null, [], ['price_id' => $priceId] + $after);

            return $priceId;
        });
    }

    /**
     * Withdraw a price that has not started yet, the latest of its kind. A
     * price it ended is open-ended again. Prices in force or past stay as
     * they are.
     */
    public function withdrawPrice(int $id, int $priceId, User $actor): void
    {
        $this->authorise($actor);
        $this->write(function () use ($id, $priceId, $actor): void {
            $this->lock($id);
            $price = DB::table('platform_prices')->where('id', $priceId)->where('package_id', $id)->lockForUpdate()->first();
            abort_if($price === null, 404);
            if ($price->withdrawn_at !== null || $price->effective_from <= self::today()) {
                throw ValidationException::withMessages(['price' => __('Only a price that has not started can be withdrawn. Add a new price instead.')]);
            }
            if ($this->series($id, (string) $price->kind, $price->product_module)->where('effective_from', '>', $price->effective_from)->exists()) {
                throw ValidationException::withMessages(['price' => __('Withdraw the later price first.')]);
            }
            DB::table('platform_prices')->where('id', $priceId)->update(['withdrawn_at' => now(), 'withdrawn_by' => $actor->id, 'updated_at' => now()]);
            Audit::record('platform_package_price_withdrawn', null, ['package_id' => $id, 'price_id' => $priceId, 'withdrawn' => false], ['package_id' => $id, 'price_id' => $priceId, 'withdrawn' => true]);
            $closed = $price->closes_price_id === null ? null : DB::table('platform_prices')->where('id', $price->closes_price_id)->lockForUpdate()->first();
            $dayBefore = CarbonImmutable::createFromFormat('!Y-m-d', (string) $price->effective_from)->subDay()->toDateString();
            if ($closed !== null && $closed->withdrawn_at === null && $closed->effective_to === $dayBefore) {
                DB::table('platform_prices')->where('id', $closed->id)->update(['effective_to' => null, 'updated_at' => now()]);
                Audit::record('platform_package_price_ended', null, ['package_id' => $id, 'price_id' => (int) $closed->id, 'effective_to' => $dayBefore],
                    ['package_id' => $id, 'price_id' => (int) $closed->id, 'effective_to' => null]);
            }
        });
    }

    /**
     * Give every school on an earlier version of the package's module set the
     * current one, with a reason. Each school is audited; a money module the
     * new set leaves out stays readable (read-only) while the school has its
     * records, as for a package change; the school's apps refresh.
     */
    public function reapply(int $id, array $input, User $actor): int
    {
        $this->authorise($actor);
        $reason = Validator::make($input, self::reapplyRules())->validate()['reason'];

        return $this->write(function () use ($id, $reason, $actor): int {
            $row = $this->lock($id);
            $version = (int) $row->modules_version;
            $schools = $this->onPackage($row)->whereRaw('COALESCE(package_version, 1) < ?', [$version])->orderBy('id')->lockForUpdate()->get(['id', 'package_key', 'package_version']);
            foreach ($schools as $school) {
                DB::table('schools')->where('id', $school->id)->update(['package_version' => $version, 'updated_at' => now()]);
                Audit::record('platform_package_reapplied', School::query()->findOrFail($school->id),
                    ['package_id' => $id, 'package_key' => $school->package_key, 'package_version' => (int) ($school->package_version ?? 1)],
                    ['package_id' => $id, 'package_key' => $school->package_key, 'package_version' => $version, 'reason' => $reason, 'changed_by' => $actor->id], (int) $school->id);
                $this->currentSchool->run((int) $school->id, function () use ($actor): void {
                    app(ModuleGate::class)->keepRetainedMoneyReadable($actor);
                });
                Outbox::record(new ContextChanged((int) $school->id, 'modules'));
            }

            return $schools->count();
        });
    }

    /** The version a school put on this package now gets. Throws unless the package is active. */
    public function versionToAssign(string $key): int
    {
        $row = DB::table('platform_packages')->where('package_key', $key)->where('status', 'active')->first();
        if ($row === null) {
            throw ValidationException::withMessages(['package_key' => __('Choose one of the listed packages.')]);
        }

        return (int) $row->modules_version;
    }

    // ---- internals ----

    /** @param  list<string>  $products */
    public static function expand(array $products): array
    {
        $catalogue = (array) config('product_modules');

        return array_values(array_merge(...array_map(fn (string $key): array => $catalogue[$key]['modules'] ?? [], array_values($products))));
    }

    /** @return list<string> the chosen product modules plus the always-included ones, in catalogue order */
    private static function withAlwaysIncluded(array $chosen): array
    {
        return array_keys(array_filter((array) config('product_modules'), fn (array $product, string $key): bool => ($product['always_included'] ?? false) || in_array($key, $chosen, true), ARRAY_FILTER_USE_BOTH));
    }

    private function authorise(User $actor): void
    {
        abort_unless(app(PlatformStaffAccess::class)->can($actor, 'packages.manage'), 403);
    }

    private function write(callable $change): mixed
    {
        try {
            return $this->currentSchool->acrossSchools(fn () => DB::transaction(fn () => $change(), 3));
        } finally {
            $this->byKey = [];
            $this->versions = [];
            $this->jobSchools = [];
        }
    }

    private function lock(int $id): object
    {
        $row = DB::table('platform_packages')->where('id', $id)->lockForUpdate()->first();
        abort_if($row === null, 404);

        return $row;
    }

    private function refuseRetired(object $row): void
    {
        if ($row->status === 'retired') {
            throw ValidationException::withMessages(['package' => __('A retired package cannot be changed.')]);
        }
    }

    private function storeVersion(int $id, int $version, array $products, User $actor): void
    {
        foreach ($products as $product) {
            DB::table('platform_package_modules')->insert(['package_id' => $id, 'version' => $version, 'product_module' => $product, 'created_by' => $actor->id, 'created_at' => now()]);
        }
    }

    private function series(int $id, string $kind, ?string $module): Builder
    {
        return DB::table('platform_prices')->where('package_id', $id)->where('kind', $kind)->whereNull('withdrawn_at')
            ->when($module === null, fn ($q) => $q->whereNull('product_module'), fn ($q) => $q->where('product_module', $module));
    }

    /** Schools on the package; the legacy package also holds the schools with no package key. */
    private function onPackage(object $row): Builder
    {
        return DB::table('schools')->where(fn ($q) => $q->where('package_key', $row->package_key)
            ->when($row->package_key === self::LEGACY, fn ($q) => $q->orWhereNull('package_key')));
    }

    private function schoolsBehind(object $row, int $version): int
    {
        return $this->onPackage($row)->whereRaw('COALESCE(package_version, 1) < ?', [$version])->count();
    }

    /** @return array<string, array{all: int, behind: int}> per package key */
    private function schoolCounts(): array
    {
        $current = DB::table('platform_packages')->pluck('modules_version', 'package_key');
        $counts = [];
        foreach (DB::table('schools')->selectRaw('COALESCE(package_key, ?) AS package, COALESCE(package_version, 1) AS version, COUNT(*) AS schools', [self::LEGACY])
            ->groupByRaw('COALESCE(package_key, ?), COALESCE(package_version, 1)', [self::LEGACY])->get() as $row) {
            $key = (string) $row->package;
            $counts[$key] ??= ['all' => 0, 'behind' => 0];
            $counts[$key]['all'] += (int) $row->schools;
            if ((int) $row->version < (int) ($current[$key] ?? 1)) {
                $counts[$key]['behind'] += (int) $row->schools;
            }
        }

        return $counts;
    }
}
