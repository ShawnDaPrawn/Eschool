<?php

declare(strict_types=1);
use App\Models\Money\WalletAccount;
use App\Models\Money\WalletDeskAction;
use App\Models\Money\WalletDeskTopup;
use App\Models\Platform\Role;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\Money\WalletDeskReconciliation;
use App\Services\Money\WalletFundingReconciliation;
use App\Services\Money\WalletFundingService;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Database\Seeders\DemoSchoolSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Desk fixtures require an isolated test database.');
}
$action = getenv('BILLING_FIXTURE_ACTION');
if (in_array($action, ['stats', 'disable'], true)) {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, function () use ($school, $action): array {
        $actor = User::join('school_memberships as m', 'm.user_id', '=', 'users.id')->where('m.school_id', $school->id)->where('m.role', 'Finance')->firstOrFail(['users.*']);
        if ($action === 'disable') {
            app(ModuleGate::class)->change('wallet', false, $actor);
        }$wallet = WalletAccount::first();

        return ['topups' => WalletDeskTopup::count(), 'actions' => WalletDeskAction::count(), 'master_cents' => app(WalletFundingService::class)->balance($actor)['balance_cents'], 'personal_cents' => $wallet ? app(LedgerService::class)->balance($wallet->ledger_account_id)->cents : 0, 'pending_cents' => (int) DB::table('ledger_accounts')->where('school_id', $school->id)->whereIn('id', WalletDeskTopup::pluck('reserve_account_id'))->sum('balance_cents'), 'healthy' => WalletDeskReconciliation::healthy(app(WalletDeskReconciliation::class)->inspect()) && WalletFundingReconciliation::healthy(app(WalletFundingReconciliation::class)->inspect()) && app(LedgerService::class)->projectionDifferences() === [], 'phase6_cents' => (int) DB::table('ledger_accounts')->where('school_id', $school->id)->whereIn('type', ['clearing', 'due_to_school'])->sum('balance_cents')];
    });
}
$data = require base_path('e2e/support/wallet-funding-fixture.php');

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $actor = User::where('email', $data['finance'])->firstOrFail();
    $actor->givePermissionTo('wallet-desk-policy-manage');
    app(WalletFundingService::class)->policy($actor, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true]);
    app(WalletFundingService::class)->record($actor, ['operation_id' => (string) Str::uuid(), 'method' => 'cash', 'source_reference' => 'SYNTHETIC-DESK-SCHOOL-FUNDING', 'amount' => '100.00', 'received_on' => '2026-01-01', 'school_owned' => true, 'evidence' => 'Synthetic verified school-owned desk funds']);
    $role = Role::firstOrCreate(['name' => 'Desk staff', 'guard_name' => 'web']);
    $role->forceFill(['custom_role' => 1])->save();
    $role->givePermissionTo('wallet-desk-process');
    $tag = substr(str_replace('-', '', (string) Str::uuid()), 0, 12);
    $reviewer = new User;
    $reviewer->forceFill(['first_name' => 'Synthetic', 'last_name' => 'Desk reviewer '.$tag, 'email' => 'desk-reviewer-'.$tag.'@'.DemoSchoolSeeder::EMAIL_DOMAIN, 'password' => Hash::make(DemoSchoolSeeder::PASSWORD), 'gender' => 'Female', 'mobile' => '0821234567', 'image' => null, 'status' => 1, 'dob' => '1990-01-01', 'email_verified_at' => now('UTC'), 'password_change_required' => false])->save();
    $reviewer->assignRole($role);
    SchoolMembership::updateOrCreate(['school_id' => $data['school'], 'user_id' => $reviewer->id, 'role' => $role->name], ['status' => 'active', 'starts_on' => now()->startOfYear()->toDateString()]);

    return $data + ['approver' => $reviewer->email, 'learner_user' => (int) Students::findOrFail($data['learners'][0]['id'])->user_id];
});
