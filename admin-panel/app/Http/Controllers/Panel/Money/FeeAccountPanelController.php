<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\FeeAccountPanelRequest;
use App\Http\Requests\Money\FeePaymentChoicePanelRequest;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountHolder;
use App\Models\Money\FeeAccountLearner;
use App\Models\Money\FeePaymentChoice;
use App\Models\Money\FeePlan;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\FeeAccounts;
use App\Services\Money\FeePaymentChoices;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class FeeAccountPanelController extends Controller
{
    public function index(FeeAccountPanelRequest $request): Response
    {
        $accounts = FeeAccount::orderBy('name')->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->input('search').'%'))->paginate(30)->withQueryString();
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.accounts.index', compact('accounts', 'canWrite'));
    }

    public function form(FeeAccountPanelRequest $request): Response
    {
        $account = $request->route('account') === null ? null : FeeAccount::findOrFail((int) $request->route('account'));
        $years = SessionYear::orderByDesc('default')->orderBy('start_date')->get(['id', 'name', 'default']);
        $year = $request->filled('year_id') ? SessionYear::findOrFail((int) $request->input('year_id')) : $years->first();
        $holders = FeeAccountHolder::where('fee_account_id', $account->id ?? 0)->where('session_year_id', $year->id ?? 0)->orderBy('user_id')->get();
        $selected = FeeAccountLearner::where('fee_account_id', $account->id ?? 0)->where('session_year_id', $year->id ?? 0)->pluck('learner_id')->all();
        $school = app(CurrentSchool::class)->id();
        $learnerIds = Enrolment::where('session_year_id', $year->id ?? 0)->pluck('learner_id')->unique();
        $learners = DB::table('students as s')->join('users as u', 'u.id', '=', 's.user_id')->where('s.school_id', $school)->whereNull('s.deleted_at')->whereNull('u.deleted_at')
            ->whereIn('s.id', $learnerIds)->orderBy('u.first_name')->get(['s.id', 'u.first_name', 'u.last_name'])->mapWithKeys(fn ($p) => [(int) $p->id => trim($p->first_name.' '.$p->last_name)]);
        $parentIds = SchoolMembership::active()->where('school_id', $school)->where('role', 'Parent')->pluck('user_id');
        $parents = User::whereIn('id', $parentIds)->where('status', 1)->orderBy('first_name')->get(['id', 'first_name', 'last_name'])->mapWithKeys(fn ($p) => [(int) $p->id => trim($p->first_name.' '.$p->last_name)]);
        foreach ($holders as $holder) {
            if (! $parents->has((int) $holder->user_id)) {
                $parents->put((int) $holder->user_id, __('Unavailable holder'));
            }
        }
        foreach ($selected as $id) {
            if (! $learners->has((int) $id)) {
                $learners->put((int) $id, __('Unavailable learner'));
            }
        }
        $canWrite = $year !== null && app(ModuleGate::class)->writable('fees');
        $paymentChoices = FeePaymentChoice::where('session_year_id', $year->id ?? 0)->whereIn('learner_id', $selected)->get()->keyBy('learner_id');
        $paymentPlans = FeePlan::where('session_year_id', $year->id ?? 0)->where('state', 'active')->get();
        $postedLearners = InvoiceLine::where('session_year_id', $year->id ?? 0)->whereIn('learner_id', $selected)->whereNull('fee_charge_id')->pluck('learner_id')->all();

        return response()->view('money.accounts.form', compact('account', 'years', 'year', 'holders', 'selected', 'learners', 'parents', 'canWrite', 'paymentChoices', 'paymentPlans', 'postedLearners'));
    }

    public function show(FeeAccountPanelRequest $request): Response
    {
        $account = FeeAccount::findOrFail((int) $request->route('account'));
        $invoices = Invoice::where('fee_account_id', $account->id)->whereNotNull('sealed_at')->orderByDesc('id')->paginate(30)->withQueryString();
        $balance = $account->ledger_account_id === null ? Money::zero($account->currency) : app(LedgerService::class)->balance((int) $account->ledger_account_id);
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.accounts.show', compact('account', 'invoices', 'balance', 'canWrite'));
    }

    public function save(FeeAccountPanelRequest $request): Response
    {
        $account = app(FeeAccounts::class)->save($request->user(), $request->validated(), $request->route('account') === null ? null : (int) $request->route('account'));

        return redirect()->route('fee-accounts.show', ['account' => $account->id], 303)->with('success', __('Family account saved.'));
    }

    public function paymentChoice(FeePaymentChoicePanelRequest $request): Response
    {
        $account = (int) $request->route('account');
        app(FeePaymentChoices::class)->save($request->user(), $account, $request->validated());

        return redirect()->route('fee-accounts.edit', ['account' => $account, 'year_id' => $request->validated('session_year_id')], 303)->with('success', __('Payment option saved.'));
    }

    public function archive(FeeAccountPanelRequest $request): Response
    {
        $account = app(FeeAccounts::class)->archive($request->user(), (int) $request->route('account'), $request->validated());

        return redirect()->route('fee-accounts.show', ['account' => $account->id], 303)->with('success', __('Family account archived.'));
    }
}
