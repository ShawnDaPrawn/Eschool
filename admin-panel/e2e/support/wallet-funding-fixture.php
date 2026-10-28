<?php

declare(strict_types=1);
use App\Models\Money\WalletFunding;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Money\WalletFundingReconciliation;
use App\Services\Money\WalletFundingService;
use App\Services\Money\WalletSettings;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Wallet funding fixtures require an isolated test database.');
}
$action = getenv('BILLING_FIXTURE_ACTION');
if (in_array($action, ['stats', 'disable'], true)) {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, function () use ($school, $action): array {
        $actor = User::join('school_memberships as m', 'm.user_id', '=', 'users.id')->where('m.school_id', $school->id)->where('m.role', 'Finance')->firstOrFail(['users.*']);
        if ($action === 'disable') {
            app(ModuleGate::class)->change('wallet', false, $actor);
        }

        return ['receipts' => WalletFunding::count(), 'external_identities' => DB::table('financial_receipts')->where('school_id', $school->id)->count(), 'master_cents' => app(WalletFundingService::class)->balance($actor)['balance_cents'], 'healthy' => WalletFundingReconciliation::healthy(app(WalletFundingReconciliation::class)->inspect()), 'phase6_cents' => (int) DB::table('ledger_accounts')->where('school_id', $school->id)->whereIn('type', ['clearing', 'due_to_school'])->sum('balance_cents')];
    });
}
$data = require base_path('e2e/support/personal-wallets-fixture.php');

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $actor = User::where('email', $data['finance'])->firstOrFail();
    app(WalletSettings::class)->save($actor, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'currency' => 'ZAR', 'daily_spend_cents' => 5000, 'child_limits_required' => true]);

    return $data;
});
