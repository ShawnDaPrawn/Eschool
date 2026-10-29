<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\VendorOrderPanelRequest;
use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorOrder;
use App\Models\Commerce\VendorOrderAction;
use App\Models\Commerce\VendorOrderLine;
use App\Models\Platform\School;
use App\Policies\Commerce\VendorPolicy;
use App\Policies\Money\WalletPolicy;
use App\Services\Commerce\VendorOrders;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class VendorOrderPanelController extends Controller
{
    public function index(VendorOrderPanelRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            app(VendorPolicy::class)->finance($request->user());
            $selected = $request->filled('vendor_id') ? Vendor::findOrFail($request->integer('vendor_id')) : null;
            $options = Vendor::orderBy('name')->pluck('name', 'id')->all();
            $rows = VendorOrder::whereNotNull('sealed_at')->when($selected, fn ($q) => $q->where('vendor_id', $selected->id))->orderByDesc('id')->paginate(30);
            $actions = VendorOrderAction::whereIn('order_id', $rows->pluck('id'))->get()->keyBy('order_id');
            $canWrite = app(ModuleGate::class)->writable('vendors');

            return response()->view('money.vendors.orders', compact('selected', 'options', 'rows', 'actions', 'canWrite'));
        });
    }

    public function show(VendorOrderPanelRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            app(VendorPolicy::class)->finance($request->user());
            $order = VendorOrder::findOrFail((int) $request->route('order'));
            $lines = VendorOrderLine::where('order_id', $order->id)->orderBy('id')->get();
            $state = app(VendorOrders::class)->state($order);
            $canWrite = app(ModuleGate::class)->writable('vendors');
            $canCancel = false;
            if ($canWrite && app(ModuleGate::class)->writable('wallet')) {
                try {
                    app(WalletPolicy::class)->finance($request->user(), true);
                    $canCancel = $state === 'ready';
                } catch (HttpExceptionInterface) {
                    $canCancel = false;
                }
            }

            return response()->view('money.vendors.order', compact('order', 'lines', 'state', 'canWrite', 'canCancel'));
        });
    }

    public function collect(VendorOrderPanelRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $alreadyCollected = VendorOrderAction::where('operation_id', $request->validated('operation_id'))->where('action', 'collected')->exists();
            $order = app(VendorOrders::class)->collect($request->user(), (int) $request->route('vendor'), $request->safe()->except(['expected_account_id', 'expected_school_id']));
            $message = $alreadyCollected ? __('Collection was already recorded. Check the counter record; do not hand over goods again.') : __('Order validated for one collection. Hand over the listed items once.');

            return redirect()->route('vendor-orders.show', ['order' => $order->id])->with('success', $message);
        }, 3);
    }

    public function cancel(VendorOrderPanelRequest $request): Response
    {
        $order = app(VendorOrders::class)->cancelByFinance($request->user(), (int) $request->route('order'), $request->safe()->except(['expected_account_id', 'expected_school_id']));

        return redirect()->route('vendor-orders.show', ['order' => $order->id])->with('success', __('Uncollected order cancelled. Money returned only to the original wallet.'));
    }
}
