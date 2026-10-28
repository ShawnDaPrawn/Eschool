<?php

declare(strict_types=1);
use App\Models\Money\WalletAccount;
use App\Models\Money\WalletTransfer;
use App\Models\Money\WalletTransferPolicy;
use App\Models\Students;
use App\Models\User;
use App\Services\Money\WalletDeskTopups;
use App\Services\Money\WalletFundingService;
use App\Services\Money\Wallets;
use App\Services\Money\WalletTransferReconciliation;
use App\Services\Money\WalletTransfers;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

if (! str_ends_with((string) config('database.connections.mysql.database'), '_test') && config('database.connections.mysql.database') !== 'eschool_e2e') {
    throw new RuntimeException('Isolated test schema only');
}
$action = getenv('BILLING_FIXTURE_ACTION');
if (in_array($action, ['stats', 'move', 'disable'], true)) {
    return app(CurrentSchool::class)->run((int) getenv('BILLING_FIXTURE_SCHOOL'), function () use ($action): array {
        $own = WalletAccount::join('school_memberships as m', function ($j) {
            $j->on('m.user_id', '=', 'wallet_accounts.user_id')->on('m.school_id', '=', 'wallet_accounts.school_id');
        })->where('m.role', 'Parent')->firstOrFail(['wallet_accounts.*']);
        $actor = User::findOrFail($own->user_id);
        $child = WalletAccount::where('user_id', '<>', $own->user_id)->firstOrFail();
        if ($action === 'move') {
            app(WalletTransfers::class)->transfer($actor, ['operation_id' => (string) Str::uuid(), 'from_wallet_id' => $own->id, 'to_wallet_id' => $child->id, 'amount' => '10.00', 'confirmed' => true]);
            app(WalletTransfers::class)->transfer($actor, ['operation_id' => (string) Str::uuid(), 'from_wallet_id' => $child->id, 'to_wallet_id' => $own->id, 'amount' => '2.25', 'confirmed' => true]);
        }
        if ($action === 'disable') {
            app(ModuleGate::class)->change('wallet', false, $actor);
        }

        return ['transfers' => WalletTransfer::count(), 'policy' => WalletTransferPolicy::first()?->child_returns, 'own_cents' => app(Wallets::class)->statement($actor, $own->id)['balance_cents'], 'child_cents' => app(Wallets::class)->statement($actor, $child->id)['balance_cents'], 'healthy' => WalletTransferReconciliation::healthy(app(WalletTransferReconciliation::class)->inspect()), 'phase6_cents' => (int) DB::table('ledger_accounts')->where('school_id', app(CurrentSchool::class)->id())->whereIn('type', ['clearing', 'due_to_school'])->sum('balance_cents')];
    });
}
$data = require base_path('e2e/support/wallet-funding-fixture.php');

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $actor = User::where('email', $data['finance'])->firstOrFail();
    $actor->givePermissionTo(['wallet-transfer-policy-manage', 'wallet-desk-policy-manage']);
    app(WalletFundingService::class)->policy($actor, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true]);
    app(WalletFundingService::class)->record($actor, ['operation_id' => (string) Str::uuid(), 'method' => 'cash', 'source_reference' => 'SYNTHETIC-PARENT-TRANSFER-TEST', 'amount' => '100.00', 'received_on' => now()->toDateString(), 'school_owned' => true, 'evidence' => 'Synthetic parent transfer browser backing']);
    app(WalletDeskTopups::class)->policy($actor, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'threshold_amount' => '100.00']);
    app(WalletDeskTopups::class)->grant($actor, ['operation_id' => (string) Str::uuid(), 'owner_user_id' => $data['parents'][0], 'amount' => '50.00', 'reason' => 'Synthetic committed parent browser wallet', 'confirmed' => true]);
    $parent = User::findOrFail($data['parents'][0]);
    $child = Students::findOrFail($data['learners'][0]['id']);
    app(Wallets::class)->open($parent, ['operation_id' => (string) Str::uuid(), 'owner_user_id' => $child->user_id]);

    return $data + ['teacher_user' => (int) User::where('email', $data['teacher'])->firstOrFail()->id];
});
