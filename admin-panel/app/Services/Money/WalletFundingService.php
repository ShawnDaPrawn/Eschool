<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\WalletFunding;
use App\Models\Money\WalletFundingPolicy;
use App\Models\Money\WalletSetting;
use App\Models\User;
use App\Policies\Money\WalletPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class WalletFundingService
{
    public function policy(User $actor, array $input): WalletFundingPolicy
    {
        $data = Validator::make($input, self::policyRules())->validate();
        $data['revision'] = (int) $data['revision'];
        $data['cash_allowed'] = (bool) $data['cash_allowed'];
        $data['eft_allowed'] = (bool) $data['eft_allowed'];
        $receipt = app(WalletOperations::class)->once($actor, 'wallet_funding_policy', $data, fn () => app(WalletPolicy::class)->finance($actor, true), function () use ($actor, $data): array {
            $row = WalletFundingPolicy::lockForUpdate()->first() ?? new WalletFundingPolicy;
            abort_unless((int) ($row->revision ?? 0) === $data['revision'], 409, 'Wallet funding policy changed. Review its current settings.');
            abort_unless($data['cash_allowed'] || $data['eft_allowed'], 422, 'Choose at least one supported manual funding method.');
            $row->fill(['cash_allowed' => $data['cash_allowed'], 'eft_allowed' => $data['eft_allowed'], 'revision' => $data['revision'] + 1, 'changed_by' => $actor->id])->save();
            Audit::record('wallet.funding_policy_saved', $row, [], ['revision' => $row->revision, 'cash_allowed' => $row->cash_allowed, 'eft_allowed' => $row->eft_allowed]);

            return ['id' => (int) $row->id];
        });

        return WalletFundingPolicy::findOrFail($receipt['id']);
    }

    public function record(User $actor, array $input): WalletFunding
    {
        app(WalletPolicy::class)->finance($actor, true);
        if (is_string($input['source_reference'] ?? null)) {
            $input['source_reference'] = FeePayments::reference($input['source_reference']);
        }
        $data = Validator::make($input, self::receiptRules())->validate();
        $data['amount_cents'] = Money::fromDecimalString($data['amount'])->cents;
        abort_unless($data['amount_cents'] > 0 && $data['amount_cents'] <= 100000000000, 422, 'Enter a positive supported funding amount.');
        $data['school_owned'] = true;
        unset($data['amount']);
        $receipt = app(WalletOperations::class)->once($actor, 'wallet_master_funding', $data, fn () => app(WalletPolicy::class)->finance($actor, true), function () use ($actor, $data): array {
            $policy = WalletFundingPolicy::lockForUpdate()->first();
            abort_unless($policy !== null && ($data['method'] === 'cash' ? $policy->cash_allowed : $policy->eft_allowed), 422, 'Configure the supported manual wallet funding method first.');
            $setting = WalletSetting::lockForUpdate()->first();
            abort_unless($setting !== null, 422, 'Configure wallet currency and spending rules first.');
            abort_unless($data['received_on'] <= now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString(), 422, 'Record only funding already received.');
            app(ExternalReceipts::class)->assertAvailable($data['method'], $data['source_reference']);
            $ledger = app(LedgerService::class);
            $currency = $setting->currency;
            $master = $ledger->openAccount(AccountType::Wallet, 'wallet:master:'.$currency, $currency);
            $asset = $ledger->openAccount($data['method'] === 'cash' ? AccountType::CashDesk : AccountType::WalletBank, 'wallet:'.$data['method'].':'.$currency, $currency, module: 'wallet');
            foreach ([$master, $asset] as $id) {
                $actual = $ledger->balance($id)->cents;
                $cached = (int) DB::table('ledger_accounts')->where('school_id', app(CurrentSchool::class)->id())->where('id', $id)->value('balance_cents');
                abort_unless($actual === $cached, 409, 'Reconcile wallet funding before continuing.');
                abort_if($data['amount_cents'] > LedgerService::MAX_CENTS - $actual, 422, 'This funding would exceed the supported account balance.');
            }
            $source = 'wallet-funding:'.$data['method'].':'.hash('sha256', $data['source_reference']);
            $entry = $ledger->post($source, $source, 'Verified school-owned wallet funding', [Posting::debit($asset, Money::ofCents($data['amount_cents'], $currency)), Posting::credit($master, Money::ofCents($data['amount_cents'], $currency))]);
            $row = WalletFunding::create(['currency' => $currency, 'amount_cents' => $data['amount_cents'], 'method' => $data['method'], 'source_reference' => $data['source_reference'], 'received_on' => $data['received_on'], 'ownership' => 'school', 'provider' => 'manual', 'collected_by' => 'school', 'fee_cents' => 0, 'net_cents' => $data['amount_cents'], 'settlement_reference' => $data['method'] === 'eft' ? $data['source_reference'] : null, 'master_account_id' => $master, 'asset_account_id' => $asset, 'ledger_entry_id' => $entry, 'policy_revision' => $policy->revision, 'evidence' => $data['evidence'], 'created_by' => $actor->id, 'created_at' => now('UTC')]);
            Audit::record('wallet.master_funded', $row, [], ['amount_cents' => $row->amount_cents, 'method' => $row->method, 'ledger_entry_id' => $entry]);

            return ['id' => (int) $row->id];
        });

        return WalletFunding::findOrFail($receipt['id']);
    }

    public function balance(User $actor): array
    {
        return DB::transaction(function () use ($actor): array {
            app(WalletPolicy::class)->finance($actor);

            return $this->balanceProjection();
        });
    }

    public function deskBalance(User $actor): array
    {
        return DB::transaction(function () use ($actor): array {
            app(WalletPolicy::class)->desk($actor);

            return $this->balanceProjection();
        });
    }

    private function balanceProjection(): array
    {
        $setting = WalletSetting::first();
        if ($setting === null) {
            return ['currency' => null, 'balance_cents' => 0];
        }
        $row = DB::table('ledger_accounts')->where('school_id', app(CurrentSchool::class)->id())->where('reference', 'wallet:master:'.$setting->currency)->first();
        $balance = $row === null ? 0 : app(LedgerService::class)->balance((int) $row->id)->cents;
        abort_if($row !== null && (int) $row->balance_cents !== $balance, 409, 'Reconcile wallet funding before continuing.');

        return ['currency' => $setting->currency, 'balance_cents' => $balance];
    }

    public static function policyRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'cash_allowed' => ['required', 'boolean'], 'eft_allowed' => ['required', 'boolean']];
    }

    public static function receiptRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'method' => ['required', 'in:cash,eft'], 'source_reference' => FeePayments::rules()['source_reference'], 'amount' => FeePayments::rules()['amount'], 'received_on' => ['required', 'date_format:Y-m-d'], 'school_owned' => ['required', 'accepted'], 'evidence' => ['required', 'string', 'min:10', 'max:2000']];
    }
}
