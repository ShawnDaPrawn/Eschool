<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\WalletAccount;
use App\Models\Money\WalletDeskPolicy;
use App\Models\Money\WalletFunding;
use App\Models\Money\WalletSetting;
use App\Models\Money\WalletWithdrawalPolicy;
use App\Models\User;
use App\Policies\Money\WalletPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class WalletSettings
{
    public function save(User $actor, array $input): WalletSetting
    {
        $data = Validator::make($input, self::rules())->validate();
        $data['daily_spend_cents'] = (int) $data['daily_spend_cents'];
        $data['revision'] = (int) $data['revision'];
        $data['child_limits_required'] = (bool) $data['child_limits_required'];
        $receipt = app(WalletOperations::class)->once($actor, 'wallet_settings', $data, fn () => app(WalletPolicy::class)->finance($actor, true), function () use ($actor, $data): array {
            $settings = WalletSetting::lockForUpdate()->first() ?? new WalletSetting;
            abort_unless((int) ($settings->revision ?? 0) === $data['revision'], 409, 'Wallet settings changed. Review the current rules.');
            abort_if($settings->exists && $settings->currency !== $data['currency'] && (WalletAccount::exists() || WalletFunding::exists() || WalletDeskPolicy::exists() || WalletWithdrawalPolicy::exists() || DB::table('ledger_accounts')->where('school_id', app(CurrentSchool::class)->id())->where('module', 'vendors')->exists()), 409, 'The school currency is retained after issuing a wallet or configuring cash thresholds.');
            $settings->fill(['currency' => $data['currency'], 'daily_spend_cents' => $data['daily_spend_cents'], 'child_limits_required' => $data['child_limits_required'], 'revision' => $data['revision'] + 1, 'changed_by' => $actor->id])->save();
            Audit::record('wallet.settings_saved', $settings, [], ['revision' => $settings->revision]);

            return ['id' => (int) $settings->id];
        });

        return WalletSetting::findOrFail($receipt['id']);
    }

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'currency' => ['required', 'regex:/\A[A-Z]{3}\z/D'], 'daily_spend_cents' => ['required', 'integer', 'min:0', 'max:100000000000'], 'child_limits_required' => ['required', 'boolean']];
    }
}
