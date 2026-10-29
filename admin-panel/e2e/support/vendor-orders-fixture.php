<?php

declare(strict_types=1);

use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorOrder;
use App\Models\Commerce\VendorOrderAction;
use App\Models\Commerce\VendorStockBalance;
use App\Models\Money\WalletAccount;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Commerce\VendorCatalogueReconciliation;
use App\Services\Commerce\VendorCatalogues;
use App\Services\Commerce\VendorOrderReconciliation;
use App\Services\Commerce\VendorOrders;
use App\Services\Money\WalletDeposits;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Isolated synthetic schema only');
}
$action = getenv('BILLING_FIXTURE_ACTION');
if (in_array($action, ['stats', 'disable'], true)) {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, function () use ($action): array {
        if ($action === 'disable') {
            // Select the membership through the active tenant explicitly.
            $finance = User::join('school_memberships as m', 'm.user_id', '=', 'users.id')->where('m.school_id', app(CurrentSchool::class)->id())->where('m.role', 'Finance')->firstOrFail(['users.*']);
            app(ModuleGate::class)->change('vendors', false, $finance);
        }

        return ['orders' => VendorOrder::count(), 'collected' => VendorOrderAction::where('action', 'collected')->count(), 'cancelled' => VendorOrderAction::where('action', 'cancelled')->count(), 'wallet_cents' => app(LedgerService::class)->balance(WalletAccount::sole()->ledger_account_id)->cents, 'vendor_cents' => app(LedgerService::class)->balance(Vendor::sole()->ledger_account_id)->cents, 'on_hand' => VendorStockBalance::sole()->on_hand, 'healthy' => VendorOrderReconciliation::healthy(app(VendorOrderReconciliation::class)->inspect()) && VendorCatalogueReconciliation::healthy(app(VendorCatalogueReconciliation::class)->inspect()) && app(LedgerService::class)->projectionDifferences() === []];
    });
}
$data = require base_path('e2e/support/vendor-catalogues-fixture.php');

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $finance = User::where('email', $data['finance'])->firstOrFail();
    app(ModuleGate::class)->change('wallet', true, $finance);
    app(WalletDeposits::class)->policy($finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true]);
    $parent = User::findOrFail($data['parents'][0]);
    $claim = app(WalletDeposits::class)->claim($parent, ['operation_id' => (string) Str::uuid(), 'amount' => '100.00', 'method' => 'eft', 'source_reference' => 'SYNTHETIC-ORDER-PARENT', 'paid_on' => now()->toDateString(), 'evidence' => 'Synthetic original parent order backing', 'confirmed' => true]);
    app(WalletDeposits::class)->verify($finance, $claim->id, ['operation_id' => (string) Str::uuid(), 'amount' => '100.00', 'method' => 'eft', 'source_reference' => 'SYNTHETIC-ORDER-VERIFIED', 'received_on' => now()->toDateString(), 'evidence' => 'Independent actual synthetic parent backing', 'verified_original_holder' => true, 'verified_actual_receipt' => true]);
    $wallet = WalletAccount::sole();
    $catalogues = app(VendorCatalogues::class);
    $vendor = $catalogues->vendor($finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'category_id' => $data['vendor_category'], 'name' => 'Synthetic order counter', 'kind' => 'school', 'status' => 'active', 'legal_name' => 'Synthetic school counter', 'vat_registered' => false, 'refund_bearer' => 'school', 'chargeback_bearer' => 'school', 'settlement_method' => 'school_internal', 'settlement_cycle' => 'manual', 'settlement_delay_days' => 0, 'settlement_terms' => 'Retain original order proceeds pending collection and review.']);
    $product = $catalogues->product($finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'vendor_id' => $vendor->id, 'category_id' => $vendor->category_id, 'sku' => 'SYNTHETIC-01', 'name' => 'Synthetic sandwich', 'unit_label' => 'each', 'price' => '12.50', 'vat_kind' => 'none', 'vat_rate_bps' => 0, 'tracks_stock' => true, 'status' => 'active']);
    $catalogues->stock($finance, ['operation_id' => (string) Str::uuid(), 'product_id' => $product->id, 'source_reference' => 'SYNTHETIC-COUNTED-DELIVERY', 'kind' => 'receipt', 'units_delta' => 10, 'private_reason' => 'Actual synthetic counted sandwich delivery']);
    $orders = [];
    for ($i = 0; $i < 4; $i++) {
        $quote = app(VendorOrders::class)->quote($parent, ['vendor_id' => $vendor->id, 'wallet_id' => $wallet->id, 'lines' => [['product_id' => $product->id, 'quantity' => 1]]]);
        $order = app(VendorOrders::class)->purchase($parent, ['operation_id' => (string) Str::uuid(), 'quote' => $quote['quote'], 'confirmed' => true]);
        $orders[] = ['id' => $order->id, 'number' => $order->order_number, 'qr' => 'navigateed:order:'.$order->private_qr_token];
    }

    return $data + ['vendor' => $vendor->id, 'orders' => $orders];
});
