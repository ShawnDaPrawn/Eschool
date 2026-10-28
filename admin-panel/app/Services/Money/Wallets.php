<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\WalletAccount;
use App\Models\Money\WalletLimit;
use App\Models\Money\WalletSetting;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Policies\Money\WalletPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class Wallets
{
    public function open(User $actor, array $input): WalletAccount
    {
        $data = Validator::make($input, self::openRules())->validate();
        $data['owner_user_id'] = (int) $data['owner_user_id'];
        $receipt = app(WalletOperations::class)->once($actor, 'wallet_open', $data, fn () => app(WalletPolicy::class)->owner($actor, $data['owner_user_id'], true), function () use ($data): array {
            return ['id' => (int) $this->issue($data['owner_user_id'])->id];
        });

        return WalletAccount::findOrFail($receipt['id']);
    }

    /** Internal Finance provisioning for a desk grant; personal API ownership is unchanged. */
    public function deskOpen(User $actor, int $owner): WalletAccount
    {
        return DB::transaction(function () use ($actor, $owner): WalletAccount {
            $policy = app(WalletPolicy::class);
            $policy->desk($actor, true);
            $subject = User::whereIn('id', SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->select('user_id'))->lockForUpdate()->findOrFail($owner);
            $policy->activePerson($subject);
            $wallet = $this->issue($owner);
            $policy->desk($actor, true);
            $policy->activePerson(User::whereKey($owner)->lockForUpdate()->firstOrFail());

            return $wallet;
        }, 3);
    }

    private function issue(int $owner): WalletAccount
    {
        $settings = WalletSetting::lockForUpdate()->first();
        abort_unless($settings !== null, 422, 'Finance must configure the school wallet currency and spending rules first.');
        $prior = WalletAccount::where('user_id', $owner)->where('currency', $settings->currency)->lockForUpdate()->first();
        if ($prior !== null) {
            return $prior;
        }
        $ledger = app(LedgerService::class)->openAccount(AccountType::Wallet, 'wallet:person:'.$owner.':'.$settings->currency, $settings->currency, $owner);
        $wallet = WalletAccount::create(['user_id' => $owner, 'ledger_account_id' => $ledger, 'currency' => $settings->currency, 'created_at' => now('UTC')]);
        Audit::record('wallet.account_opened', $wallet, [], ['owner_user_id' => $owner]);

        return $wallet;
    }

    public function controls(User $actor, int $id, array $input): WalletLimit
    {
        $data = Validator::make($input, self::limitRules())->validate();
        $data['daily_spend_cents'] = (int) $data['daily_spend_cents'];
        $data['revision'] = (int) $data['revision'];
        $data['all_categories'] = (bool) $data['all_categories'];
        $data['categories'] = array_values(array_unique($data['categories'] ?? []));
        sort($data['categories'], SORT_STRING);
        $data['wallet_id'] = $id;
        abort_unless(! $data['all_categories'] || $data['categories'] === [], 422, 'Choose all categories or an explicit permitted list.');
        $authorize = fn () => app(WalletPolicy::class)->limits($actor, WalletAccount::lockForUpdate()->findOrFail($id), true);
        $receipt = app(WalletOperations::class)->once($actor, 'wallet_limits', $data, $authorize, function () use ($actor, $id, $data): array {
            $settings = WalletSetting::lockForUpdate()->firstOrFail();
            abort_unless($data['daily_spend_cents'] <= $settings->daily_spend_cents, 422, 'Personal limits may not exceed the current school daily spending cap.');
            $limits = WalletLimit::where('wallet_account_id', $id)->lockForUpdate()->first() ?? new WalletLimit(['wallet_account_id' => $id]);
            abort_unless((int) ($limits->revision ?? 0) === $data['revision'], 409, 'Wallet controls changed. Review them before saving.');
            $limits->fill(['daily_spend_cents' => $data['daily_spend_cents'], 'all_categories' => $data['all_categories'], 'categories' => $data['categories'], 'revision' => $data['revision'] + 1, 'changed_by' => $actor->id])->save();
            Audit::record('wallet.limits_saved', $limits, [], ['revision' => $limits->revision, 'daily_spend_cents' => $limits->daily_spend_cents]);

            return ['id' => (int) $limits->id];
        });

        return WalletLimit::findOrFail($receipt['id']);
    }

    public function accessible(User $actor): array
    {
        return DB::transaction(function () use ($actor): array {
            $policy = app(WalletPolicy::class);
            $fresh = $policy->actor($actor);
            $ids = [];
            if (array_intersect($policy->roles($fresh), WalletPolicy::PERSONAL_ROLES) !== []) {
                $ids[] = (int) $fresh->id;
            }
            if (in_array('Parent', $policy->roles($fresh), true) && $fresh->email_verified_at !== null) {
                $ids = [...$ids, ...GuardianLink::join('students as s', fn ($j) => $j->on('s.school_id', '=', 'guardian_links.school_id')->on('s.id', '=', 'guardian_links.learner_id'))->where('guardian_links.guardian_user_id', $fresh->id)->where('guardian_links.status', 'active')->where('guardian_links.can_approve', true)->whereNull('s.deleted_at')->pluck('s.user_id')->map(fn ($id) => (int) $id)->all()];
            }
            $settings = WalletSetting::first();
            $rows = [];
            foreach (User::whereIn('id', array_unique($ids))->orderBy('id')->lockForUpdate()->get() as $subject) {
                $policy->owner($fresh, (int) $subject->id);
                $wallet = WalletAccount::where('user_id', $subject->id)->first();
                $rows[] = ['owner_user_id' => (int) $subject->id, 'owner_name' => trim($subject->first_name.' '.$subject->last_name), 'is_self' => (int) $subject->id === (int) $fresh->id, 'can_open' => $settings !== null && app(ModuleGate::class)->writable('wallet') && $this->canOpen($subject), 'wallets' => $wallet ? [$this->summary($wallet) + ['can_manage_limits' => $this->canManage($fresh, $wallet)]] : []];
            }

            return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $fresh->id, 'currency' => WalletSetting::first()?->currency, 'owners' => $rows];
        });
    }

    public function statement(User $actor, int $id, int $page = 1, int $perPage = 30): array
    {
        $this->paging($page, $perPage);

        return DB::transaction(function () use ($actor, $id, $page, $perPage): array {
            app(WalletPolicy::class)->actor($actor);
            $wallet = WalletAccount::lockForUpdate()->findOrFail($id);
            app(WalletPolicy::class)->owner($actor, (int) $wallet->user_id);

            return $this->projection($wallet, $page, $perPage, $this->canManage($actor, $wallet));
        });
    }

    public function financeStatement(User $actor, int $id, int $page = 1, int $perPage = 30): array
    {
        $this->paging($page, $perPage);

        return DB::transaction(function () use ($actor, $id, $page, $perPage): array {
            app(WalletPolicy::class)->finance($actor);
            $wallet = WalletAccount::lockForUpdate()->findOrFail($id);

            return $this->projection($wallet, $page, $perPage, false);
        });
    }

    private function paging(int $page, int $perPage): void
    {
        abort_unless($page >= 1 && $page <= 1000000 && $perPage >= 1 && $perPage <= 50, 422);
    }

    private function projection(WalletAccount $wallet, int $page, int $perPage, bool $canManage): array
    {
        $school = app(CurrentSchool::class)->id();
        $query = DB::table('ledger_postings as p')->join('ledger_entries as e', fn ($j) => $j->on('e.school_id', '=', 'p.school_id')->on('e.id', '=', 'p.entry_id'))->where('p.school_id', $school)->where('p.account_id', $wallet->ledger_account_id)->whereNotNull('e.posted_at')->selectRaw("e.id,e.posted_at,SUM(CASE WHEN p.direction='credit' THEN p.cents ELSE -p.cents END) AS change_cents")->groupBy('e.id', 'e.posted_at');
        $movements = DB::query()->fromSub($query, 'm')->selectRaw('m.*,SUM(change_cents) OVER (ORDER BY posted_at,id ROWS UNBOUNDED PRECEDING) AS running_cents');
        $total = DB::query()->fromSub($query, 'n')->count();
        $rows = DB::query()->fromSub($movements, 's')->orderByDesc('posted_at')->orderByDesc('id')->offset(($page - 1) * $perPage)->limit($perPage)->get()->map(fn ($r) => ['entry_id' => (int) $r->id, 'posted_at' => CarbonImmutable::parse((string) $r->posted_at, 'UTC')->format('Y-m-d\TH:i:s.u\Z'), 'change_cents' => (int) $r->change_cents, 'balance_after_cents' => (int) $r->running_cents, 'description' => (int) $r->change_cents >= 0 ? 'Wallet credit' : 'Wallet debit'])->all();

        return $this->summary($wallet) + ['can_manage_limits' => $canManage, 'entries' => ['items' => $rows, 'current_page' => $page, 'per_page' => $perPage, 'total' => $total]];
    }

    public function summary(WalletAccount $wallet): array
    {
        $balance = app(LedgerService::class)->balance($wallet->ledger_account_id)->cents;
        $school = app(CurrentSchool::class)->id();
        $raw = (int) DB::table('ledger_postings as p')->join('ledger_entries as e', fn ($j) => $j->on('e.school_id', '=', 'p.school_id')->on('e.id', '=', 'p.entry_id'))->where('p.school_id', $school)->where('p.account_id', $wallet->ledger_account_id)->whereNotNull('e.posted_at')->selectRaw("COALESCE(SUM(CASE WHEN p.direction='credit' THEN p.cents ELSE -p.cents END),0) AS cents")->value('cents');
        abort_unless($raw === $balance && $balance >= 0, 409, 'Wallet balance needs reconciliation before continuing.');
        $settings = WalletSetting::firstOrFail();
        $limit = WalletLimit::where('wallet_account_id', $wallet->id)->first();
        $subject = User::withTrashed()->findOrFail($wallet->user_id);
        $child = Students::where('user_id', $subject->id)->exists();
        $gate = app(ModuleGate::class);

        return ['id' => (int) $wallet->id, 'school_id' => $school, 'owner_user_id' => $wallet->user_id, 'owner_name' => trim($subject->first_name.' '.$subject->last_name), 'currency' => $wallet->currency, 'balance_cents' => $balance, 'is_child' => $child, 'read_only' => ! $gate->writable('wallet'), 'school_daily_spend_cents' => $settings->daily_spend_cents, 'child_limits_required' => $settings->child_limits_required, 'limits' => ['revision' => $limit->revision ?? 0, 'daily_spend_cents' => $limit?->daily_spend_cents, 'effective_daily_spend_cents' => ($child && $settings->child_limits_required && $limit === null) ? 0 : min($settings->daily_spend_cents, $limit->daily_spend_cents ?? $settings->daily_spend_cents), 'all_categories' => $limit->all_categories ?? true, 'categories' => $limit->categories ?? [], 'configured' => $limit !== null]];
    }

    private function canOpen(User $subject): bool
    {
        if ((int) $subject->status !== 1 || $subject->deleted_at !== null || array_intersect(app(WalletPolicy::class)->roles($subject), WalletPolicy::PERSONAL_ROLES) === []) {
            return false;
        }
        if (in_array('Student', app(WalletPolicy::class)->roles($subject), true)) {
            $student = Students::where('user_id', $subject->id)->first();

            return $student !== null && app(ApprovalService::class)->approved($student);
        }

        return true;
    }

    private function canManage(User $actor, WalletAccount $wallet): bool
    {
        if (! app(ModuleGate::class)->writable('wallet')) {
            return false;
        }$subject = User::withTrashed()->findOrFail($wallet->user_id);

        return $this->canOpen($subject) && ((int) $actor->id !== (int) $wallet->user_id || ! Students::where('user_id', $actor->id)->exists());
    }

    public static function openRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'owner_user_id' => ['required', 'integer', 'min:1']];
    }

    public static function limitRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'daily_spend_cents' => ['required', 'integer', 'min:0', 'max:100000000000'], 'all_categories' => ['required', 'boolean'], 'categories' => ['sometimes', 'array', 'max:50'], 'categories.*' => ['required', 'string', 'regex:/\A[a-z][a-z0-9_-]{0,39}\z/D', 'distinct']];
    }
}
