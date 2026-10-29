<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\VendorPaymentReaderPanelRequest;
use App\Models\Cards\CardReaderDevice;
use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorCardReader;
use App\Services\Cards\CardReaders;
use App\Services\Commerce\VendorPaymentReaders;
use App\Services\Platform\Modules\ModuleGate;
use Symfony\Component\HttpFoundation\Response;

class VendorPaymentReaderPanelController extends Controller
{
    public function handle(VendorPaymentReaderPanelRequest $request): Response
    {
        $pairing = null;
        if ($request->routeIs('vendor-payment-readers.register')) {
            $pairing = app(VendorPaymentReaders::class)->register($request->user(), $request->integer('vendor_id'), $request->safe()->except(['expected_school_id', 'expected_account_id', 'vendor_id']));
        }
        if ($request->routeIs('vendor-payment-readers.revoke')) {
            $binding = VendorCardReader::where('device_id', (string) $request->route('reader'))->firstOrFail();
            app(CardReaders::class)->revoke($request->user(), $binding->device_id);
        }
        $vendors = Vendor::orderBy('name')->pluck('name', 'id')->all();
        $bindings = VendorCardReader::orderByDesc('id')->paginate(30);
        $devices = CardReaderDevice::whereIn('id', $bindings->pluck('device_id'))->get()->keyBy('id');
        $canWrite = app(ModuleGate::class)->writable('wallet_payments') && app(ModuleGate::class)->writable('cards');

        return response()->view('money.vendors.payment-readers', compact('pairing', 'vendors', 'bindings', 'devices', 'canWrite'));
    }
}
