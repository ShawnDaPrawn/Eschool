<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\FeePaymentPanelRequest;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountLearner;
use App\Models\Money\FeePayment;
use App\Models\Money\FeePaymentPolicy;
use App\Models\Money\InvoiceLine;
use App\Models\Money\PaymentAllocationRun;
use App\Services\Money\FeeAllocations;
use App\Services\Money\FeePayments;
use App\Services\Money\FeePaymentSettings;
use App\Services\Money\FeeStatements;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class FeePaymentPanelController extends Controller
{
    public function index(FeePaymentPanelRequest $request): Response
    {
        $payments = FeePayment::join('fee_accounts as a', function ($j) {
            $j->on('a.school_id', '=', 'payments.school_id')->on('a.id', '=', 'payments.fee_account_id');
        })->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('a.name', 'like', '%'.$request->input('search').'%')->orWhere('payments.source_reference', 'like', '%'.$request->input('search').'%')))
            ->when($request->filled('account_id'), fn ($q) => $q->where('payments.fee_account_id', $request->integer('account_id')))
            ->orderByDesc('payments.received_on')->orderByDesc('payments.id')->paginate(30, ['payments.*', 'a.name as account_name'])->withQueryString();
        $policy = FeePaymentPolicy::first();
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.payments.index', compact('payments', 'policy', 'canWrite'));
    }

    public function form(FeePaymentPanelRequest $request): Response
    {
        $accounts = FeeAccount::orderBy('name')->get(['id', 'name']);
        $account = $request->filled('account_id') ? FeeAccount::findOrFail($request->integer('account_id')) : null;
        $holders = $account === null ? [] : app(FeePayments::class)->holders($account);
        $learnerOptions = [];
        $eligible = [];
        if ($account !== null) {
            $ids = FeeAccountLearner::where('fee_account_id', $account->id)->pluck('learner_id')->all();
            $invoiced = InvoiceLine::join('invoices as i', function ($j) {
                $j->on('i.school_id', '=', 'invoice_lines.school_id')->on('i.id', '=', 'invoice_lines.invoice_id');
            })->where('i.fee_account_id', $account->id)->pluck('invoice_lines.learner_id')->all();
            $school = app(CurrentSchool::class)->id();
            foreach (DB::table('students as s')->join('users as u', 'u.id', '=', 's.user_id')->where('s.school_id', $school)->whereIn('s.id', array_unique([...$ids, ...$invoiced]))->orderBy('s.id')->get(['s.id', 'u.first_name', 'u.last_name']) as $learner) {
                $learnerOptions[(int) $learner->id] = trim($learner->first_name.' '.$learner->last_name);
            }
            foreach (array_keys($holders) as $id) {
                $eligible[$id] = app(FeePayments::class)->eligibleLearners($account, (int) $id);
            }
        }
        $today = now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString();
        $canWrite = $account !== null && $holders !== [] && app(ModuleGate::class)->writable('fees');

        return response()->view('money.payments.form', compact('accounts', 'account', 'holders', 'learnerOptions', 'eligible', 'today', 'canWrite'));
    }

    public function record(FeePaymentPanelRequest $request): Response
    {
        $payment = app(FeePayments::class)->record($request->user(), $request->validated());

        return redirect()->route('fee-payments.show', ['payment' => $payment->id], 303)->with('success', __('Verified fee receipt recorded once.'));
    }

    public function show(FeePaymentPanelRequest $request): Response
    {
        $payment = FeePayment::findOrFail((int) $request->route('payment'));
        $account = FeeAccount::findOrFail($payment->fee_account_id);
        app(FeeStatements::class)->complete($account);
        $credit = app(FeePayments::class)->available($payment);
        $runs = PaymentAllocationRun::where('payment_id', $payment->id)->whereNotNull('sealed_at')->orderByDesc('id')->paginate(30);
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.payments.show', compact('payment', 'account', 'credit', 'runs', 'canWrite'));
    }

    public function preview(FeePaymentPanelRequest $request): Response
    {
        $payment = FeePayment::findOrFail((int) $request->route('payment'));
        $preview = app(FeeAllocations::class)->preview($request->user(), (int) $payment->id);
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.payments.preview', compact('payment', 'preview', 'canWrite'));
    }

    public function allocate(FeePaymentPanelRequest $request): Response
    {
        app(FeeAllocations::class)->post($request->user(), (int) $request->route('payment'), $request->validated());

        return redirect()->route('fee-payments.show', ['payment' => $request->route('payment')], 303)->with('success', __('Payment allocated once. Excess credit remains held.'));
    }

    public function policy(FeePaymentPanelRequest $request): Response
    {
        app(FeePaymentSettings::class)->save($request->user(), $request->validated());

        return redirect()->route('fee-payments.index', [], 303)->with('success', __('Fee allocation rule saved.'));
    }

    public function statement(FeePaymentPanelRequest $request): Response
    {
        $account = FeeAccount::findOrFail((int) $request->route('account'));
        $statement = app(FeeStatements::class)->finance($account);
        $charges = $this->page($request, $statement['charges'], 'charges_page');
        $receipts = $this->page($request, $statement['payments'], 'receipts_page');
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.payments.statement', compact('account', 'statement', 'charges', 'receipts', 'canWrite'));
    }

    private function page(FeePaymentPanelRequest $request, array $rows, string $name): LengthAwarePaginator
    {
        $page = $request->integer($name, 1);

        return new LengthAwarePaginator(array_slice($rows, ($page - 1) * 30, 30), count($rows), 30, $page, ['path' => $request->url(), 'query' => $request->query(), 'pageName' => $name]);
    }
}
