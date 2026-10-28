<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\FeeLeaverPanelRequest;
use App\Models\Money\BillRun;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeLeaverPolicy;
use App\Models\Money\FeeLeaverRun;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Services\Money\FeeLeaverPolicies;
use App\Services\Money\FeeLeaverRuns;
use App\Services\Platform\Modules\ModuleGate;
use Symfony\Component\HttpFoundation\Response;

class FeeLeaverPanelController extends Controller
{
    public function index(FeeLeaverPanelRequest $request): Response
    {
        $policy = FeeLeaverPolicy::first();
        $runs = FeeLeaverRun::orderByDesc('id')->paginate(30);
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.leavers.index', compact('policy', 'runs', 'canWrite'));
    }

    public function policy(FeeLeaverPanelRequest $request): Response
    {
        app(FeeLeaverPolicies::class)->save($request->user(), $request->validated());

        return redirect()->route('fee-leavers.index', status: 303)->with('success', __('School leaver credit policy saved. Review affected departures again.'));
    }

    public function form(FeeLeaverPanelRequest $request): Response
    {
        $accounts = FeeAccount::orderBy('name')->get(['id', 'name']);
        $account = $request->filled('account_id') ? FeeAccount::findOrFail($request->integer('account_id')) : null;
        $invoices = Invoice::whereNotNull('sealed_at')->whereIn('bill_run_id', BillRun::where('kind', 'annual')->where('state', 'posted')->select('id'))->when($account !== null, fn ($q) => $q->where('fee_account_id', $account->id))->orderByDesc('id')->limit(200)->get();
        $invoice = $request->filled('invoice_id') ? Invoice::whereNotNull('sealed_at')->findOrFail($request->integer('invoice_id')) : null;
        if ($invoice !== null) {
            abort_unless($account === null || (int) $account->id === (int) $invoice->fee_account_id, 404);
        }
        $learners = $invoice === null ? collect() : InvoiceLine::where('invoice_id', $invoice->id)->whereNull('fee_charge_id')->orderBy('id')->get()->unique('learner_id')->pluck('learner_name', 'learner_id');
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.leavers.form', compact('accounts', 'account', 'invoices', 'invoice', 'learners', 'canWrite'));
    }

    public function prepare(FeeLeaverPanelRequest $request): Response
    {
        $run = app(FeeLeaverRuns::class)->prepare($request->user(), $request->validated());

        return redirect()->route('fee-leavers.show', ['run' => $run->id], 303);
    }

    public function show(FeeLeaverPanelRequest $request): Response
    {
        $run = FeeLeaverRun::findOrFail((int) $request->route('run'));
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.leavers.show', compact('run', 'canWrite'));
    }

    public function approve(FeeLeaverPanelRequest $request): Response
    {
        $run = app(FeeLeaverRuns::class)->approve($request->user(), (int) $request->route('run'), $request->validated());

        return redirect()->route('fee-leavers.show', ['run' => $run->id], 303);
    }

    public function post(FeeLeaverPanelRequest $request): Response
    {
        $run = app(FeeLeaverRuns::class)->post($request->user(), (int) $request->route('run'), $request->validated());

        return redirect()->route('fee-leavers.show', ['run' => $run->id], 303)->with('success', __('Leaver credit posted once. Original invoices are retained.'));
    }
}
