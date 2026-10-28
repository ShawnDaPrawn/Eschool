<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\WalletAccount;
use App\Models\Money\WalletDepositAction;
use App\Models\Money\WalletDepositClaim;
use App\Models\Money\WalletDepositPolicy;
use App\Models\Money\WalletParentDeposit;
use App\Models\Money\WalletSetting;
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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Parent-owned incoming money never becomes school master funding. */
final class WalletDeposits
{
    public function policy(User $actor, array $input): WalletDepositPolicy
    {
        $data = Validator::make($input, self::policyRules())->validate();
        $data['revision'] = (int) $data['revision'];
        $data['cash_allowed'] = (bool) $data['cash_allowed'];
        $data['eft_allowed'] = (bool) $data['eft_allowed'];
        $receipt = app(WalletOperations::class)->once($actor, 'wallet_parent_deposit_policy', $data, fn () => app(WalletPolicy::class)->finance($actor, true), function () use ($actor, $data): array {
            abort_unless(WalletSetting::lockForUpdate()->first() !== null, 422, 'Configure wallet currency first.');
            $row = WalletDepositPolicy::lockForUpdate()->first() ?? new WalletDepositPolicy;
            abort_unless((int) ($row->revision ?? 0) === $data['revision'], 409, 'Parent deposit methods changed. Review the current policy.');
            $row->fill(['cash_allowed' => $data['cash_allowed'], 'eft_allowed' => $data['eft_allowed'], 'revision' => $data['revision'] + 1, 'changed_by' => $actor->id])->save();
            Audit::record('wallet.parent_deposit_policy_saved', $row, [], ['cash_allowed' => $row->cash_allowed, 'eft_allowed' => $row->eft_allowed, 'revision' => $row->revision]);

            return ['id' => (int) $row->id];
        });

        return WalletDepositPolicy::findOrFail($receipt['id']);
    }

