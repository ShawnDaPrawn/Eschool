<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\WalletAccount;
use App\Models\Money\WalletTransfer;
use App\Models\Money\WalletTransferPolicy;
use App\Models\User;
use App\Policies\Money\WalletPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class WalletTransfers
{
    public function policy(User $actor, array $input): WalletTransferPolicy
    {
        $data = Validator::make($input, self::policyRules())->validate();
        $data['revision'] = (int) $data['revision'];
        $data['confirmed'] = true;
        $receipt = app(WalletOperations::class)->once($actor, 'wallet_transfer_policy', $data, fn () => app(WalletPolicy::class)->transferPolicy($actor, true), function () use ($actor, $data): array {
            $row = WalletTransferPolicy::lockForUpdate()->first() ?? new WalletTransferPolicy;
            abort_unless((int) ($row->revision ?? 0) === $data['revision'], 409, 'The child return policy changed. Review it before saving.');
            $row->fill(['child_returns' => $data['child_returns'], 'revision' => $data['revision'] + 1, 'changed_by' => $actor->id])->save();
            Audit::record('wallet.transfer_policy_saved', $row, [], ['child_returns' => $row->child_returns, 'revision' => $row->revision]);

            return ['id' => (int) $row->id];
        });

        return WalletTransferPolicy::findOrFail($receipt['id']);
    }

    /** Fresh authority is shared with middleware, including before HTTP replay. */
    public function authorize(User $actor, int $from, int $to): array
    {
        $policy = app(WalletPolicy::class);
        $fresh = $policy->actor($actor, true);
        abort_unless($this->isParent($fresh), 403, 'Only a verified parent can move money between their own and an approved child wallet.');
        $source = WalletAccount::lockForUpdate()->findOrFail($from);
        $target = WalletAccount::lockForUpdate()->findOrFail($to);
        abort_if($from === $to || $source->user_id === $target->user_id, 422);
        $selfSource = $source->user_id === (int) $actor->id;
        abort_unless($selfSource !== ($target->user_id === (int) $actor->id), 403);
        $policy->owner($actor, $source->user_id, true);
        $policy->owner($actor, $target->user_id, true);
        $child = $selfSource ? $target : $source;
        abort_unless(in_array('Student', $policy->roles(User::findOrFail($child->user_id)), true), 403);
        abort_unless($source->currency === $target->currency, 422);
        if (! $selfSource) {
            abort_unless(WalletTransferPolicy::lockForUpdate()->first()?->child_returns === 'current_approving_guardian', 422, 'The school must explicitly permit child wallet returns first.');
        }

        return [$source, $target];
    }

    public function transfer(User $actor, array $input): WalletTransfer
    {
        $data = Validator::make($input, self::rules())->validate();
        $data['from_wallet_id'] = (int) $data['from_wallet_id'];
        $data['to_wallet_id'] = (int) $data['to_wallet_id'];
        $data['amount_cents'] = Money::fromDecimalString($data['amount'])->cents;
        $data['confirmed'] = true;
        unset($data['amount']);
        abort_unless($data['amount_cents'] > 0 && $data['amount_cents'] <= 100000000000, 422, 'Enter a positive supported wallet transfer.');
        $authority = fn () => $this->authorize($actor, $data['from_wallet_id'], $data['to_wallet_id']);
        $receipt = app(WalletOperations::class)->once($actor, 'wallet_transfer', $data, $authority, function () use ($actor, $data): array {
            [$from, $to] = $this->authorize($actor, $data['from_wallet_id'], $data['to_wallet_id']);
            $ledger = app(LedgerService::class);
            $fromBalance = $this->soundBalance($from);
            $toBalance = $this->soundBalance($to);
            abort_unless($fromBalance >= $data['amount_cents'], 422, 'The source wallet does not cover this transfer.');
            abort_if($data['amount_cents'] > LedgerService::MAX_CENTS - $toBalance, 422, 'The recipient wallet would exceed the supported balance.');
            $source = 'wallet-transfer:'.$data['operation_id'];
            $money = Money::ofCents($data['amount_cents'], $from->currency);
            $entry = $ledger->post($source, $source, 'Parent and child wallet transfer', [Posting::debit($from->ledger_account_id, $money), Posting::credit($to->ledger_account_id, $money)]);
            $row = WalletTransfer::create(['actor_id' => $actor->id, 'operation_id' => $data['operation_id'], 'from_wallet_id' => $from->id, 'to_wallet_id' => $to->id, 'from_owner_id' => $from->user_id, 'to_owner_id' => $to->user_id, 'kind' => $from->user_id === (int) $actor->id ? 'to_child' : 'to_parent', 'currency' => $from->currency, 'amount_cents' => $data['amount_cents'], 'policy_revision' => WalletTransferPolicy::first()?->revision, 'ledger_entry_id' => $entry, 'created_at' => now('UTC')]);
            Audit::record('wallet.transferred', $row, [], ['amount_cents' => $row->amount_cents, 'kind' => $row->kind]);

            return ['id' => (int) $row->id];
        });

        return WalletTransfer::findOrFail($receipt['id']);
    }

    private function soundBalance(WalletAccount $wallet): int
    {
        $balance = app(LedgerService::class)->balance($wallet->ledger_account_id)->cents;
        $cached = DB::table('ledger_accounts')->where('school_id', app(CurrentSchool::class)->id())->where('id', $wallet->ledger_account_id)->lockForUpdate()->value('balance_cents');
        abort_unless($cached !== null && (int) $cached === $balance, 409, 'Reconcile wallet balances before continuing.');

        return $balance;
    }

    private function isParent(User $actor): bool
    {
        $roles = app(WalletPolicy::class)->roles($actor);

        return $actor->email_verified_at !== null && in_array('Parent', $roles, true) && ! in_array('Student', $roles, true);
    }

    public function index(User $actor, int $page = 1, int $perPage = 30): array
    {
        abort_unless($page >= 1 && $page <= 1000000 && $perPage >= 1 && $perPage <= 50, 422);

        return DB::transaction(function () use ($actor, $page, $perPage): array {
            $fresh = app(WalletPolicy::class)->actor($actor);
            $policy = WalletTransferPolicy::first();
            $query = WalletTransfer::where('actor_id', $fresh->id);
            $total = (clone $query)->count();
            $rows = $query->orderByDesc('id')->offset(($page - 1) * $perPage)->limit($perPage)->get();

            return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $fresh->id, 'can_transfer' => $this->isParent($fresh) && app(ModuleGate::class)->writable('wallet'), 'child_returns' => $policy->child_returns ?? 'unconfigured', 'policy_revision' => $policy?->revision, 'current_page' => $page, 'per_page' => $perPage, 'total' => $total, 'items' => $rows->map(fn ($row) => $this->receipt($row))->all()];
        });
    }

    public function receipt(WalletTransfer $row): array
    {
        return ['id' => (int) $row->id, 'reference' => 'WX-'.$row->id, 'school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => $row->actor_id, 'operation_id' => $row->operation_id, 'from_wallet_id' => $row->from_wallet_id, 'to_wallet_id' => $row->to_wallet_id, 'from_owner_id' => $row->from_owner_id, 'to_owner_id' => $row->to_owner_id, 'kind' => $row->kind, 'currency' => $row->currency, 'amount_cents' => $row->amount_cents, 'created_at' => CarbonImmutable::parse($row->created_at)->utc()->toISOString()];
    }

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'from_wallet_id' => ['required', 'integer', 'min:1'], 'to_wallet_id' => ['required', 'integer', 'min:1'], 'amount' => FeePayments::rules()['amount'], 'confirmed' => ['required', 'accepted']];
    }

    public static function policyRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'child_returns' => ['required', Rule::in(['disabled', 'current_approving_guardian'])], 'confirmed' => ['required', 'accepted']];
    }
}
