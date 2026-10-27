<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\FeeReportPanelRequest;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountingSetting;
use App\Services\Money\FeeAccountingSettings;
use App\Services\Money\FeeReporting;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

class FeeReportPanelController extends Controller
{
    public function index(FeeReportPanelRequest $request): Response
    {
        $currencies = FeeAccount::orderBy('currency')->distinct()->pluck('currency')->mapWithKeys(fn ($v) => [$v => $v])->all();
        $currency = $request->input('currency', array_key_first($currencies) ?? Money::defaultCurrency());
        $currencies[$currency] = $currency;
        $report = app(FeeReporting::class)->ageing($request->user(), $currency);
        $status = $request->input('status', 'all');
        $rows = array_values(array_filter($report['accounts'], fn ($r) => match ($status) {
            'overdue' => $r['outstanding_cents'] > $r['buckets']['current'], 'credit' => $r['credit_cents'] > 0, default => true,
        }));
        $page = $request->integer('page', 1);
        $accounts = new LengthAwarePaginator(array_slice($rows, ($page - 1) * 30, 30), count($rows), 30, $page, ['path' => $request->url(), 'query' => $request->query()]);
        $today = $report['as_of'];
        $monthStart = substr($today, 0, 8).'01';

        return response()->view('money.reports.index', compact('report', 'accounts', 'currencies', 'currency', 'status', 'today', 'monthStart'));
    }

    public function settings(FeeReportPanelRequest $request): Response
    {
        $settings = FeeAccountingSetting::first();
        $types = FeeAccountingSettings::labels();
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.reports.settings', compact('settings', 'types', 'canWrite'));
    }

    public function saveSettings(FeeReportPanelRequest $request): Response
    {
        app(FeeAccountingSettings::class)->save($request->user(), $request->validated());

        return redirect()->route('fee-reporting.settings', [], 303)->with('success', __('Accounting export mapping saved.'));
    }

    public function export(FeeReportPanelRequest $request): Response
    {
        $result = app(FeeReporting::class)->export($request->user(), $request->validated());

        return response($result['csv'], 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="'.$result['filename'].'"', 'X-Content-Type-Options' => 'nosniff']);
    }
}
