<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\FeeRefundPanelRequest;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeePayment;
use App\Models\Money\FeeRefund;
use App\Models\Money\FeeRefundAction;
use App\Models\Money\FeeRefundPolicy;
use App\Services\Money\FeePayments;
use App\Services\Money\FeeRefundPolicies;
use App\Services\Money\FeeRefunds;
use App\Services\Money\FeeStatements;
use App\Services\Platform\Modules\ModuleGate;
use Symfony\Component\HttpFoundation\Response;

class FeeRefundPanelController extends Controller
{
    public function index(FeeRefundPanelRequest $request): Response
    {
        $policy = FeeRefundPolicy::first();
        $refunds = FeeRefund::orderByDesc('id')->paginate(30);
        $actions = FeeRefundAction::whereIn('fee_refund_id', $refunds->pluck('id'))->orderBy('id')->get()->groupBy('fee_refund_id');
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.refunds.index', compact('policy', 'refunds', 'actions', 'canWrite'));
    }

    public function policy(FeeRefundPanelRequest $request): Response
    {
        app(FeeRefundPolicies::class)->save($request->user(), $request->validated());

        return redirect()->route('fee-refunds.index', status: 303)->with('success', __('School refund policy saved.'));
    }

    public function form(FeeRefundPanelRequest $request): Response
    {
        $payments = FeePayment::orderByDesc('id')->limit(200)->get(['id', 'source_reference', 'currency', 'amount_cents']);
        $payment = $request->filled('payment_id') ? FeePayment::findOrFail($request->integer('payment_id')) : null;
        $account = $payment ? FeeAccount::findOrFail($payment->fee_account_id) : null;
        $available = null;
        if ($payment) {
            app(FeeStatements::class)->complete($account);
            app(FeePayments::class)->verifyCredit($payment);
            $available = app(FeePayments::class)->available($payment);
        }
        $policy = FeeRefundPolicy::first();
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.refunds.form', compact('payments', 'payment', 'account', 'available', 'policy', 'canWrite'));
    }

    public function prepare(FeeRefundPanelRequest $request): Response
    {
        $refund = app(FeeRefunds::class)->prepare($request->user(), $request->validated());

        return redirect()->route('fee-refunds.show', ['refund' => $refund->id], 303);
    }

    public function show(FeeRefundPanelRequest $request): Response
    {
        $refund = FeeRefund::findOrFail((int) $request->route('refund'));
        $actions = FeeRefundAction::where('fee_refund_id', $refund->id)->orderBy('id')->get();
        $state = app(FeeRefunds::class)->state($refund);
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.refunds.show', compact('refund', 'actions', 'state', 'canWrite'));
    }

    public function reserve(FeeRefundPanelRequest $request): Response
    {
        app(FeeRefunds::class)->reserve($request->user(), (int) $request->route('refund'), $request->validated());

        return $this->redirect($request);
    }

    public function payout(FeeRefundPanelRequest $request): Response
    {
        app(FeeRefunds::class)->payout($request->user(), (int) $request->route('refund'), $request->validated());

        return $this->redirect($request);
    }

    public function cancel(FeeRefundPanelRequest $request): Response
    {
        app(FeeRefunds::class)->cancel($request->user(), (int) $request->route('refund'), $request->validated());

        return $this->redirect($request);
    }

    private function redirect(FeeRefundPanelRequest $request): Response
    {
        return redirect()->route('fee-refunds.show', ['refund' => $request->route('refund')], 303);
    }
}
