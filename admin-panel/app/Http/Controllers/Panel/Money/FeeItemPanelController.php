<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\FeeItemPanelRequest;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountLearner;
use App\Models\Money\FeeCharge;
use App\Models\Money\FeeItem;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\SessionYear;
use App\Services\Money\FeeCharges;
use App\Services\Money\FeeItems;
use App\Services\Money\FeeStatements;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Symfony\Component\HttpFoundation\Response;

class FeeItemPanelController extends Controller
{
    public function index(FeeItemPanelRequest $request): Response
    {
        $items = FeeItem::orderBy('name')->orderBy('id')->paginate(30);
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.items.index', compact('items', 'canWrite'));
    }

    public function form(FeeItemPanelRequest $request): Response
    {
        $item = $request->route('item') === null ? null : FeeItem::findOrFail((int) $request->route('item'));
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.items.form', compact('item', 'canWrite'));
    }

    public function save(FeeItemPanelRequest $request): Response
    {
        $item = app(FeeItems::class)->save($request->user(), $request->validated(), $request->route('item') === null ? null : (int) $request->route('item'));

        return redirect()->route('fee-items.edit', ['item' => $item->id], 303)->with('success', __('Fee item saved.'));
    }

    public function charges(FeeItemPanelRequest $request): Response
    {
        $charges = FeeCharge::whereNotNull('sealed_at')->orderByDesc('id')->paginate(30);
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.items.charges', compact('charges', 'canWrite'));
    }

    public function chargeForm(FeeItemPanelRequest $request): Response
    {
        $items = FeeItem::where('state', 'active')->orderBy('name')->get(['id', 'name']);
        $accounts = FeeAccount::where('active', true)->orderBy('name')->get(['id', 'name']);
        $item = $request->filled('fee_item_id') ? FeeItem::findOrFail($request->integer('fee_item_id')) : null;
        $account = $request->filled('fee_account_id') ? FeeAccount::findOrFail($request->integer('fee_account_id')) : null;
        $year = $request->filled('session_year_id') ? SessionYear::findOrFail($request->integer('session_year_id')) : SessionYear::where('default', 1)->first();
        $learners = collect();
        if ($account !== null && $year !== null) {
            $learners = FeeAccountLearner::join('students as s', fn ($j) => $j->on('s.school_id', '=', 'fee_account_learners.school_id')->on('s.id', '=', 'fee_account_learners.learner_id'))->join('users as u', 'u.id', '=', 's.user_id')->where('fee_account_id', $account->id)->where('session_year_id', $year->id)->orderBy('learner_id')->get(['fee_account_learners.learner_id', 'u.first_name', 'u.last_name']);
        }
        $learnerOptions = $learners->mapWithKeys(fn ($l) => [(int) $l->learner_id => trim($l->first_name.' '.$l->last_name)])->all();
        $canWrite = app(ModuleGate::class)->writable('fees');
        $today = now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString();

        return response()->view('money.items.charge-form', compact('items', 'accounts', 'item', 'account', 'year', 'learnerOptions', 'canWrite', 'today'));
    }

    public function review(FeeItemPanelRequest $request): Response
    {
        $input = $request->validated();
        $preview = app(FeeCharges::class)->preview($request->user(), $input);
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.items.review', compact('input', 'preview', 'canWrite'));
    }

    public function post(FeeItemPanelRequest $request): Response
    {
        $charge = app(FeeCharges::class)->post($request->user(), $request->validated());

        return redirect()->route('fee-item-charges.show', ['charge' => $charge->id], 303)->with('success', __('Fee item charged once.'));
    }

    public function show(FeeItemPanelRequest $request): Response
    {
        $charge = FeeCharge::whereNotNull('sealed_at')->findOrFail((int) $request->route('charge'));
        app(FeeStatements::class)->complete(FeeAccount::findOrFail($charge->fee_account_id));
        $invoice = Invoice::where('bill_run_id', $charge->bill_run_id)->whereNotNull('sealed_at')->firstOrFail();

        return response()->view('money.items.show', compact('charge', 'invoice'));
    }

    public function invoice(FeeItemPanelRequest $request): Response
    {
        $charge = FeeCharge::whereNotNull('sealed_at')->findOrFail((int) $request->route('charge'));
        app(FeeStatements::class)->complete(FeeAccount::findOrFail($charge->fee_account_id));
        $invoice = Invoice::where('bill_run_id', $charge->bill_run_id)->whereNotNull('sealed_at')->firstOrFail();
        $lines = InvoiceLine::where('invoice_id', $invoice->id)->orderBy('id')->paginate(50);
        $backUrl = route('fee-item-charges.show', ['charge' => $charge->id]);
        $backLabel = __('Back to fee charge');
        $tax = $charge->snapshot;

        return response()->view('money.bill-runs.invoice', compact('invoice', 'lines', 'backUrl', 'backLabel', 'tax'));
    }
}
