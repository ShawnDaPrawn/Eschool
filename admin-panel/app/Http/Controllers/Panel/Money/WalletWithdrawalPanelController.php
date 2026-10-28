<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\WalletWithdrawalPanelRequest;
use App\Models\Money\WalletAccount;
use App\Models\Money\WalletSetting;
use App\Models\Money\WalletWithdrawal;
use App\Models\Money\WalletWithdrawalPolicy;
use App\Models\Money\WalletWithdrawalSlot;
use App\Models\Money\WalletWithdrawalSlotClosure;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Money\WalletWithdrawals;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class WalletWithdrawalPanelController extends Controller
{
    public function index(WalletWithdrawalPanelRequest $request): Response
    {
        return DB::transaction(function (): Response {
            $service = app(WalletWithdrawals::class);
            $rows = WalletWithdrawal::orderByDesc('id')->paginate(30);
            $receipts = $rows->mapWithKeys(fn ($row) => [$row->id => $service->receipt($row)]);
            $names = User::withTrashed()->whereIn('id', $rows->pluck('holder_user_id'))->get()->mapWithKeys(fn ($u) => [$u->id => trim($u->first_name.' '.$u->last_name)]);
            $policy = WalletWithdrawalPolicy::first();
            $currency = WalletSetting::first()?->currency;
            $canWrite = app(ModuleGate::class)->writable('wallet');
            $slots = WalletWithdrawalSlot::orderByDesc('starts_at')->limit(100)->get();
            $closed = WalletWithdrawalSlotClosure::whereIn('slot_id', $slots->pluck('id'))->pluck('slot_id')->all();
            $timezone = app(CurrentSchool::class)->school()->timezone ?? 'UTC';
            $slotOptions = [];
            foreach ($slots as $slot) {
                if (! in_array($slot->id, $closed, true) && $slot->starts_at->isFuture()) {
                    $slotOptions[$slot->id] = $slot->starts_at->setTimezone($timezone)->format('Y-m-d H:i').' '.$timezone;
                }
            }
            $adultIds = SchoolMembership::where('school_id', app(CurrentSchool::class)->id())->whereIn('role', ['Parent', 'Teacher', 'Facilitator'])->pluck('user_id');
            $studentIds = SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->where('role', 'Student')->pluck('user_id');
            $walletIds = WalletAccount::whereIn('user_id', $adultIds)->whereNotIn('user_id', $studentIds)->pluck('user_id');
            $holders = User::withTrashed()->whereIn('id', $walletIds)->where('id', '<>', auth()->id())->get()->mapWithKeys(fn ($u) => [$u->id => trim($u->first_name.' '.$u->last_name)]);

            return response()->view('money.wallets.withdrawals', compact('rows', 'receipts', 'names', 'policy', 'currency', 'canWrite', 'slots', 'closed', 'slotOptions', 'holders', 'timezone'));
        });
    }

    public function show(WalletWithdrawalPanelRequest $request): Response
    {
        $row = WalletWithdrawal::findOrFail((int) $request->route('withdrawal'));
        $holder = User::withTrashed()->findOrFail($row->holder_user_id);
        $receipt = app(WalletWithdrawals::class)->receipt($row);
        $canWrite = app(ModuleGate::class)->writable('wallet');
        $canApprove = $canWrite && $row->holder_user_id !== (int) $request->user()->id;

        return response()->view('money.wallets.withdrawal-review', compact('row', 'holder', 'receipt', 'canWrite', 'canApprove'));
    }

    public function policy(WalletWithdrawalPanelRequest $request): Response
    {
        app(WalletWithdrawals::class)->policy($request->user(), $request->validated());

        return redirect()->route('wallet-withdrawals.index', status: 303)->with('success', __('Withdrawal policy saved.'));
    }

    public function publishSlot(WalletWithdrawalPanelRequest $request): Response
    {
        app(WalletWithdrawals::class)->publishSlot($request->user(), $request->validated());

        return redirect()->route('wallet-withdrawals.index', status: 303)->with('success', __('Cash pickup slot published.'));
    }

    public function closeSlot(WalletWithdrawalPanelRequest $request): Response
    {
        app(WalletWithdrawals::class)->closeSlot($request->user(), (int) $request->route('slot'), $request->validated());

        return redirect()->route('wallet-withdrawals.index', status: 303)->with('success', __('Pickup slot closed to new bookings; existing bookings are retained.'));
    }

    public function offboarding(WalletWithdrawalPanelRequest $request): Response
    {
        $row = app(WalletWithdrawals::class)->request($request->user(), $request->validated(), $request->integer('holder_user_id'));

        return redirect()->route('wallet-withdrawals.show', ['withdrawal' => $row->id], 303)->with('success', __('Original adult funds reserved for Finance approval.'));
    }

    public function approve(WalletWithdrawalPanelRequest $request): Response
    {
        app(WalletWithdrawals::class)->approve($request->user(), (int) $request->route('withdrawal'), $request->validated());

        return $this->changed($request, __('Withdrawal approved; no outside payment has been sent.'));
    }

    public function finish(WalletWithdrawalPanelRequest $request): Response
    {
        $status = $request->routeIs('wallet-withdrawals.paid') ? 'paid' : ($request->routeIs('wallet-withdrawals.reject') ? 'rejected' : 'cancelled');
        app(WalletWithdrawals::class)->finish($request->user(), (int) $request->route('withdrawal'), $status, $request->validated());

        return $this->changed($request, $status === 'paid' ? __('Verified original-holder payout recorded.') : __('Withdrawal reservation returned to its original wallet.'));
    }

    private function changed(WalletWithdrawalPanelRequest $request, string $message): Response
    {
        return redirect()->route('wallet-withdrawals.show', ['withdrawal' => $request->route('withdrawal')], 303)->with('success', $message);
    }

    public function receipt(WalletWithdrawalPanelRequest $request): Response
    {
        $row = WalletWithdrawal::findOrFail((int) $request->route('withdrawal'));
        $receipt = app(WalletWithdrawals::class)->receipt($row);
        abort_unless($receipt['status'] === 'paid', 404);
        $holder = User::withTrashed()->findOrFail($row->holder_user_id);

        return response()->view('money.wallets.withdrawal-receipt', compact('receipt', 'holder'));
    }
}
