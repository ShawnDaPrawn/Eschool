<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\WalletAccount;
use App\Models\Money\WalletDeskAction;
use App\Models\Money\WalletDeskPolicy;
use App\Models\Money\WalletDeskTopup;
use App\Models\Money\WalletSetting;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Policies\Money\WalletPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class WalletDeskTopups
{
    public function policy(User $actor, array $input): WalletDeskPolicy
    {
        $data = Validator::make($input, self::policyRules())->validate();
        $data['revision'] = (int) $data['revision'];
        $data['threshold_cents'] = Money::fromDecimalString($data['threshold_amount'])->cents;
        unset($data['threshold_amount']);
        abort_if($data['threshold_cents'] > 100000000000, 422, 'Use a supported desk approval threshold.');
        $receipt = app(WalletOperations::class)->once($actor, 'wallet_desk_policy', $data, fn () => app(WalletPolicy::class)->deskPolicy($actor, true), function () use ($actor, $data): array {
            $currency = WalletSetting::lockForUpdate()->first()?->currency;
            abort_unless($currency !== null, 422, 'Configure wallet currency first.');
            $row = WalletDeskPolicy::lockForUpdate()->first() ?? new WalletDeskPolicy;
            abort_unless((int) ($row->revision ?? 0) === $data['revision'], 409, 'Desk approval policy changed. Review its current rule.');
            $row->fill(['currency' => $currency, 'threshold_cents' => $data['threshold_cents'], 'revision' => $data['revision'] + 1, 'changed_by' => $actor->id])->save();
            Audit::record('wallet.desk_policy_saved', $row, [], ['revision' => $row->revision, 'currency' => $currency, 'threshold_cents' => $row->threshold_cents]);

            return ['id' => (int) $row->id];
        });

        return WalletDeskPolicy::findOrFail($receipt['id']);
    }

    public function grant(User $actor, array $input): WalletDeskTopup
    {
        $data = Validator::make($input, self::grantRules())->validate();
        $data['owner_user_id'] = (int) $data['owner_user_id'];
        $data['amount_cents'] = Money::fromDecimalString($data['amount'])->cents;
        $data['confirmed'] = true;
        unset($data['amount']);
        abort_unless($data['amount_cents'] > 0 && $data['amount_cents'] <= 100000000000, 422, 'Enter a positive supported desk top-up.');
        $authority = function () use ($actor, $data): void {
            app(WalletPolicy::class)->desk($actor, true);
            $this->person($data['owner_user_id']);
        };
        $receipt = app(WalletOperations::class)->once($actor, 'wallet_desk_grant', $data, $authority, function () use ($actor, $data): array {
            $wallet = app(Wallets::class)->deskOpen($actor, $data['owner_user_id']);
            $policy = $this->currentPolicy($wallet->currency);
            $master = DB::table('ledger_accounts')->where('school_id', app(CurrentSchool::class)->id())->where('reference', 'wallet:master:'.$wallet->currency)->lockForUpdate()->first();
            abort_unless($master !== null, 422, 'Fund the school master wallet before a desk top-up.');
            $masterId = (int) $master->id;
            $ledger = app(LedgerService::class);
            $available = $this->soundBalance($masterId);
            abort_unless($available >= $data['amount_cents'], 422, 'The master wallet does not cover this top-up.');
            $reserve = $ledger->openAccount(AccountType::Wallet, 'wallet:desk-reserve:'.$data['operation_id'], $wallet->currency);
            $this->capacity($reserve, $data['amount_cents']);
            $source = 'wallet-desk:reserve:'.$data['operation_id'];
            $money = Money::ofCents($data['amount_cents'], $wallet->currency);
            $entry = $ledger->post($source, $source, 'Reserved school desk top-up', [Posting::debit($masterId, $money), Posting::credit($reserve, $money)]);
            $row = WalletDeskTopup::create(['operation_id' => $data['operation_id'], 'wallet_account_id' => $wallet->id, 'owner_user_id' => $wallet->user_id, 'currency' => $wallet->currency, 'amount_cents' => $data['amount_cents'], 'master_account_id' => $masterId, 'reserve_account_id' => $reserve, 'reserve_entry_id' => $entry, 'policy_revision' => $policy->revision, 'threshold_cents' => $policy->threshold_cents, 'requires_second_approval' => $data['amount_cents'] > $policy->threshold_cents, 'requested_by' => $actor->id, 'reason' => $data['reason'], 'created_at' => now('UTC')]);
            if (! $row->requires_second_approval) {
                $this->postTerminal($actor, $row, 'released', null);
            }
            Audit::record('wallet.desk_topup_reserved', $row, [], ['amount_cents' => $row->amount_cents, 'requires_second_approval' => $row->requires_second_approval]);

            return ['id' => (int) $row->id];
        });

        return WalletDeskTopup::findOrFail($receipt['id']);
    }

    public function finish(User $actor, int $id, string $action, array $input): WalletDeskTopup
    {
        abort_unless(in_array($action, ['released', 'cancelled'], true), 422);
        $data = Validator::make($input, self::actionRules($action))->validate();
        $data['confirmed'] = true;
        $data['note'] = $data['note'] ?? null;
        $data['topup_id'] = $id;
        $data['action'] = $action;
        $authority = function () use ($actor, $id, $action): void {
            $this->authorizeAction($actor, WalletDeskTopup::lockForUpdate()->findOrFail($id), $action);
        };
        $receipt = app(WalletOperations::class)->once($actor, 'wallet_desk_action', $data, $authority, function () use ($actor, $id, $action, $data): array {
            $row = WalletDeskTopup::lockForUpdate()->findOrFail($id);
            $this->postTerminal($actor, $row, $action, $data['note']);

            return ['id' => (int) $row->id];
        });

        return WalletDeskTopup::findOrFail($receipt['id']);
    }

    private function authorizeAction(User $actor, WalletDeskTopup $row, string $action): void
    {
        app(WalletPolicy::class)->desk($actor, true);
        $policy = $this->currentPolicy($row->currency);
        if ($action === 'released') {
            $this->person($row->owner_user_id);
            $second = $row->requires_second_approval || $row->amount_cents > $policy->threshold_cents;
            if ($second) {
                abort_if((int) $actor->id === $row->requested_by, 403, 'A different current Finance approver must confirm this top-up.');
                $requester = User::whereKey($row->requested_by)->lockForUpdate()->firstOrFail();
                app(WalletPolicy::class)->desk($requester, true);
            }
        }
    }

    private function postTerminal(User $actor, WalletDeskTopup $row, string $action, ?string $note): void
    {
        $this->authorizeAction($actor, $row, $action);
        abort_if(WalletDeskAction::where('topup_id', $row->id)->lockForUpdate()->exists(), 409, 'This desk top-up already has a final outcome.');
        $policy = $this->currentPolicy($row->currency);
        $wallet = WalletAccount::lockForUpdate()->findOrFail($row->wallet_account_id);
        $target = $action === 'released' ? $wallet->ledger_account_id : $row->master_account_id;
        abort_unless($this->soundBalance($row->reserve_account_id) === $row->amount_cents, 409, 'Reconcile the pending desk top-up before continuing.');
        $this->capacity($target, $row->amount_cents);
        $source = 'wallet-desk:'.$action.':'.$row->id;
        $money = Money::ofCents($row->amount_cents, $row->currency);
        $entry = app(LedgerService::class)->post($source, $source, $action === 'released' ? 'School desk top-up' : 'Cancelled school desk reservation', [Posting::debit($row->reserve_account_id, $money), Posting::credit($target, $money)]);
        $second = $action === 'released' && ($row->requires_second_approval || $row->amount_cents > $policy->threshold_cents);
        WalletDeskAction::create(['topup_id' => $row->id, 'action' => $action, 'actor_id' => $actor->id, 'ledger_entry_id' => $entry, 'policy_revision' => $policy->revision, 'threshold_cents' => $policy->threshold_cents, 'requires_second_approval' => $second, 'note' => $note, 'created_at' => now('UTC')]);
        $this->authorizeAction($actor, $row, $action);
        Audit::record('wallet.desk_topup_'.$action, $row, [], ['amount_cents' => $row->amount_cents, 'ledger_entry_id' => $entry, 'requires_second_approval' => $second]);
    }

    private function person(int $id): User
    {
        $subject = User::whereIn('id', SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->select('user_id'))->lockForUpdate()->findOrFail($id);
        app(WalletPolicy::class)->activePerson($subject);

        return $subject;
    }

    private function currentPolicy(string $currency): WalletDeskPolicy
    {
        $policy = WalletDeskPolicy::lockForUpdate()->first();
        abort_unless($policy !== null && $policy->currency === $currency, 422, 'Review the desk approval threshold for the current wallet currency first.');

        return $policy;
    }

    private function soundBalance(int $id): int
    {
        $actual = app(LedgerService::class)->balance($id)->cents;
        $cached = DB::table('ledger_accounts')->where('school_id', app(CurrentSchool::class)->id())->where('id', $id)->lockForUpdate()->value('balance_cents');
        abort_unless($cached !== null && (int) $cached === $actual, 409, 'Reconcile wallet balances before continuing.');

        return $actual;
    }

    private function capacity(int $id, int $amount): void
    {
        abort_if($amount > LedgerService::MAX_CENTS - $this->soundBalance($id), 422, 'This top-up would exceed the supported wallet balance.');
    }

    public function financeReceipt(User $actor, int $id): array
    {
        return DB::transaction(function () use ($actor, $id): array {
            app(WalletPolicy::class)->desk($actor);
            $row = WalletDeskTopup::findOrFail($id);
            $wallet = WalletAccount::findOrFail($row->wallet_account_id);

            return ['receipt' => $this->receipt($row), 'owner' => app(Wallets::class)->summary($wallet), 'reason' => $row->reason, 'topup' => $row];
        });
    }

    public function receipts(User $actor, int $walletId, int $page = 1, int $perPage = 30): array
    {
        abort_unless($page >= 1 && $page <= 1000000 && $perPage >= 1 && $perPage <= 50, 422);

        return DB::transaction(function () use ($actor, $walletId, $page, $perPage): array {
            app(WalletPolicy::class)->actor($actor);
            $wallet = WalletAccount::lockForUpdate()->findOrFail($walletId);
            app(WalletPolicy::class)->owner($actor, $wallet->user_id);
            $query = WalletDeskTopup::where('wallet_account_id', $walletId);
            $total = (clone $query)->count();
            $rows = $query->orderByDesc('id')->offset(($page - 1) * $perPage)->limit($perPage)->get();

            return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id, 'wallet_id' => $walletId, 'owner_user_id' => $wallet->user_id, 'currency' => $wallet->currency, 'read_only' => ! app(ModuleGate::class)->writable('wallet'), 'current_page' => $page, 'per_page' => $perPage, 'total' => $total, 'items' => $rows->map(fn ($row) => $this->receipt($row))->all()];
        });
    }

    private function receipt(WalletDeskTopup $row): array
    {
        $action = WalletDeskAction::where('topup_id', $row->id)->first();

        return ['id' => (int) $row->id, 'reference' => 'WT-'.$row->id, 'currency' => $row->currency, 'amount_cents' => $row->amount_cents, 'status' => $action->action ?? 'pending', 'created_at' => CarbonImmutable::parse($row->created_at)->utc()->toISOString(), 'processed_at' => $action ? CarbonImmutable::parse($action->created_at)->utc()->toISOString() : null, 'collected_by' => 'school', 'fee_cents' => 0, 'net_cents' => $row->amount_cents];
    }

    public static function policyRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'threshold_amount' => FeePayments::rules()['amount']];
    }

    public static function grantRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'owner_user_id' => ['required', 'integer', 'min:1'], 'amount' => FeePayments::rules()['amount'], 'reason' => ['required', 'string', 'min:10', 'max:2000'], 'confirmed' => ['required', 'accepted']];
    }

    public static function actionRules(string $action): array
    {
        return ['operation_id' => ['required', 'uuid'], 'confirmed' => ['required', 'accepted'], 'note' => [$action === 'cancelled' ? 'required' : 'nullable', 'string', 'min:10', 'max:2000']];
    }
}
