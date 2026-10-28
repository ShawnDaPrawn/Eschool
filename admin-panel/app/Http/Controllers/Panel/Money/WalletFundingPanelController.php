<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\WalletFundingPanelRequest;
use App\Models\Money\WalletFunding;
use App\Models\Money\WalletFundingPolicy;
use App\Services\Money\WalletFundingService;
use App\Services\Platform\Modules\ModuleGate;
use Symfony\Component\HttpFoundation\Response;

class WalletFundingPanelController extends Controller
{
    public function index(WalletFundingPanelRequest $request): Response
    {
        $policy = WalletFundingPolicy::first();
        $balance = app(WalletFundingService::class)->balance($request->user());
        $receipts = WalletFunding::orderByDesc('id')->paginate(30);
        $canWrite = app(ModuleGate::class)->writable('wallet');

        return response()->view('money.wallets.funding', compact('policy', 'balance', 'receipts', 'canWrite'));
    }

    public function policy(WalletFundingPanelRequest $request): Response
    {
        app(WalletFundingService::class)->policy($request->user(), $request->validated());

        return redirect()->route('wallet-funding.index', status: 303)->with('success', __('Manual wallet funding policy saved.'));
    }

    public function record(WalletFundingPanelRequest $request): Response
    {
        $receipt = app(WalletFundingService::class)->record($request->user(), $request->validated());

        return redirect()->route('wallet-funding.receipt', ['funding' => $receipt->id], 303)->with('success', __('Verified school wallet funding recorded.'));
    }

    public function receipt(WalletFundingPanelRequest $request): Response
    {
        $receipt = WalletFunding::findOrFail((int) $request->route('funding'));

        return response()->view('money.wallets.funding-receipt', compact('receipt'));
    }
}
