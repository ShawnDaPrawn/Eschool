<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\BillRunPanelRequest;
use App\Models\Money\BillRun;
use App\Models\Money\FeeCharge;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\SessionYear;
use App\Services\Money\BillRuns;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

class BillRunPanelController extends Controller
{
    public function index(BillRunPanelRequest $request): Response
    {
        $runs = BillRun::where('kind', 'annual')->orderByDesc('id')->paginate(30);
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.bill-runs.index', compact('runs', 'canWrite'));
    }

    public function form(BillRunPanelRequest $request): Response
    {
        $years = SessionYear::orderByDesc('default')->orderBy('start_date')->get(['id', 'name', 'start_date', 'end_date']);
        $canWrite = app(ModuleGate::class)->writable('fees') && $years->isNotEmpty();

        return response()->view('money.bill-runs.form', compact('years', 'canWrite'));
    }

    public function show(BillRunPanelRequest $request): Response
    {
        $run = BillRun::where('kind', 'annual')->findOrFail((int) $request->route('run'));
        $snapshot = $run->snapshot;
        $filtered = array_filter($snapshot['rows'], fn ($r) => (! $request->boolean('exceptions_only') || $r['issues'] !== [])
            && (! $request->filled('search') || str_contains(mb_strtolower($r['name']), mb_strtolower((string) $request->input('search')))));
        $page = (int) $request->input('page', 1);
        $rows = new LengthAwarePaginator(array_slice(array_values($filtered), ($page - 1) * 50, 50), count($filtered), 50, $page, ['path' => $request->url(), 'query' => $request->query()]);
        $invoices = Invoice::where('bill_run_id', $run->id)->whereNotNull('sealed_at')->orderBy('id')->paginate(30, ['id', 'number', 'account_name', 'currency', 'total_cents'], 'invoices_page')->withQueryString();
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.bill-runs.show', compact('run', 'snapshot', 'rows', 'invoices', 'canWrite'));
    }

    public function prepare(BillRunPanelRequest $request): Response
    {
        $run = app(BillRuns::class)->prepare($request->user(), $request->validated());

        return redirect()->route('fee-bill-runs.show', ['run' => $run->id], 303)->with('success', __('Bill preview prepared.'));
    }

    public function approve(BillRunPanelRequest $request): Response
    {
        $run = app(BillRuns::class)->approve($request->user(), (int) $request->route('run'), $request->validated());

        return redirect()->route('fee-bill-runs.show', ['run' => $run->id], 303)->with('success', __('Bill run approved.'));
    }

    public function post(BillRunPanelRequest $request): Response
    {
        $run = app(BillRuns::class)->post($request->user(), (int) $request->route('run'), $request->validated());

        return redirect()->route('fee-bill-runs.show', ['run' => $run->id], 303)->with('success', __('Invoices posted once.'));
    }

    public function invoice(BillRunPanelRequest $request): Response
    {
        $invoice = Invoice::whereNotNull('sealed_at')->findOrFail((int) $request->route('invoice'));
        $lines = InvoiceLine::where('invoice_id', $invoice->id)->orderBy('id')->paginate(50);

        $backUrl = BillRun::findOrFail($invoice->bill_run_id)->kind === 'annual' ? route('fee-bill-runs.show', ['run' => $invoice->bill_run_id]) : route('fee-bill-runs.index');

        $tax = FeeCharge::where('bill_run_id', $invoice->bill_run_id)->whereNotNull('sealed_at')->first()?->snapshot;

        return response()->view('money.bill-runs.invoice', compact('invoice', 'lines', 'backUrl', 'tax'));
    }
}
