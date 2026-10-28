<?php

declare(strict_types=1);

use App\Models\Money\WalletAccount;
use App\Models\Money\WalletDepositAction;
use App\Models\Money\WalletDepositClaim;
use App\Models\Money\WalletParentDeposit;
use App\Models\User;
use App\Services\Money\WalletDeposits;
use App\Services\Money\WalletFundingReconciliation;
use App\Services\Platform\Ledger\LedgerService;
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
    return app(CurrentSchool::class)->run((int) getenv('BILLING_FIXTURE_SCHOOL'), function () use ($action): array {
        $actor = User::findOrFail(WalletDepositClaim::firstOrFail()->actor_id);
        if ($action === 'disable') {
            $finance = User::join('school_memberships as m', 'm.user_id', '=', 'users.id')->where('m.school_id', app(CurrentSchool::class)->id())->where('m.role', 'Finance')->firstOrFail(['users.*']);
            app(ModuleGate::class)->change('wallet', false, $finance);
        }
        $wallet = WalletAccount::where('user_id', $actor->id)->sole();

        return ['claims' => WalletDepositClaim::count(), 'verified' => WalletParentDeposit::count(), 'rejected' => WalletDepositAction::where('status', 'rejected')->count(), 'parent_cents' => app(LedgerService::class)->balance($wallet->ledger_account_id)->cents, 'entries' => DB::table('ledger_entries')->where('school_id', app(CurrentSchool::class)->id())->count(), 'master_accounts' => DB::table('ledger_accounts')->where('school_id', app(CurrentSchool::class)->id())->where('reference', 'wallet:master:ZAR')->count(), 'healthy' => WalletFundingReconciliation::healthy(app(WalletFundingReconciliation::class)->inspect())];
    });
}
$data = require base_path('e2e/support/wallet-funding-fixture.php');

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $finance = User::where('email', $data['finance'])->firstOrFail();
    app(WalletDeposits::class)->policy($finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true]);
    $parent = User::findOrFail($data['parents'][0]);
    $first = app(WalletDeposits::class)->claim($parent, ['operation_id' => (string) Str::uuid(), 'amount' => '100.25', 'method' => 'eft', 'source_reference' => 'SYNTHETIC-PARENT-CLAIM', 'paid_on' => now()->toDateString(), 'evidence' => 'Private synthetic parent-supplied proof', 'confirmed' => true]);
    $second = app(WalletDeposits::class)->claim($parent, ['operation_id' => (string) Str::uuid(), 'amount' => '10.00', 'method' => 'cash', 'source_reference' => 'SYNTHETIC-UNVERIFIED-CLAIM', 'paid_on' => now()->toDateString(), 'evidence' => 'Private synthetic unverified proof', 'confirmed' => true]);

    return $data + ['claim' => $first->id, 'reject_claim' => $second->id];
});
