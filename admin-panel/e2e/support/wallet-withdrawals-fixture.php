<?php

declare(strict_types=1);

use App\Models\Money\WalletAccount;
use App\Models\Money\WalletWithdrawal;
use App\Models\Money\WalletWithdrawalPolicy;
use App\Models\User;
use App\Services\Money\WalletDeposits;
use App\Services\Money\WalletWithdrawalReconciliation;
use App\Services\Money\WalletWithdrawals;
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
        $own = WalletAccount::sole();
        $finance = User::join('school_memberships as m', 'm.user_id', '=', 'users.id')->where('m.school_id', app(CurrentSchool::class)->id())->where('m.role', 'Finance')->firstOrFail(['users.*']);
        if ($action === 'disable') {
            app(ModuleGate::class)->change('wallet', false, $finance);
        }

        return ['withdrawals' => WalletWithdrawal::count(), 'paid' => DB::table('financial_disbursements')->where('school_id', app(CurrentSchool::class)->id())->count(), 'own_cents' => app(LedgerService::class)->balance($own->ledger_account_id)->cents, 'cash_at_boundary' => WalletWithdrawalPolicy::sole()->cash_at_boundary, 'healthy' => WalletWithdrawalReconciliation::healthy(app(WalletWithdrawalReconciliation::class)->inspect())];
    });
}
$data = require base_path('e2e/support/wallet-deposits-fixture.php');

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $finance = User::where('email', $data['finance'])->firstOrFail();
    $parent = User::findOrFail($data['parents'][0]);
    app(WalletDeposits::class)->verify($finance, $data['claim'], ['operation_id' => (string) Str::uuid(), 'amount' => '500.00', 'method' => 'eft', 'source_reference' => 'SYNTHETIC-VERIFIED-WITHDRAWAL-BACKING', 'received_on' => now()->toDateString(), 'evidence' => 'Independent synthetic original parent deposit verified', 'verified_original_holder' => true, 'verified_actual_receipt' => true]);
    app(WalletWithdrawals::class)->policy($finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_threshold_amount' => '250.00', 'cash_at_boundary' => false, 'accepting_requests' => true]);
    $start = now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->addDay()->startOfDay()->addHours(10);
    $slot = app(WalletWithdrawals::class)->publishSlot($finance, ['operation_id' => (string) Str::uuid(), 'starts_at' => $start->format('Y-m-d\TH:i'), 'ends_at' => $start->copy()->addHour()->format('Y-m-d\TH:i'), 'capacity' => 2]);
    $bank = app(WalletWithdrawals::class)->request($parent, ['operation_id' => (string) Str::uuid(), 'amount' => '300.00', 'private_bank_instructions' => 'Private synthetic original parent bank instructions', 'confirmed' => true]);
    $cash = app(WalletWithdrawals::class)->request($parent, ['operation_id' => (string) Str::uuid(), 'amount' => '10.00', 'slot_id' => $slot->id, 'confirmed' => true]);

    return $data + ['bank_withdrawal' => $bank->id, 'cash_withdrawal' => $cash->id];
});
