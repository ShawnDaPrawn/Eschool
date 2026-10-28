<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\WalletDepositPanelRequest;
use App\Models\Money\WalletDepositAction;
use App\Models\Money\WalletDepositClaim;
use App\Models\Money\WalletDepositPolicy;
use App\Models\User;
use App\Policies\Money\WalletPolicy;
use App\Services\Money\WalletDeposits;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class WalletDepositPanelController extends Controller
{
    public function index(WalletDepositPanelRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            app(WalletPolicy::class)->finance($request->user());
            $claims = WalletDepositClaim::orderByDesc('id')->paginate(30);
            $names = User::withTrashed()->whereIn('id', $claims->pluck('actor_id'))->get()->mapWithKeys(fn ($user) => [(int) $user->id => trim($user->first_name.' '.$user->last_name)]);
            $statuses = WalletDepositAction::whereIn('claim_id', $claims->pluck('id'))->pluck('status', 'claim_id');

            $policy = WalletDepositPolicy::first();
            $canWrite = app(ModuleGate::class)->writable('wallet');

            return response()->view('money.wallets.deposits', compact('claims', 'names', 'statuses', 'policy', 'canWrite'));
        });
    }

    public function show(WalletDepositPanelRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            app(WalletPolicy::class)->finance($request->user());
            $claim = WalletDepositClaim::findOrFail((int) $request->route('claim'));
            $owner = User::withTrashed()->findOrFail($claim->actor_id);
            $action = WalletDepositAction::where('claim_id', $claim->id)->first();
            $receipt = app(WalletDeposits::class)->receipt($claim);
            $policy = WalletDepositPolicy::first();
            $methods = [];
            if ($policy?->cash_allowed) {
                $methods['cash'] = __('Cash');
            }
            if ($policy?->eft_allowed) {
                $methods['eft'] = __('EFT');
            }
            $canWrite = app(ModuleGate::class)->writable('wallet');
            $canVerify = $canWrite && $action === null && $methods !== [] && (int) $request->user()->id !== $claim->actor_id;

            return response()->view('money.wallets.deposit-review', compact('claim', 'owner', 'action', 'receipt', 'methods', 'canWrite', 'canVerify'));
        });
    }

    public function policy(WalletDepositPanelRequest $request): Response
    {
        app(WalletDeposits::class)->policy($request->user(), $request->validated());

        return redirect()->route('wallet-deposits.index', status: 303)->with('success', __('Parent deposit methods saved.'));
    }

    public function verify(WalletDepositPanelRequest $request): Response
    {
        app(WalletDeposits::class)->verify($request->user(), (int) $request->route('claim'), $request->validated());

        return redirect()->route('wallet-deposits.show', ['claim' => $request->route('claim')], 303)->with('success', __('Verified parent deposit credited to its original holder.'));
    }

    public function reject(WalletDepositPanelRequest $request): Response
    {
        app(WalletDeposits::class)->finish($request->user(), (int) $request->route('claim'), 'rejected', $request->validated());

        return redirect()->route('wallet-deposits.show', ['claim' => $request->route('claim')], 303)->with('success', __('Parent deposit request rejected without moving money.'));
    }

    public function receipt(WalletDepositPanelRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            app(WalletPolicy::class)->finance($request->user());
            $claim = WalletDepositClaim::findOrFail((int) $request->route('claim'));
            $receipt = app(WalletDeposits::class)->receipt($claim);
            abort_unless($receipt['verified_receipt'] !== null, 404);
            $owner = User::withTrashed()->findOrFail($claim->actor_id);

            return response()->view('money.wallets.parent-deposit-receipt', compact('receipt', 'owner'));
        });
    }
}
