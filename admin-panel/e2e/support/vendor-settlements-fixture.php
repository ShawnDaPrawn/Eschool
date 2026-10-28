<?php

declare(strict_types=1);
use App\Models\Commerce\VendorSettlement;
use App\Models\Commerce\VendorSettlementOutcome;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Commerce\VendorOrderReconciliation;
use App\Services\Commerce\VendorOrders;
use App\Services\Commerce\VendorSettlementReconciliation;
use App\Services\ParentPolicyService;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

if (! in_array(config('database.connections.mysql.database'), ['eschool_pastoral_test', 'eschool_e2e', 'eschool_test'], true)) {
    throw new RuntimeException('Isolated synthetic schema only');
}
$action = getenv('BILLING_FIXTURE_ACTION');
if ($action === 'stats') {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, function (): array {
        return ['batches' => VendorSettlement::count(), 'settled' => VendorSettlementOutcome::count(), 'settlement_entries' => DB::table('ledger_entries')->where('school_id', app(CurrentSchool::class)->id())->where('source', 'like', 'vendor:settlement:%')->count(), 'vendor_cents' => (int) DB::table('ledger_accounts')->where('school_id', app(CurrentSchool::class)->id())->where('type', 'vendor')->sum('balance_cents'), 'phase6_cents' => (int) DB::table('ledger_accounts')->where('school_id', app(CurrentSchool::class)->id())->whereIn('type', ['clearing', 'due_to_school'])->sum('balance_cents'), 'healthy' => VendorSettlementReconciliation::healthy(app(VendorSettlementReconciliation::class)->inspect()) && VendorOrderReconciliation::healthy(app(VendorOrderReconciliation::class)->inspect()) && app(LedgerService::class)->projectionDifferences() === []];
    });
}
$data = require base_path('e2e/support/vendor-access-fixture.php');

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $finance = User::where('email', $data['finance'])->firstOrFail();
    $email = 'reviewer.'.$data['school'].'@demo.navigateed.test';
    $reviewer = User::create(['first_name' => 'Synthetic', 'last_name' => 'Second Finance', 'email' => $email, 'password' => Hash::make('NavigateDemo2026'), 'status' => 1, 'password_change_required' => false, 'email_verified_at' => now(), 'dob' => '1990-01-01']);
    $reviewer->assignRole('Finance');
    app(MembershipService::class)->grant($reviewer, $data['school'], 'Finance');
    ParentPolicyService::acceptCurrent($reviewer);
    foreach (array_slice($data['orders'], 0, 2) as $order) {
        app(VendorOrders::class)->collect($finance, $data['vendor'], ['operation_id' => (string) Str::uuid(), 'order_number' => $order['number'], 'confirmed' => true]);
    }

    return $data + ['reviewer' => $email, 'today' => now('Africa/Johannesburg')->toDateString()];
});
