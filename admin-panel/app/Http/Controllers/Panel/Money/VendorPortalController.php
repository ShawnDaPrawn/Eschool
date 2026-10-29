<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\VendorPortalRequest;
use App\Services\Commerce\VendorCounterOrders;
use App\Services\Commerce\VendorOperatorAccess;
use App\Services\Commerce\VendorSettlements;
use App\Services\Commerce\VendorSignIn;
use App\Services\Platform\Identity\PortalSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VendorPortalController extends Controller
{
    public function signInForm(): Response
    {
        return response()->view('vendor.sign-in', ['requirements' => null]);
    }

    public function signIn(VendorPortalRequest $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $result = app(VendorSignIn::class)->signIn($request, $request->validated(), panel: true);
        if (! $result['authenticated']) {
            return response()->view('vendor.sign-in', ['requirements' => $result], 409);
        }

        return redirect()->route('vendor.index');
    }

    public function signOut(VendorPortalRequest $request): Response
    {
        PortalSession::signOut($request, $request->user());

        return redirect()->route('vendor.sign-in-form');
    }

    public function index(VendorPortalRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            $profile = app(VendorOperatorAccess::class)->profile($request->user());
            $vendor = $request->integer('vendor_id') ?: $profile['counters'][0]['id'];
            $listing = app(VendorCounterOrders::class)->listing($request->user(), $vendor, $request->validated());

            return response()->view('vendor.counter', compact('profile', 'vendor', 'listing'));
        });
    }

    public function lookup(VendorPortalRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            $vendor = (int) $request->route('vendor');
            $order = app(VendorCounterOrders::class)->lookup($request->user(), $vendor, $request->validated());
            $profile = app(VendorOperatorAccess::class)->profile($request->user());

            return response()->view('vendor.order', compact('profile', 'vendor', 'order'));
        });
    }

    public function show(VendorPortalRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            $vendor = (int) $request->route('vendor');
            $order = app(VendorCounterOrders::class)->receipt($request->user(), $vendor, (int) $request->route('order'));
            $profile = app(VendorOperatorAccess::class)->profile($request->user());

            return response()->view('vendor.order', compact('profile', 'vendor', 'order'));
        });
    }

    public function settlements(VendorPortalRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            $profile = app(VendorOperatorAccess::class)->profile($request->user());
            $vendor = (int) $request->route('vendor');
            $service = app(VendorSettlements::class);
            $listing = $service->listingForOperator($request->user(), $vendor, $request->validated());
            $receipt = $request->route('settlement') === null ? null : $service->receiptForOperator($request->user(), $vendor, (int) $request->route('settlement'));

            return response()->view('vendor.settlements', compact('profile', 'vendor', 'listing', 'receipt'));
        });
    }

    public function collect(VendorPortalRequest $request): Response
    {
        $vendor = (int) $request->route('vendor');
        $order = app(VendorCounterOrders::class)->collect($request->user(), $vendor, $request->safe()->except(['expected_account_id', 'expected_school_id']));

        return redirect()->route('vendor.order', ['vendor' => $vendor, 'order' => $order['id']])->with('vendor_collection_success', $order['newly_collected'] ? __('Order validated for one collection. Hand over the listed items once.') : __('Collection was already recorded. Check the counter record; do not hand over goods again.'));
    }
}
