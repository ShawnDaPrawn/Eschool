<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\FeePayerCorrectionPanelRequest;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountHolder;
use App\Models\Money\FeePayerCorrection;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\User;
use App\Services\Money\FeePayerCorrections;
use App\Services\Money\FeeStatements;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class FeePayerCorrectionPanelController extends Controller
{
    public function index(FeePayerCorrectionPanelRequest $request): Response
    {
        $accounts = FeeAccount::orderBy('name')->paginate(30, ['id', 'name']);
        $account = $request->integer('account_id') ? FeeAccount::findOrFail($request->integer('account_id')) : null;
        $line = $request->integer('invoice_line_id') ? InvoiceLine::findOrFail($request->integer('invoice_line_id')) : null;
        if ($line !== null) {
            $invoice = Invoice::findOrFail($line->invoice_id);
            abort_if($account !== null && (int) $account->id !== (int) $invoice->fee_account_id, 404);
            $account = FeeAccount::findOrFail($invoice->fee_account_id);
        }
        $statement = $account === null ? null : app(FeeStatements::class)->finance($account);
        $source = $line === null ? null : collect($statement['charges'])->first(fn ($row) => $row['invoice_line_id'] === (int) $line->id && $row['holder_user_id'] === $request->integer('holder_user_id'));
        abort_if($line !== null && $source === null, 404);
        $holderOptions = [];
        if ($line !== null) {
            $ids = FeeAccountHolder::where('fee_account_id', $account->id)->where('session_year_id', $line->session_year_id)->where('user_id', '<>', $request->integer('holder_user_id'))->pluck('user_id');
            $holderOptions = User::whereIn('id', $ids)->where('status', 1)->orderBy('first_name')->get()->mapWithKeys(fn ($u) => [(int) $u->id => trim($u->first_name.' '.$u->last_name)])->all();
        }
        $history = FeePayerCorrection::when($account !== null, fn ($q) => $q->where('fee_account_id', $account->id))->orderByDesc('id')->paginate(30, ['*'], 'history_page');
        $readOnly = ! app(ModuleGate::class)->writable('fees');
        $operation = (string) Str::uuid();

        return response()->view('money.payer-corrections.index', compact('accounts', 'account', 'statement', 'source', 'holderOptions', 'history', 'readOnly', 'operation'));
    }

    public function review(FeePayerCorrectionPanelRequest $request): Response
    {
        $input = $request->safe()->except(['expected_school_id', 'expected_account_id']);
        $preview = app(FeePayerCorrections::class)->preview($request->user(), $input);

        return response()->view('money.payer-corrections.review', compact('input', 'preview'));
    }

    public function post(FeePayerCorrectionPanelRequest $request): Response
    {
        $row = app(FeePayerCorrections::class)->post($request->user(), $request->safe()->except(['expected_school_id', 'expected_account_id']));

        return redirect()->route('fee-payer-corrections.index', ['account_id' => $row->fee_account_id], 303)->with('success', __('Original unpaid credit and equal replacement payer invoice recorded once.'));
    }
}
