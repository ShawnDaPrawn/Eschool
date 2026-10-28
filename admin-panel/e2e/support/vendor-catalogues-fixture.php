<?php

declare(strict_types=1);

use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorProduct;
use App\Models\Commerce\VendorStockBalance;
use App\Models\Commerce\VendorStockMovement;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Commerce\VendorCatalogueReconciliation;
use App\Services\Commerce\VendorCatalogues;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Isolated synthetic schema only');
}
$action = getenv('BILLING_FIXTURE_ACTION');
if (in_array($action, ['stats', 'disable'], true)) {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, function () use ($school, $action): array {
        $finance = User::join('school_memberships as m', 'm.user_id', '=', 'users.id')->where('m.school_id', $school->id)->where('m.role', 'Finance')->firstOrFail(['users.*']);
        if ($action === 'disable') {
            app(ModuleGate::class)->change('vendors', false, $finance);
        }

        return ['vendors' => Vendor::count(), 'products' => VendorProduct::count(), 'movements' => VendorStockMovement::count(), 'on_hand' => (int) VendorStockBalance::sum('on_hand'), 'entries' => DB::table('ledger_entries')->where('school_id', $school->id)->count(), 'healthy' => VendorCatalogueReconciliation::healthy(app(VendorCatalogueReconciliation::class)->inspect())];
    });
}
$data = require base_path('e2e/support/wallet-funding-fixture.php');

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $finance = User::where('email', $data['finance'])->firstOrFail();
    $finance->roles()->firstOrFail()->givePermissionTo('vendors-manage');
    app(ModuleGate::class)->change('wallet', false, $finance);
    app(ModuleGate::class)->change('vendors', true, $finance);
    $category = app(VendorCatalogues::class)->category($finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'key' => 'food', 'name' => 'Synthetic food', 'status' => 'active']);

    return $data + ['vendor_category' => $category->id];
});
