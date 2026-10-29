<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\FeeLateChargePanelRequest;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeLateCharge;
use App\Models\Money\FeeLateChargePolicy;
use App\Models\Money\FeeLatePolicyRevision;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\SessionYear;
use App\Services\Money\FeeLateChargePolicies;
use App\Services\Money\FeeLateCharges;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class FeeLateChargePanelController extends Controller
{
    public function index(FeeLateChargePanelRequest $request): Response
    {
        $accounts = FeeAccount::orderBy('name')->paginate(30, ['id', 'name']);
        $account = $request->integer('account_id') ? FeeAccount::findOrFail($request->integer('account_id')) : null;
        if ($request->integer('original_invoice_line_id')) {
            $line = InvoiceLine::findOrFail($request->integer('original_invoice_line_id'));
            $invoice = Invoice::findOrFail($line->invoice_id);
            abort_if($account !== null && (int) $account->id !== (int) $invoice->fee_account_id, 404);
            $account = FeeAccount::findOrFail($invoice->fee_account_id);
        }
        $sources = $account === null ? [] : app(FeeLateCharges::class)->sources($request->user(), $account);
        $source = $request->integer('original_invoice_line_id') ? collect($sources)->first(fn ($s) => $s['original_invoice_line_id'] === $request->integer('original_invoice_line_id') && $s['holder_user_id'] === $request->integer('holder_user_id')) : null;
        abort_if($request->integer('original_invoice_line_id') && $source === null, 404);
        $history = FeeLateCharge::when($account !== null, fn ($q) => $q->where('fee_account_id', $account->id))->orderByDesc('id')->paginate(30, ['*'], 'history_page');
        $readOnly = ! app(ModuleGate::class)->writable('fees');
        $operation = (string) Str::uuid();
        $format = fn (string $currency, int $cents) => Money::ofCents($cents, $currency)->format();

        return response()->view('money.late-charges.index', compact('accounts', 'account', 'sources', 'source', 'history', 'readOnly', 'operation', 'format'));
    }

    public function policy(FeeLateChargePanelRequest $request): Response
    {
        $years = SessionYear::orderByDesc('default')->orderByDesc('start_date')->get(['id', 'name', 'default']);
        $year = $request->integer('year_id') ? SessionYear::findOrFail($request->integer('year_id')) : $years->first();
        $currency = $request->string('currency')->toString();
        $policy = $year === null || $currency === '' ? null : FeeLateChargePolicy::where('session_year_id', $year->id)->where('currency', $currency)->first();
        $revisions = FeeLatePolicyRevision::where('policy_id', $policy?->id)->orderByDesc('revision')->paginate(20);
        $readOnly = ! app(ModuleGate::class)->writable('fees');
        $decimal = fn (?int $cents) => $cents === null ? '' : Money::ofCents($cents)->toDecimalString();
        $operation = (string) Str::uuid();

        return response()->view('money.late-charges.policy', compact('years', 'year', 'currency', 'policy', 'revisions', 'readOnly', 'decimal', 'operation'));
    }

    public function savePolicy(FeeLateChargePanelRequest $request): Response
    {
        $row = app(FeeLateChargePolicies::class)->save($request->user(), $request->safe()->except(['expected_school_id', 'expected_account_id']));

        return redirect()->route('fee-late-charges.policy', ['year_id' => $row->session_year_id, 'currency' => $row->currency], 303)->with('success', __('Original late-charge policy revision retained. Prepare a new review before posting.'));
    }

    public function review(FeeLateChargePanelRequest $request): Response
    {
        $input = $request->safe()->except(['expected_school_id', 'expected_account_id']);
        $preview = app(FeeLateCharges::class)->preview($request->user(), $input);
        $format = fn (string $currency, int $cents) => Money::ofCents($cents, $currency)->format();

        return response()->view('money.late-charges.review', compact('input', 'preview', 'format'));
    }

    public function post(FeeLateChargePanelRequest $request): Response
    {
        $row = app(FeeLateCharges::class)->post($request->user(), $request->safe()->except(['expected_school_id', 'expected_account_id']));

        return redirect()->route('fee-late-charges.index', ['account_id' => $row->fee_account_id], 303)->with('success', __('Original one-off late invoice recorded once.'));
    }
}
