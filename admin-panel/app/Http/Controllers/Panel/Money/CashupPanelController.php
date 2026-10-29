<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\CashupPanelRequest;
use App\Models\Money\CashupCount;
use App\Models\User;
use App\Policies\Money\CashupPolicy;
use App\Services\Money\CashFlows;
use App\Services\Money\Cashups;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CashupPanelController extends Controller
{
    public function index(CashupPanelRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            app(CashupPolicy::class)->finance($request->user());
            $service = app(Cashups::class);
            $currencies = $service->currencies();
            $currency = $request->input('currency', $currencies[0] ?? null);
            $date = $request->input('counted_on', now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString());
            $operator = $request->integer('operator_id') ?: (int) $request->user()->id;
            $report = $currency === null ? null : $service->report($request->user(), $operator, $currency, $date);
            $ids = [(int) $request->user()->id];
            if ($currency !== null) {
                foreach (app(CashFlows::class)->inspect($currency)['events'] as $event) {
                    $ids[] = $event['operator_id'];
                }foreach (CashupCount::where('currency', $currency)->pluck('operator_id') as $id) {
                    $ids[] = (int) $id;
                }
            }
            $operators = User::withTrashed()->whereIn('id', array_unique($ids))->get()->mapWithKeys(fn ($u) => [(int) $u->id => $u->first_name.' '.$u->last_name])->all();
            $history = $currency === null ? collect() : CashupCount::where('operator_id', $operator)->where('currency', $currency)->where('counted_on', $date)->orderByDesc('revision')->get();

            return response()->view('money.cashup.index', compact('currencies', 'currency', 'date', 'operator', 'operators', 'report', 'history'));
        });
    }

    public function record(CashupPanelRequest $request): Response
    {
        $row = app(Cashups::class)->record($request->user(), $request->safe()->except(['expected_school_id', 'expected_account_id']));

        return redirect()->route('cashups.index', ['operator_id' => $row->operator_id, 'currency' => $row->currency, 'counted_on' => $row->counted_on])->with('success', __('Physical cash count retained. The original ledger has not been adjusted.'));
    }

    public function export(CashupPanelRequest $request): Response
    {
        $currency = (string) $request->input('currency');
        $date = (string) $request->input('counted_on');
        $operator = $request->integer('operator_id') ?: (int) $request->user()->id;
        $r = app(Cashups::class)->report($request->user(), $operator, $currency, $date);

        return response()->streamDownload(function () use ($r): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['counted_on', 'operator_id', 'currency', 'opening_cents', 'receipts_cents', 'payouts_cents', 'closing_cents', 'counted_cents', 'variance_cents', 'count_revision', 'needs_review'], ',', '"', '');
            fputcsv($out, [$r['counted_on'], $r['operator_id'], $r['currency'], $r['opening_cents'], $r['receipts_cents'], $r['payouts_cents'], $r['closing_cents'], $r['latest']?->counted_cents, $r['latest']?->variance_cents, $r['latest']?->revision, $r['needs_review'] ? 'yes' : 'no'], ',', '"', '');
            fputcsv($out, ['kind', 'record_id', 'effective_on', 'date_basis', 'amount_cents', 'ledger_entry_id'], ',', '"', '');
            foreach ($r['events'] as $e) {
                fputcsv($out, [$e['kind'], $e['record_id'], $e['effective_on'], $e['date_basis'], $e['amount_cents'], $e['ledger_entry_id']], ',', '"', '');
            }fclose($out);
        }, 'daily-cashup.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
