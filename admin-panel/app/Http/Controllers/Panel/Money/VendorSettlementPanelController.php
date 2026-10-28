<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\VendorSettlementPanelRequest;
use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorSettlement;
use App\Models\Commerce\VendorSettlementApproval;
use App\Models\Commerce\VendorSettlementLine;
use App\Models\Commerce\VendorSettlementOutcome;
use App\Policies\Commerce\VendorPolicy;
use App\Services\Commerce\VendorSettlements;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VendorSettlementPanelController extends Controller
{
    public function index(VendorSettlementPanelRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            app(VendorPolicy::class)->finance($request->user());
            $today = now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString();
            $period = ['period_from' => $request->input('period_from', $today), 'period_to' => $request->input('period_to', $today)];
            $selected = $request->filled('vendor_id') ? Vendor::findOrFail($request->integer('vendor_id')) : null;
            $options = Vendor::orderBy('name')->pluck('name', 'id')->all();
            $orders = $selected ? app(VendorSettlements::class)->eligible($request->user(), $selected->id, $period) : [];
            $rows = VendorSettlement::whereNotNull('sealed_at')->when($selected, fn ($q) => $q->where('vendor_id', $selected->id))->orderByDesc('id')->paginate(30);
            $canWrite = app(ModuleGate::class)->writable('vendors');

            return response()->view('money.vendors.settlements', compact('selected', 'period', 'options', 'orders', 'rows', 'canWrite'));
        });
    }

    public function show(VendorSettlementPanelRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            app(VendorPolicy::class)->finance($request->user());
            $row = VendorSettlement::whereNotNull('sealed_at')->findOrFail((int) $request->route('settlement'));
            $lines = VendorSettlementLine::where('settlement_id', $row->id)->orderBy('order_id')->get();
            $approval = VendorSettlementApproval::where('settlement_id', $row->id)->first();
            $outcome = VendorSettlementOutcome::where('settlement_id', $row->id)->first();
            $canWrite = app(ModuleGate::class)->writable('vendors');

            return response()->view('money.vendors.settlement', compact('row', 'lines', 'approval', 'outcome', 'canWrite'));
        });
    }

    public function prepare(VendorSettlementPanelRequest $request): Response
    {
        $row = app(VendorSettlements::class)->prepare($request->user(), $request->safe()->except(['expected_school_id', 'expected_account_id']));

        return redirect()->route('vendor-settlements.show', ['settlement' => $row->id])->with('success', __('Original collected orders reserved for settlement. No money has moved.'));
    }

    public function approve(VendorSettlementPanelRequest $request): Response
    {
        $row = app(VendorSettlements::class)->approve($request->user(), (int) $request->route('settlement'), $request->safe()->except(['expected_school_id', 'expected_account_id']));

        return redirect()->route('vendor-settlements.show', ['settlement' => $row->id])->with('success', __('Independent settlement approval recorded. No money has moved.'));
    }

    public function finish(VendorSettlementPanelRequest $request): Response
    {
        $row = app(VendorSettlements::class)->finish($request->user(), (int) $request->route('settlement'), $request->safe()->except(['expected_school_id', 'expected_account_id']));

        return redirect()->route('vendor-settlements.show', ['settlement' => $row->id])->with('success', __('Original vendor settlement recorded. Do not pay this batch again.'));
    }

    public function export(VendorSettlementPanelRequest $request): Response
    {
        // Materialize the bounded authorised report before streaming or losing tenant context.
        $rows = VendorSettlement::whereNotNull('sealed_at')->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', $request->integer('vendor_id')))->when($request->filled('period_from'), fn ($q) => $q->where('period_from', '>=', $request->input('period_from')))->when($request->filled('period_to'), fn ($q) => $q->where('period_to', '<=', $request->input('period_to')))->orderBy('id')->limit(10001)->get();
        abort_if($rows->count() > 10000, 422, 'Narrow this report before exporting.');
        $data = $rows->map(function ($row): array {
            $outcome = VendorSettlementOutcome::where('settlement_id', $row->id)->first();

            return [$row->id, $row->vendor_id, $row->private_vendor_snapshot['name'], $row->period_from, $row->period_to, $row->currency, $row->gross_cents, $row->sales_net_cents, $row->vat_cents, $row->collected_by, $row->collector_fee_cents, $row->payout_cents, app(VendorSettlements::class)->state($row), $outcome?->paid_on, $outcome?->ledger_entry_id];
        })->all();

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['settlement_id', 'vendor_id', 'vendor', 'period_from', 'period_to', 'currency', 'gross_cents', 'sales_net_cents', 'vat_cents', 'collected_by', 'collector_fee_cents', 'payout_cents', 'status', 'paid_on', 'ledger_entry_id'], ',', '"', '');
            foreach ($data as $row) {
                fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[\\s]*[=+@-]/u', $v) ? "'".$v : $v, $row), ',', '"', '');
            }fclose($out);
        }, 'vendor-settlements.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