    public static function policyRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'cash_allowed' => ['required', 'boolean'], 'eft_allowed' => ['required', 'boolean']];
    }

    public function parent(User $actor, bool $write = false): User
    {
        $policy = app(WalletPolicy::class);
        $fresh = $policy->actor($actor, $write);
        $roles = $policy->roles($fresh);
        abort_unless(in_array('Parent', $roles, true) && ! in_array('Student', $roles, true) && $fresh->email_verified_at !== null, 403, 'Only a verified parent may request a personal wallet deposit.');

        return $fresh;
    }

    public function claim(User $actor, array $input): WalletDepositClaim
    {
        $data = $this->validated($input, self::claimRules());
        $data['confirmed'] = true;
        $receipt = app(WalletOperations::class)->once($actor, 'wallet_deposit_claim', $data, fn () => $this->parent($actor, true), function () use ($actor, $data): array {
            $policy = $this->methodPolicy($data['method']);
            $this->pastDate($data['paid_on']);
            $wallet = app(Wallets::class)->open($actor, ['operation_id' => (string) Str::uuid(), 'owner_user_id' => $actor->id]);
            $row = WalletDepositClaim::create(['actor_id' => $actor->id, 'operation_id' => $data['operation_id'], 'wallet_account_id' => $wallet->id, 'currency' => $wallet->currency, 'requested_cents' => $data['amount_cents'], 'method' => $data['method'], 'source_reference' => $data['source_reference'], 'paid_on' => $data['paid_on'], 'policy_revision' => $policy->revision, 'evidence' => $data['evidence'], 'created_at' => now('UTC')]);
            Audit::record('wallet.deposit_requested', $row, [], ['requested_cents' => $row->requested_cents, 'method' => $row->method]);

            return ['id' => (int) $row->id];
        });

        return WalletDepositClaim::findOrFail($receipt['id']);
    }

    public function authorizeVerification(User $actor, int $claim): WalletDepositClaim
    {
        app(WalletPolicy::class)->finance($actor, true);
        $row = WalletDepositClaim::lockForUpdate()->findOrFail($claim);
        abort_if($row->actor_id === (int) $actor->id, 403, 'A different current Finance operator must verify your deposit.');

        return $row;
    }

    public function verify(User $actor, int $claim, array $input): WalletParentDeposit
    {
        $data = $this->validated($input, self::verifyRules());
        $data['claim_id'] = $claim;
        $data['verified_original_holder'] = true;
        $data['verified_actual_receipt'] = true;
        $authority = fn () => $this->authorizeVerification($actor, $claim);
        $receipt = app(WalletOperations::class)->once($actor, 'wallet_deposit_verified', $data, $authority, function () use ($actor, $claim, $data): array {
            $request = $this->authorizeVerification($actor, $claim);
            $this->pending($claim);
            $policy = $this->methodPolicy($data['method']);
            $this->pastDate($data['received_on']);
            app(ExternalReceipts::class)->assertAvailable($data['method'], $data['source_reference']);
            // The wallet was bound while the original requester was active. An
            // already received deposit remains their liability after offboarding.
            $wallet = WalletAccount::lockForUpdate()->findOrFail($request->wallet_account_id);
            abort_unless($wallet->user_id === $request->actor_id && $wallet->currency === $request->currency, 409);
            $currency = $request->currency;
            $ledger = app(LedgerService::class);
            $asset = $ledger->openAccount($data['method'] === 'cash' ? AccountType::CashDesk : AccountType::WalletBank, 'wallet:'.$data['method'].':'.$currency, $currency, module: 'wallet');
            foreach ([$asset, $wallet->ledger_account_id] as $id) {
                $actual = $ledger->balance($id)->cents;
                $cached = DB::table('ledger_accounts')->where('school_id', app(CurrentSchool::class)->id())->where('id', $id)->lockForUpdate()->value('balance_cents');
                abort_unless($cached !== null && (int) $cached === $actual, 409, 'Reconcile wallet balances before verifying another deposit.');
                abort_if($data['amount_cents'] > LedgerService::MAX_CENTS - $actual, 422, 'The verified deposit would exceed a supported account balance.');
            }
            $source = 'wallet-parent-deposit:'.$data['operation_id'];
            $money = Money::ofCents($data['amount_cents'], $currency);
            $entry = $ledger->post($source, $source, 'Verified original-parent wallet deposit', [Posting::debit($asset, $money), Posting::credit($wallet->ledger_account_id, $money)]);
            $deposit = WalletParentDeposit::create(['claim_id' => $claim, 'operation_id' => $data['operation_id'], 'currency' => $currency, 'amount_cents' => $data['amount_cents'], 'method' => $data['method'], 'source_reference' => $data['source_reference'], 'received_on' => $data['received_on'], 'provider' => 'manual', 'collected_by' => 'school', 'fee_cents' => 0, 'net_cents' => $data['amount_cents'], 'settlement_reference' => $data['method'] === 'eft' ? $data['source_reference'] : null, 'asset_account_id' => $asset, 'ledger_entry_id' => $entry, 'policy_revision' => $policy->revision, 'verified_by' => $actor->id, 'cash_operator_id' => $data['method'] === 'cash' ? $actor->id : null, 'evidence' => $data['evidence'], 'created_at' => now('UTC')]);
            WalletDepositAction::create(['claim_id' => $claim, 'status' => 'verified', 'actor_id' => $actor->id, 'deposit_id' => $deposit->id, 'reason_code' => 'verified', 'created_at' => now('UTC')]);
            Audit::record('wallet.deposit_verified', $deposit, [], ['amount_cents' => $deposit->amount_cents, 'method' => $deposit->method, 'ledger_entry_id' => $entry]);

            return ['id' => (int) $deposit->id];
        });

        return WalletParentDeposit::findOrFail($receipt['id']);
    }

    public function finish(User $actor, int $claim, string $status, array $input): WalletDepositClaim
    {
        abort_unless(in_array($status, ['cancelled', 'rejected'], true), 422);
        $data = Validator::make($input, self::finishRules($status))->validate();
        $data['claim_id'] = $claim;
        $data['status'] = $status;
        $data['confirmed'] = true;
        $data['reason_code'] = $status === 'cancelled' ? 'withdrawn' : $data['reason_code'];
        $data['private_note'] = $data['private_note'] ?? null;
        $authority = function () use ($actor, $claim, $status): void {
            if ($status === 'cancelled') {
                $this->parent($actor, true);
                abort_unless(WalletDepositClaim::lockForUpdate()->findOrFail($claim)->actor_id === (int) $actor->id, 404);
            } else {
                app(WalletPolicy::class)->finance($actor, true);
                WalletDepositClaim::lockForUpdate()->findOrFail($claim);
            }
        };
        $receipt = app(WalletOperations::class)->once($actor, 'wallet_deposit_outcome', $data, $authority, function () use ($actor, $claim, $status, $data): array {
            $this->pending($claim);
            $row = WalletDepositClaim::findOrFail($claim);
            WalletDepositAction::create(['claim_id' => $claim, 'status' => $status, 'actor_id' => $actor->id, 'reason_code' => $data['reason_code'], 'private_note' => $data['private_note'], 'created_at' => now('UTC')]);
            Audit::record('wallet.deposit_'.$status, $row, [], ['reason_code' => $data['reason_code']]);

            return ['id' => (int) $row->id];
        });

        return WalletDepositClaim::findOrFail($receipt['id']);
    }

    private function pending(int $claim): void
    {
        abort_if(WalletDepositAction::where('claim_id', $claim)->lockForUpdate()->exists() || WalletParentDeposit::where('claim_id', $claim)->lockForUpdate()->exists(), 409, 'This deposit request already has a final outcome.');
    }

    private function methodPolicy(string $method): WalletDepositPolicy
    {
        $policy = WalletDepositPolicy::lockForUpdate()->first();
        abort_unless($policy !== null && ($method === 'cash' ? $policy->cash_allowed : $policy->eft_allowed), 422, 'Finance must explicitly enable this manual wallet receipt method first.');

        return $policy;
    }

    private function pastDate(string $date): void
    {
        abort_unless($date <= now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString(), 422, 'Only an already received deposit can be recorded.');
    }

    private function validated(array $input, array $rules): array
    {
        if (is_string($input['source_reference'] ?? null)) {
            $input['source_reference'] = FeePayments::reference($input['source_reference']);
        }
        $data = Validator::make($input, $rules)->validate();
        $data['amount_cents'] = Money::fromDecimalString($data['amount'])->cents;
        unset($data['amount']);
        abort_unless($data['amount_cents'] > 0 && $data['amount_cents'] <= 100000000000, 422, 'Enter a positive supported deposit amount.');

        return $data;
    }

    public function index(User $actor, int $page = 1, int $perPage = 30): array
    {
        abort_unless($page >= 1 && $page <= 1000000 && $perPage >= 1 && $perPage <= 50, 422);

        return DB::transaction(function () use ($actor, $page, $perPage): array {
            $fresh = app(WalletPolicy::class)->actor($actor);
            $roles = app(WalletPolicy::class)->roles($fresh);
            $isParent = $fresh->email_verified_at !== null && in_array('Parent', $roles, true) && ! in_array('Student', $roles, true);
            $policy = WalletDepositPolicy::first();
            $methods = [];
            if ($policy?->cash_allowed) {
                $methods[] = 'cash';
            }
            if ($policy?->eft_allowed) {
                $methods[] = 'eft';
            }
            $currency = WalletSetting::first()?->currency;
            $query = WalletDepositClaim::where('actor_id', $actor->id);
            $total = (clone $query)->count();
            $rows = $query->orderByDesc('id')->offset(($page - 1) * $perPage)->limit($perPage)->get();

            return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id, 'currency' => $currency, 'is_parent' => $isParent, 'read_only' => ! app(ModuleGate::class)->writable('wallet'), 'can_submit' => $isParent && $currency !== null && $methods !== [] && app(ModuleGate::class)->writable('wallet'), 'cash_allowed' => $isParent && in_array('cash', $methods, true), 'eft_allowed' => $isParent && in_array('eft', $methods, true), 'current_page' => $page, 'per_page' => $perPage, 'total' => $total, 'items' => $rows->map(fn ($row) => $this->receipt($row))->all()];
        });
    }

    /** Parent-facing receipt excludes all private proof and operator evidence. */
    public function receipt(WalletDepositClaim $row): array
    {
        $action = WalletDepositAction::where('claim_id', $row->id)->first();
        $deposit = $action?->deposit_id !== null ? WalletParentDeposit::findOrFail($action->deposit_id) : null;

        return ['id' => (int) $row->id, 'reference' => 'WD-'.$row->id, 'school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => $row->actor_id, 'operation_id' => $row->operation_id, 'wallet_id' => $row->wallet_account_id, 'currency' => $row->currency, 'requested_cents' => $row->requested_cents, 'requested_method' => $row->method, 'requested_reference' => $row->source_reference, 'paid_on' => $row->paid_on, 'status' => $action->status ?? 'pending', 'reason_code' => $action->reason_code ?? null, 'created_at' => CarbonImmutable::parse($row->created_at)->utc()->toISOString(), 'processed_at' => $action ? CarbonImmutable::parse($action->created_at)->utc()->toISOString() : null, 'verified_receipt' => $deposit ? ['reference' => 'WP-'.$deposit->id, 'currency' => $deposit->currency, 'amount_cents' => $deposit->amount_cents, 'method' => $deposit->method, 'source_reference' => $deposit->source_reference, 'received_on' => $deposit->received_on, 'collected_by' => 'school', 'fee_cents' => 0, 'net_cents' => $deposit->amount_cents, 'settlement_reference' => $deposit->method === 'eft' ? $deposit->source_reference : null] : null];
    }

    public static function claimRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'amount' => FeePayments::rules()['amount'], 'method' => ['required', Rule::in(['cash', 'eft'])], 'source_reference' => FeePayments::rules()['source_reference'], 'paid_on' => ['required', 'date_format:Y-m-d'], 'evidence' => ['required', 'string', 'min:10', 'max:2000'], 'confirmed' => ['required', 'accepted']];
    }

    public static function verifyRules(): array
    {
        $rules = self::claimRules();
        unset($rules['paid_on'], $rules['confirmed']);

        return $rules + ['received_on' => ['required', 'date_format:Y-m-d'], 'verified_original_holder' => ['required', 'accepted'], 'verified_actual_receipt' => ['required', 'accepted']];
    }

    public static function finishRules(string $status): array
    {
        $rules = ['operation_id' => ['required', 'uuid'], 'confirmed' => ['required', 'accepted']];

        return $status === 'cancelled' ? $rules : $rules + ['reason_code' => ['required', Rule::in(['not_verified', 'duplicate', 'request_incorrect'])], 'private_note' => ['required', 'string', 'min:10', 'max:2000']];
    }
}
