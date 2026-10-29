<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\WalletPaymentRequest;
use App\Models\Platform\School;
use App\Services\Commerce\WalletCardPayments;
use App\Services\Commerce\WalletCounterPayments;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class WalletPaymentController extends Controller
{
    public function handle(WalletPaymentRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();

            return $this->respond($request);
        }, 3);
    }

    private function respond(WalletPaymentRequest $request): Response
    {
        $service = app(WalletCounterPayments::class);
        $actor = $request->user();
        $vendor = (int) $request->route('vendor');
        $id = (string) $request->route('payment');
        $credential = $request->header('X-Payment-Reader', '');
        if ($request->routeIs('api.v2.vendor.payments.portrait')) {
            $photo = app(WalletCardPayments::class)->portrait($actor, $vendor, $credential, $id);

            return response($photo['bytes'])->header('Content-Type', $photo['mime'])->header('Cache-Control', 'private, no-store')->header('X-Content-Type-Options', 'nosniff');
        }
        $data = match (true) {
            $request->routeIs('api.v2.commerce.wallet-payments.index') => $service->listing($actor, $request->integer('page', 1)),
            $request->routeIs('api.v2.commerce.wallet-payments.issue') => $service->qr($actor, $request->validated()),
            $request->routeIs('api.v2.commerce.wallet-payments.cancel') => $service->cancel($actor, $id),
            $request->routeIs('api.v2.commerce.wallet-payments.outcome') => $service->personalOutcome($actor, (string) $request->route('operation')),
            $request->routeIs('api.v2.commerce.wallet-payments.show') => $service->personal($actor, $id),
            $request->routeIs('api.v2.vendor.payments.catalogue') => $service->catalogue($actor, $vendor, $request->integer('page', 1)),
            $request->routeIs('api.v2.vendor.payments.lookup') => $service->lookup($actor, $vendor, $request->validated()),
            $request->routeIs('api.v2.vendor.payments.card') => app(WalletCardPayments::class)->prepare($actor, $vendor, $credential, $request->validated()),
            $request->routeIs('api.v2.vendor.payments.pay') => $service->pay($actor, $vendor, $request->validated(), $credential),
            $request->routeIs('api.v2.vendor.payments.resolve') => $service->resolve($actor, $vendor, (string) $request->route('operation')),
            default => $service->receipt($actor, $vendor, $id),
        };

        return response()->json(['error' => false, 'message' => '', 'data' => $data, 'code' => 200])->header('Cache-Control', 'private, no-store');
    }
}
