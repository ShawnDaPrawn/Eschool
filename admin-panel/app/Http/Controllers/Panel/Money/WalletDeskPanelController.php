<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\WalletDeskPanelRequest;
use App\Models\Money\WalletDeskAction;
use App\Models\Money\WalletDeskPolicy;
use App\Models\Money\WalletDeskTopup;
use App\Models\Money\WalletSetting;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Policies\Money\WalletPolicy;
use App\Services\Money\WalletDeskTopups;
use App\Services\Money\WalletFundingService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class WalletDeskPanelController extends Controller
{
    public function index(WalletDeskPanelRequest $request): Response
    {
        $setting = WalletSetting::first();
        $policy = WalletDeskPolicy::first();
        $balance = app(WalletFundingService::class)->deskBalance($request->user());
        $canWrite = app(ModuleGate::class)->writable('wallet');
        $canFinance = false;
        try {
            app(WalletPolicy::class)->finance($request->user());
            $canFinance = true;
        } catch (HttpExceptionInterface $e) {
            if ($e->getStatusCode() !== 403) {
                throw $e;
            }
        }$canConfigureDesk = false;
        try {
            app(WalletPolicy::class)->deskPolicy($request->user(), false);
            $canConfigureDesk = true;
        } catch (HttpExceptionInterface $e) {
            if ($e->getStatusCode() !== 403) {
                throw $e;
            }
        }$configured = $setting !== null && $policy !== null && $setting->currency === $policy->currency;
        $search = trim((string) $request->input('search', ''));
        $recipients = [];
        if ($canWrite) {
            $people = User::whereIn('id', SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->select('user_id'))->where('status', 1)->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('first_name', 'like', '%'.addcslashes($search, '%_\\').'%')->orWhere('last_name', 'like', '%'.addcslashes($search, '%_\\').'%')))->orderBy('last_name')->orderBy('first_name')->limit(60)->get();
            foreach ($people as $person) {
                try {
                    app(WalletPolicy::class)->activePerson($person);
                    $recipients[$person->id] = trim($person->first_name.' '.$person->last_name).' (#'.$person->id.')';
                } catch (HttpExceptionInterface) {
                }
            }
        }
        $topups = WalletDeskTopup::orderByDesc('id')->paginate(30);
        $states = WalletDeskAction::whereIn('topup_id', $topups->pluck('id'))->pluck('action', 'topup_id');
        $names = User::withTrashed()->whereIn('id', $topups->pluck('owner_user_id'))->get()->mapWithKeys(fn ($user) => [(int) $user->id => trim($user->first_name.' '.$user->last_name)]);

        return response()->view('money.wallets.desk', compact('setting', 'policy', 'balance', 'canWrite', 'canFinance', 'canConfigureDesk', 'configured', 'search', 'recipients', 'topups', 'states', 'names'));
    }

    public function policy(WalletDeskPanelRequest $request): Response
    {
        app(WalletDeskTopups::class)->policy($request->user(), $request->validated());

        return redirect()->route('wallet-desk.index', status: 303)->with('success', __('Desk approval threshold saved.'));
    }

    public function grant(WalletDeskPanelRequest $request): Response
    {
        $row = app(WalletDeskTopups::class)->grant($request->user(), $request->validated());

        return redirect()->route('wallet-desk.receipt', ['topup' => $row->id], 303)->with('success', __('Desk top-up recorded. Review its receipt and approval status.'));
    }

    public function release(WalletDeskPanelRequest $request): Response
    {
        return $this->finish($request, 'released');
    }

    public function cancel(WalletDeskPanelRequest $request): Response
    {
        return $this->finish($request, 'cancelled');
    }

    private function finish(WalletDeskPanelRequest $request, string $action): Response
    {
        $row = app(WalletDeskTopups::class)->finish($request->user(), (int) $request->route('topup'), $action, $request->validated());

        return redirect()->route('wallet-desk.receipt', ['topup' => $row->id], 303)->with('success', __('Desk top-up decision recorded.'));
    }

    public function receipt(WalletDeskPanelRequest $request): Response
    {
        $data = app(WalletDeskTopups::class)->financeReceipt($request->user(), (int) $request->route('topup'));
        $canWrite = app(ModuleGate::class)->writable('wallet');

        return response()->view('money.wallets.desk-receipt', $data + ['canWrite' => $canWrite]);
    }
}
