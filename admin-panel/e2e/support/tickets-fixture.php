<?php

declare(strict_types=1);

use App\Models\Commerce\Ticket;
use App\Models\Commerce\TicketAction;
use App\Models\Commerce\TicketType;
use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorOperator;
use App\Models\Commerce\VendorOperatorAction;
use App\Models\Commerce\VendorOrderLine;
use App\Models\Money\WalletAccount;
use App\Models\Money\WalletSetting;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Commerce\TicketCatalogues;
use App\Services\Commerce\TicketHolders;
use App\Services\Commerce\TicketReconciliation;
use App\Services\Commerce\VendorCatalogues;
use App\Services\Commerce\VendorOperators;
use App\Services\Commerce\VendorOrders;
use App\Services\Money\WalletSettings;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$database = (string) config('database.connections.mysql.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Isolated synthetic ticket fixture only');
}
$action = getenv('BILLING_FIXTURE_ACTION');
if (in_array($action, ['stats', 'pause', 'revoke'], true)) {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, function () use ($action): array {
        $finance = User::join('school_memberships as m', 'm.user_id', '=', 'users.id')->where('m.school_id', app(CurrentSchool::class)->id())->where('m.role', 'Finance')->firstOrFail(['users.*']);
        if ($action === 'pause') {
            app(ModuleGate::class)->change('ticketing', false, $finance);
        }
        if ($action === 'revoke') {
            $binding = VendorOperator::firstOrFail();
            $latest = VendorOperatorAction::where('operator_id', $binding->id)->orderByDesc('revision')->firstOrFail();
            app(VendorOperators::class)->bind($finance, ['operation_id' => (string) Str::uuid(), 'vendor_id' => $binding->vendor_id, 'user_id' => $binding->user_id, 'revision' => $latest->revision, 'status' => 'revoked', 'private_reason' => 'Synthetic admission operator removed from this counter', 'confirmed_operator_identity' => true]);
        }

        return ['tickets' => Ticket::count(), 'redeemed' => TicketAction::where('action', 'redeemed')->count(), 'transferred' => TicketAction::where('action', 'transferred')->count(), 'types' => TicketType::count(), 'entries' => DB::table('ledger_entries')->where('school_id', app(CurrentSchool::class)->id())->count(), 'healthy' => TicketReconciliation::healthy(app(TicketReconciliation::class)->inspect())];
    });
}
$data = require base_path('e2e/support/vendor-access-fixture.php');

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $finance = User::where('email', $data['finance'])->firstOrFail();
    app(ModuleGate::class)->change('ticketing', true, $finance);
    app(WalletSettings::class)->save($finance, ['operation_id' => (string) Str::uuid(), 'revision' => WalletSetting::sole()->revision, 'currency' => 'ZAR', 'daily_spend_cents' => 50000, 'child_limits_required' => true]);
    $time = now('Africa/Johannesburg');
    $catalogue = app(TicketCatalogues::class);
    $event = $catalogue->event($finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic original school event', 'description' => 'Original synthetic event and explicit admissions', 'status' => 'active', 'sales_from' => $time->copy()->subHour()->format('Y-m-d\TH:i'), 'sales_until' => $time->copy()->addHour()->format('Y-m-d\TH:i'), 'starts_at' => $time->copy()->subMinutes(10)->format('Y-m-d\TH:i'), 'ends_at' => $time->copy()->addHours(2)->format('Y-m-d\TH:i'), 'private_reason' => 'Explicit original synthetic event sale and entry windows']);
    $parent = User::findOrFail($data['parents'][0]);
    $vendor = Vendor::findOrFail($data['vendor']);
    $wallet = WalletAccount::where('user_id', $parent->id)->sole();
    $rows = [];
    foreach (['general', 'named'] as $kind) {
        $product = app(VendorCatalogues::class)->product($finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'vendor_id' => $vendor->id, 'category_id' => $vendor->category_id, 'sku' => 'ADMISSION-'.strtoupper($kind), 'name' => 'Synthetic '.$kind.' admission', 'unit_label' => 'admission', 'price' => '12.50', 'vat_kind' => 'none', 'vat_rate_bps' => 0, 'tracks_stock' => false, 'status' => 'active']);
        $type = $catalogue->type($finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'event_id' => $event->id, 'product_id' => $product->id, 'kind' => $kind, 'status' => 'active', 'transfer_rule' => 'families', 'capacity' => 1, 'private_reason' => 'Explicit original synthetic admission capacity and family transfer rule']);
        $cart = ['vendor_id' => $vendor->id, 'wallet_id' => $wallet->id, 'lines' => [['product_id' => $product->id, 'quantity' => 1]]];
        if ($kind === 'named') {
            $cart['learner_id'] = $data['learners'][0]['id'];
        }
        $quote = app(VendorOrders::class)->quote($parent, $cart);
        $order = app(VendorOrders::class)->purchase($parent, ['operation_id' => (string) Str::uuid(), 'quote' => $quote['quote'], 'confirmed' => true]);
        $ticket = Ticket::whereIn('order_line_id', VendorOrderLine::where('order_id', $order->id)->select('id'))->sole();
        $rows[] = app(TicketHolders::class)->receipt($parent, $ticket->id);
    }

    return $data + ['event' => $event->id, 'tickets' => $rows];
});
