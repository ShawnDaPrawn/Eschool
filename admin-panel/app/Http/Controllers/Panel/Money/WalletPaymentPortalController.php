<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\WalletPaymentPortalRequest;
use App\Models\Platform\School;
use App\Services\Commerce\VendorOperatorAccess;
use App\Services\Commerce\VendorPaymentTapUri;
use App\Services\Commerce\WalletCardPayments;
use App\Services\Commerce\WalletCounterPayments;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class WalletPaymentPortalController extends Controller
{
    public function handle(WalletPaymentPortalRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $actor = $request->user();
            $vendor = (int) $request->route('vendor');
            $service = app(WalletCounterPayments::class);
            $profile = app(VendorOperatorAccess::class)->profile($actor);
            $review = $outcome = $portrait = null;
            $credentials = [];
            if ($request->routeIs('vendor.payments.lookup')) {
                $credentials = $request->safe()->only(['qr_token']);
                $review = $service->lookup($actor, $vendor, $credentials);
            }
            if ($request->routeIs('vendor.payments.card')) {
                $data = $request->safe()->except(['expected_school_id', 'expected_account_id', 'tap_uri', 'reader_credential']);
                $data['lines'] = array_values(array_filter($data['lines'], fn ($line) => (int) $line['quantity'] > 0));
                $data += VendorPaymentTapUri::parse($request->string('tap_uri')->toString());
                $reader = $request->string('reader_credential')->toString();
                $review = app(WalletCardPayments::class)->prepare($actor, $vendor, $reader, $data);
                $photo = app(WalletCardPayments::class)->portrait($actor, $vendor, $reader, $review['id']);
                $portrait = 'data:image/jpeg;base64,'.base64_encode($photo['bytes']);
                $credentials = ['reader_credential' => $reader, 'portrait_revision' => $review['portrait_revision'], 'portrait_confirmed' => 1];
            }
            if ($request->routeIs('vendor.payments.pay')) {
                $outcome = $service->pay($actor, $vendor, $request->safe()->except(['expected_school_id', 'expected_account_id', 'reader_credential']), $request->string('reader_credential')->toString());
            }
            if ($request->routeIs('vendor.payments.resolve')) {
                $result = $service->resolve($actor, $vendor, $request->string('operation_id')->toString());
                $outcome = $result['payment'];
                if ($outcome !== null) {
                    $outcome['replayed'] = true;
                } else {
                    $outcome = ['operation_id' => $result['operation_id'], 'abandoned' => true];
                }
            }
            $canWrite = app(ModuleGate::class)->writable('wallet_payments') && app(ModuleGate::class)->writable('wallet') && app(ModuleGate::class)->writable('vendors');
            $catalogue = $canWrite ? $service->catalogue($actor, $vendor, $request->integer('page', 1)) : null;
            $canTap = $canWrite && app(ModuleGate::class)->writable('cards');
            $format = fn (string $currency, int $cents) => Money::ofCents($cents, $currency)->format();

            return response()->view('vendor.payments', compact('profile', 'vendor', 'review', 'outcome', 'portrait', 'credentials', 'canWrite', 'canTap', 'catalogue', 'format'));
        }, 3);
    }
}
