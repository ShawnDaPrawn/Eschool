<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\WalletSettingsPanelRequest;
use App\Models\Money\WalletAccount;
use App\Models\Money\WalletFunding;
use App\Models\Money\WalletSetting;
use App\Services\Money\Wallets;
use App\Services\Money\WalletSettings;
use App\Services\Platform\Modules\ModuleGate;
use Symfony\Component\HttpFoundation\Response;

class WalletFinancePanelController extends Controller
{
    public function index(WalletSettingsPanelRequest $request): Response
    {
        $settings = WalletSetting::first();
        $issued = WalletAccount::exists() || WalletFunding::exists();
        $wallets = WalletAccount::join('users as u', 'u.id', '=', 'wallet_accounts.user_id')->orderBy('wallet_accounts.id')->paginate(30, ['wallet_accounts.*', 'u.first_name', 'u.last_name']);
        $summaries = [];
        foreach ($wallets as $wallet) {
            $summaries[(int) $wallet->id] = app(Wallets::class)->summary($wallet);
        }$canWrite = app(ModuleGate::class)->writable('wallet');

        return response()->view('money.wallets.index', compact('settings', 'issued', 'wallets', 'summaries', 'canWrite'));
    }

    public function save(WalletSettingsPanelRequest $request): Response
    {
        app(WalletSettings::class)->save($request->user(), $request->validated());

        return redirect()->route('wallet-finance.index', status: 303)->with('success', __('School wallet spending rules saved.'));
    }

    public function show(WalletSettingsPanelRequest $request): Response
    {
        $statement = app(Wallets::class)->financeStatement($request->user(), (int) $request->route('wallet'), $request->integer('page', 1));

        return response()->view('money.wallets.statement', compact('statement'));
    }
}
