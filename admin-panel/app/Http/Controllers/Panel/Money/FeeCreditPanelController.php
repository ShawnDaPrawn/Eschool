<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\FeeCreditPanelRequest;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeCreditNote;
use App\Models\Money\FeeCreditRelease;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Services\Money\FeeCreditNotes;
use App\Services\Money\FeeStatements;
use App\Services\Platform\Modules\ModuleGate;
use Symfony\Component\HttpFoundation\Response;

class FeeCreditPanelController extends Controller
{
    public function index(FeeCreditPanelRequest $request): Response
    {
        $notes = FeeCreditNote::whereNotNull('sealed_at')->when($request->filled('account_id'), fn ($q) => $q->where('fee_account_id', $request->integer('account_id')))->orderByDesc('id')->paginate(30)->withQueryString();
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.credits.index', compact('notes', 'canWrite'));
    }

    public function form(FeeCreditPanelRequest $request): Response
    {
        $accounts = FeeAccount::orderBy('name')->get(['id', 'name']);
        $account = $request->filled('account_id') ? FeeAccount::findOrFail($request->integer('account_id')) : null;
        $charge = null;
        if ($request->filled('invoice_line_id')) {
            $line = InvoiceLine::findOrFail($request->integer('invoice_line_id'));
            $invoice = Invoice::whereNotNull('sealed_at')->findOrFail($line->invoice_id);
            abort_if($account !== null && (int) $account->id !== (int) $invoice->fee_account_id, 404);
            $account = FeeAccount::findOrFail($invoice->fee_account_id);
        }
        $charges = $account === null ? [] : app(FeeStatements::class)->finance($account)['charges'];
        if ($request->filled('invoice_line_id')) {
            $charge = collect($charges)->first(fn ($c) => $c['invoice_line_id'] === $request->integer('invoice_line_id') && $c['holder_user_id'] === $request->integer('holder_user_id'));
            abort_unless($charge !== null, 404);
        }
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.credits.form', compact('accounts', 'account', 'charges', 'charge', 'canWrite'));
    }

    public function review(FeeCreditPanelRequest $request): Response
    {
        $input = $request->validated();
        $preview = app(FeeCreditNotes::class)->preview($request->user(), $input);
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.credits.review', compact('input', 'preview', 'canWrite'));
    }

    public function post(FeeCreditPanelRequest $request): Response
    {
        $note = app(FeeCreditNotes::class)->post($request->user(), $request->validated());

        return redirect()->route('fee-credit-notes.show', ['note' => $note->id], 303)->with('success', __('Credit note posted once.'));
    }

    public function show(FeeCreditPanelRequest $request): Response
    {
        $note = FeeCreditNote::whereNotNull('sealed_at')->findOrFail((int) $request->route('note'));
        app(FeeStatements::class)->complete(FeeAccount::findOrFail($note->fee_account_id));
        $releases = FeeCreditRelease::where('credit_note_id', $note->id)->orderBy('id')->get();

        return response()->view('money.credits.show',compact('note','releases'));
    }
}
