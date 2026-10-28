<?php

declare(strict_types=1);

namespace App\Services\Platform\Modules;

use App\Models\Platform\School;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\RequestContext;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ModuleGate
{
    private array $jobRows = [];

    /** Cache on the HTTP request, never on a long-lived worker or global singleton. */
    private function rows(bool $locked = false): array
    {
        $id = app(CurrentSchool::class)->id();
        if ($locked) {
            // A money domain may already hold a REPEATABLE READ snapshot.
            // Current locked state, not that snapshot, decides a new write.
            return SchoolModule::query()->where('school_id', $id)->lockForUpdate()->get()->keyBy('module')->all();
        }
        $cacheKey = 'modules.school.'.$id;
        if (RequestContext::inHttpRequest() && request()->attributes->has($cacheKey)) {
            return request()->attributes->get($cacheKey);
        }
        if (! RequestContext::inHttpRequest() && isset($this->jobRows[$id])) {
            return $this->jobRows[$id];
        }
        $rows = SchoolModule::query()->where('school_id', $id)->get()->keyBy('module')->all();
        request()->attributes->set($cacheKey, $rows);

        return $this->jobRows[$id] = $rows;
    }

    public function forget(): void
    {
        unset($this->jobRows[app(CurrentSchool::class)->id()]);
        request()->attributes->remove('modules.school.'.app(CurrentSchool::class)->id());
    }

    /** @return array{enabled: bool, read_only: bool, in_package: bool} */
    public function state(string $key, array $visited = [], bool $locked = false): array
    {
        $off = ['enabled' => false, 'read_only' => false, 'in_package' => false];
        $definition = config('modules.'.$key);
        if (! is_array($definition) || in_array($key, $visited, true)) {
            return $off;
        }
        if ($definition['always_on'] ?? false) {
            return ['enabled' => true, 'read_only' => false, 'in_package' => true];
        }
        $school = $locked
            ? School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id())
            : app(CurrentSchool::class)->school();
        if ($locked && $school?->status !== School::ACTIVE) {
            return $off;
        }
        $inPackage = in_array($key, config('school_packages.'.($school?->package_key ?? 'legacy'), []), true);
        $row = $this->rows($locked)[$key] ?? null;
        // A new school without rows still gets secure defaults. No implicit
        // entitlement is granted by enabling a switch in the database.
        $entitled = $inPackage && ($row?->in_package ?? true);
        $default = $school?->package_key === null ? ($definition['legacy'] ?? $definition['default'] ?? false) : ($definition['default'] ?? false);
        $enabled = $entitled && ($row?->enabled ?? $default);
        $readOnly = $row?->read_only ?? false;
        foreach ($definition['dependencies'] ?? [] as $dependency) {
            $state = $this->state($dependency, [...$visited, $key], $locked);
            $enabled = $enabled && $state['enabled'];
            $readOnly = $readOnly || $state['read_only'];
        }

        // Retained financial data stays readable even after entitlement ends.
        return ['enabled' => $enabled || ($readOnly && ($definition['money'] ?? false)), 'read_only' => $readOnly || ! $entitled, 'in_package' => $entitled];
    }

    public function allows(string $key): bool
    {
        return $this->state($key)['enabled'];
    }

    public function writable(string $key, bool $locked = false): bool
    {
        $state = $this->state($key, [], $locked);

        return $state['enabled'] && ! $state['read_only'];
    }

    /** @return list<array{key: string, read_only: bool}> */
    public function context(): array
    {
        $answer = [];
        foreach (array_keys(config('modules', [])) as $key) {
            $state = $this->state($key);
            if ($state['enabled']) {
                $answer[] = ['key' => $key, 'read_only' => $state['read_only']];
            }
        }

        return $answer;
    }

    public function change(string $key, bool $enabled, User $actor): array
    {
        return DB::transaction(function () use ($key, $enabled, $actor) {
            $school = School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $definition = config('modules.'.$key);
            abort_unless(is_array($definition), 404);
            if ($definition['always_on'] ?? false) {
                throw ValidationException::withMessages(['module' => 'This module is required and stays on.']);
            }
            $this->forget();
            $state = $this->state($key);
            if ($enabled && ! $state['in_package']) {
                throw ValidationException::withMessages(['module' => 'This module is not included in the school package.']);
            }
            if ($enabled) {
                foreach ($definition['dependencies'] ?? [] as $dependency) {
                    if (! $this->writable($dependency)) {
                        throw ValidationException::withMessages(['module' => 'Enable the required module first: '.$dependency]);
                    }
                }
            } else {
                foreach (config('modules') as $dependent => $other) {
                    if (in_array($key, $other['dependencies'] ?? [], true) && $this->allows($dependent)) {
                        throw ValidationException::withMessages(['module' => 'Switch off the dependent module first: '.$dependent]);
                    }
                }
            }
            $readOnly = ! $enabled && ($definition['money'] ?? false) && $this->hasRetainedMoney($school->id, $key);
            SchoolModule::query()->updateOrCreate(['module' => $key], [
                'in_package' => $state['in_package'], 'enabled' => $enabled || $readOnly, 'read_only' => $readOnly,
                'changed_by' => $actor->id, 'changed_at' => now(),
            ]);
            $this->forget();

            Outbox::record(new ContextChanged((int) $school->id, 'modules'));

            return $this->state($key);
        });
    }

    /**
     * After a package change: a money module the package no longer includes
     * stays readable, never writable, while the school has retained records,
     * as when its own admin switches it off. A module already read-only, or
     * never used, is left as it is. Runs in the school whose package changed.
     */
    public function keepRetainedMoneyReadable(User $actor): void
    {
        $schoolId = app(CurrentSchool::class)->id();
        $this->forget();
        foreach (config('modules') as $key => $definition) {
            if (! ($definition['money'] ?? false)) {
                continue;
            }
            $state = $this->state($key);
            if ($state['in_package'] || $state['enabled'] || ! $this->hasRetainedMoney($schoolId, $key)) {
                continue;
            }
            // in_package stays as it is: the package config decides entitlement, and a later upgrade must still count.
            $row = SchoolModule::query()->firstOrNew(['module' => $key]);
            if (! $row->exists) {
                $row->in_package = true;
            }
            $row->fill(['enabled' => true, 'read_only' => true, 'changed_by' => $actor->id, 'changed_at' => now()])->save();
        }
        $this->forget();
    }

    private function hasRetainedMoney(int $schoolId, string $module): bool
    {
        // Preserve financial history as well as outstanding charges. These
        // integer-cents records are never removed by a switch operation.
        foreach ($module === 'fees' ? ['fees_classes', 'fees_paids', 'payment_transactions'] : [] as $table) {
            if (DB::table($table)->where('school_id', $schoolId)->exists()) {
                return true;
            }
        }

        // Parent agreements and reviewed reductions also remain readable before
        // the first ledger posting. Disabling a module does not hide legal history.
        if ($module === 'fees') {
            foreach (['fee_item_consents', 'fee_discounts', 'fee_discount_runs', 'fee_lifecycle_policies', 'fee_leaver_policies', 'fee_leaver_runs', 'fee_refund_policies', 'fee_refunds', 'fee_refund_actions'] as $table) {
                if (Schema::hasTable($table) && DB::table($table)->where('school_id', $schoolId)->exists()) {
                    return true;
                }
            }
        }

        if ($module === 'wallet') {
            foreach (['wallet_settings', 'wallet_limits', 'wallet_operations', 'wallet_funding_policies', 'wallet_fundings', 'wallet_desk_policies', 'wallet_desk_topups', 'wallet_desk_actions'] as $table) {
                if (Schema::hasTable($table) && DB::table($table)->where('school_id', $schoolId)->exists()) {
                    return true;
                }
            }
        }

        // Canonical history remains readable even after a wallet is emptied.
        // Opening an unused zero account alone does not retain a module.
        return DB::table('ledger_accounts as a')->where('a.school_id', $schoolId)->where('a.module', $module)
            ->where(fn ($query) => $query->where('a.balance_cents', '<>', 0)->orWhereExists(fn ($postings) => $postings
                ->selectRaw('1')->from('ledger_postings as p')->whereColumn('p.school_id', 'a.school_id')->whereColumn('p.account_id', 'a.id')))
            ->exists();
    }
}
