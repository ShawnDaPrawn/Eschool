<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\WalletAccount;
use App\Models\Money\WalletLimit;
use App\Models\Money\WalletSetting;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Policies\Money\WalletPolicy;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/** Bounded panel projection; owner statements retain their private contract. */
final class WalletFinanceOverview
{
    public function read(User $actor, Collection $requested): array
    {
        return DB::transaction(function () use ($actor, $requested): array {
            $policy = app(WalletPolicy::class);
            $policy->finance($actor); // Same school serialization as money writes.
            abort_if($requested->count() > 30, 422);
            if ($requested->isEmpty()) {
                return [];
            }
            $school = app(CurrentSchool::class)->id();
            $wallets = WalletAccount::whereIn('id', $requested->modelKeys())->get();
            abort_unless($wallets->count() === $requested->count(), 404);
            $ids = $wallets->pluck('ledger_account_id');
            $accounts = DB::table('ledger_accounts')->where('school_id', $school)->whereIn('id', $ids)->where('type', 'wallet')->get()->keyBy('id');
            $totals = DB::table('ledger_postings as p')->join('ledger_entries as e', fn ($j) => $j->on('e.school_id', '=', 'p.school_id')->on('e.id', '=', 'p.entry_id'))
                ->where('p.school_id', $school)->whereIn('p.account_id', $ids)->whereNotNull('e.posted_at')
                ->selectRaw("p.account_id, SUM(CASE WHEN p.direction='credit' THEN p.cents ELSE -p.cents END) AS cents")
                ->groupBy('p.account_id')->pluck('cents', 'p.account_id');
            $settings = WalletSetting::firstOrFail();
            $limits = WalletLimit::whereIn('wallet_account_id', $wallets->modelKeys())->get()->keyBy('wallet_account_id');
            $users = User::withTrashed()->whereIn('id', $wallets->pluck('user_id'))->get()->keyBy('id');
            $memberships = SchoolMembership::active()->where('school_id', $school)->whereIn('user_id', $users->modelKeys())->lockForUpdate()->get()->groupBy('user_id');
            $roles = DB::table('model_has_roles as mr')->join('roles as r', 'r.id', '=', 'mr.role_id')
                ->where('mr.school_id', $school)->where('r.school_id', $school)->whereIn('mr.model_id', $users->modelKeys())
                ->where('mr.model_type', $actor->getMorphClass())->lockForUpdate()->get(['mr.model_id', 'r.name'])->groupBy('model_id');
            $learners = Students::whereIn('user_id', $users->modelKeys())->pluck('user_id')->all();
            $adults = SchoolMembership::where('school_id', $school)->whereIn('user_id', $users->modelKeys())
                ->whereIn('role', ['Parent', 'Teacher', 'Facilitator', 'Super Admin', 'Vendor'])->lockForUpdate()->pluck('user_id')->all();
            $rows = [];
            foreach ($wallets as $wallet) {
                $account = $accounts->get($wallet->ledger_account_id);
                $balance = filter_var($totals[$wallet->ledger_account_id] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => LedgerService::MAX_CENTS]]);
                abort_unless($account !== null && $account->normal_direction === 'credit' && $account->currency === $wallet->currency
                    && (int) $account->owner_user_id === (int) $wallet->user_id && $balance !== false && (int) $account->balance_cents === $balance, 409, 'Wallet balance needs reconciliation before continuing.');
                $person = $users->get($wallet->user_id);
                abort_unless($person !== null, 409, 'Wallet owner needs reconciliation.');
                $currentRoles = array_intersect($roles->get($wallet->user_id, collect())->pluck('name')->all(), $memberships->get($wallet->user_id, collect())->pluck('role')->all());
                $child = in_array('Student', $currentRoles, true) || ($currentRoles === [] && in_array($wallet->user_id, $learners) && ! in_array($wallet->user_id, $adults));
                $limit = $limits->get($wallet->id);
                $cap = $child && $settings->child_limits_required && $limit === null ? 0 : min($settings->daily_spend_cents, $limit->daily_spend_cents ?? $settings->daily_spend_cents);
                $rows[(int) $wallet->id] = ['owner_name' => trim($person->first_name.' '.$person->last_name), 'balance_cents' => $balance, 'limits' => ['effective_daily_spend_cents' => $cap]];
            }
            $policy->finance($actor);

            return $rows;
        });
    }
}
