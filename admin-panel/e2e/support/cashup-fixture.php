<?php

declare(strict_types=1);
use App\Models\Money\CashupCount;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Money\CashFlows;
use App\Services\Money\WalletDeposits;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

if (! in_array(config('database.connections.mysql.database'), ['eschool_pastoral_test', 'eschool_e2e'], true)) {
    throw new RuntimeException('Isolated synthetic schema only');
}
function cashupFixtureDeposit(User $finance, User $parent, string $method, string $amount): void
{
    $date = now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString();
    $s = app(WalletDeposits::class);
    $claim = $s->claim($parent, ['operation_id' => (string) Str::uuid(), 'amount' => $amount, 'method' => $method, 'source_reference' => 'SYNTHETIC-COUNT-CLAIM-'.Str::uuid(), 'paid_on' => $date, 'evidence' => 'Original synthetic receipt before physical cash count', 'confirmed' => true]);
    $s->verify($finance, $claim->id, ['operation_id' => (string) Str::uuid(), 'amount' => $amount, 'method' => $method, 'source_reference' => 'SYNTHETIC-COUNT-VERIFIED-'.Str::uuid(), 'received_on' => $date, 'evidence' => 'Actually verified synthetic outside receipt held by this Finance operator', 'verified_original_holder' => true, 'verified_actual_receipt' => true]);
}
$action = getenv('BILLING_FIXTURE_ACTION');
if (in_array($action, ['stats', 'late', 'disable'], true)) {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, function () use ($action): array {
        $finance = User::join('school_memberships as m', 'm.user_id', '=', 'users.id')->where('m.school_id', app(CurrentSchool::class)->id())->where('m.role', 'Finance')->firstOrFail(['users.*']);
        if ($action === 'disable') {
            app(ModuleGate::class)->change('wallet', false, $finance);
        }
        if ($action === 'late') {
            $parent = User::join('school_memberships as m', 'm.user_id', '=', 'users.id')->where('m.school_id', app(CurrentSchool::class)->id())->where('m.role', 'Parent')->firstOrFail(['users.*']);
            cashupFixtureDeposit($finance, $parent, 'cash', '5.00');
        }
        $flow = app(CashFlows::class)->inspect('ZAR');
        $latest = CashupCount::where('operator_id', $finance->id)->orderByDesc('revision')->first();

        return ['counts' => CashupCount::count(), 'revision' => $latest?->revision, 'counted_cents' => $latest?->counted_cents, 'variance_cents' => $latest?->variance_cents, 'cash_cents' => $flow['cash_asset_cents'], 'entries' => DB::table('ledger_entries')->where('school_id', app(CurrentSchool::class)->id())->count(), 'healthy' => CashFlows::healthy($flow)];
    });
}
$data = require base_path('e2e/support/wallet-funding-fixture.php');

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $finance = User::where('email', $data['finance'])->firstOrFail();
    $finance->roles()->firstOrFail()->givePermissionTo('money-cashup-manage');
    $parent = User::findOrFail($data['parents'][0]);
    app(WalletDeposits::class)->policy($finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true]);
    cashupFixtureDeposit($finance, $parent, 'cash', '20.00');
    cashupFixtureDeposit($finance, $parent, 'eft', '60.00');

    return $data + ['operator_id' => (int) $finance->id, 'today' => now('Africa/Johannesburg')->toDateString()];
});
