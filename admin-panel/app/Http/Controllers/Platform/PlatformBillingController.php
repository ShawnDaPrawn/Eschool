<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\BillingRequest;
use App\Services\Platform\Saas\PlatformBilling;
use App\Services\Platform\Saas\PlatformBillingAccounts;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** HQ: billing schools, EFT payments, statements and overdue accounts (Action Plan 1.17 and SA.8). */
class PlatformBillingController extends Controller
{
    public function __construct(
        private readonly PlatformBilling $billing,
        private readonly PlatformBillingAccounts $accounts,
    ) {}

    public function index(Request $request): View
    {
        return view('saas.billing', $this->accounts->overview(PlatformBilling::month($request->query('month') === null ? null : (string) $request->query('month'))));
    }

    public function run(BillingRequest $request): RedirectResponse
    {
        $result = $this->billing->run($request->validated(), $request->user());

        return redirect()->route('saas.billing', ['month' => $request->validated('period')])->with('success', __(':created drafts created; :existing schools already had an invoice for the month; :skipped schools are not active.', $result));
    }

    public function issueMonth(BillingRequest $request): RedirectResponse
    {
        $result = $this->billing->issueMonth($request->validated(), $request->user());

        return redirect()->route('saas.billing', ['month' => $request->validated('period')])->with('success', __(':issued invoices issued; :left drafts still need attention; :skipped left as drafts because the school is no longer active; :changed already issued or discarded meanwhile.', $result));
    }

    public function statement(int $school): View
    {
        return view('saas.billing-statement', $this->accounts->statement($school));
    }

    public function invoice(int $school, int $invoice): View
    {
        return view('saas.billing-invoice', $this->billing->show($school, $invoice));
    }

    public function recompute(BillingRequest $request, int $school, int $invoice): RedirectResponse
    {
        $this->billing->recompute($school, $invoice, $request->user());

        return redirect()->route('saas.billing.invoices.show', [$school, $invoice])->with('success', __('Draft recomputed.'));
    }

    public function issue(BillingRequest $request, int $school, int $invoice): RedirectResponse
    {
        $reference = $this->billing->issue($school, $invoice, $request->user());

        return redirect()->route('saas.billing.invoices.show', [$school, $invoice])->with('success', __('Invoice :reference issued.', ['reference' => $reference]));
    }

    public function discard(BillingRequest $request, int $school, int $invoice): RedirectResponse
    {
        $this->billing->discard($school, $invoice, $request->user());

        return redirect()->route('saas.billing.statement', $school)->with('success', __('Draft discarded: the school is not billed for that month.'));
    }

    public function credit(BillingRequest $request, int $school, int $invoice): RedirectResponse
    {
        $note = $this->billing->creditNote($school, $invoice, $request->validated(), $request->user());

        return redirect()->route('saas.billing.invoices.show', [$school, $note])->with('success', __('Credit note issued.'));
    }

    public function applyCredit(BillingRequest $request, int $school, int $invoice): RedirectResponse
    {
        $this->accounts->applyCredit($school, $invoice, $request->user());

        return redirect()->route('saas.billing.statement', $school)->with('success', __('Account credit applied.'));
    }

    public function payment(BillingRequest $request, int $school): RedirectResponse
    {
        $this->accounts->record($school, $request->validated(), $request->user());

        return redirect()->route('saas.billing.statement', $school)->with('success', __('Payment recorded.'));
    }

    public function reverse(BillingRequest $request, int $school, int $payment): RedirectResponse
    {
        $this->accounts->reverse($school, $payment, $request->validated(), $request->user());

        return redirect()->route('saas.billing.statement', $school)->with('success', __('Payment reversed.'));
    }

    public function remind(BillingRequest $request, int $school): RedirectResponse
    {
        $sent = $this->accounts->remind($school, $request->validated(), $request->user());

        return redirect()->back(fallback: route('saas.billing.statement', $school))->with('success', __('Reminder sent to :admins Super Admins and :bursars bursar contacts.', ['admins' => $sent['super_admins'], 'bursars' => $sent['bursars']]));
    }
}
