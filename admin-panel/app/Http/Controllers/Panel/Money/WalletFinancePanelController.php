<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\WalletSettingsPanelRequest;
use App\Models\Money\WalletAccount;
use App\Models\Money\WalletDeskPolicy;
use App\Models\Money\WalletFunding;
use App\Models\Money\WalletSetting;
use App\Models\Money\WalletTransferPolicy;
use App\Models\Money\WalletWithdrawalPolicy;
use App\Policies\Money\WalletPolicy;
use App\Services\Money\WalletFinanceOverview;
use App\Services\Money\Wallets;
use App\Services\Money\WalletSettings;
use App\Services\Money\WalletTransfers;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class WalletFinancePanelController extends Controller
{
    public function index(WalletSettingsPanelRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            app(WalletPolicy::class)->finance($request->user());
            $settings = WalletSetting::first();
            $issued = WalletAccount::exists() || WalletFunding::exists() || WalletDeskPolicy::exists() || WalletWithdrawalPolicy::exists() || DB::table('ledger_accounts')->where('school_id', app(CurrentSchool::class)->id())->where('module', 'vendors')->exists();
            $wallets = WalletAccount::join('users as u', 'u.id', '=', 'wallet_accounts.user_id')->orderBy('wallet_accounts.id')->paginate(30, ['wallet_accounts.*', 'u.first_name', 'u.last_name']);
            $summaries = app(WalletFinanceOverview::class)->read($request->user(), $wallets->getCollection());
            $canWrite = app(ModuleGate::class)->writable('wallet');

            $transferPolicy = WalletTransferPolicy::first();
            $canConfigureReturns = false;
            try {
                app(WalletPolicy::class)->transferPolicy($request->user());
                $canConfigureReturns = true;
            } catch (HttpExceptionInterface $e) {
                if ($e->getStatusCode() !== 403) {
                    throw $e;
                }
            }

            return response()->view('money.wallets.index', compact('settings', 'issued', 'wallets', 'summaries', 'canWrite', 'transferPolicy', 'canConfigureReturns'));
        });
    }

    public function returnPolicy(WalletSettingsPanelRequest $request): Response
    {
        app(WalletTransfers::class)->policy($request->user(), $request->validated());

        return redirect()->route('wallet-finance.index', status: 303)->with('success', __('Child wallet return policy saved.'));
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
