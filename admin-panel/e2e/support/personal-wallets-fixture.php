<?php

declare(strict_types=1);
use App\Models\Money\WalletAccount;
use App\Models\Money\WalletLimit;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\User;
use App\Services\Money\Wallets;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Wallet fixtures require an isolated test database.');
}
$action = getenv('BILLING_FIXTURE_ACTION');
if (in_array($action, ['issue', 'stats', 'disable'], true)) {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, function () use ($school, $action): array {
        $learner = Students::orderBy('id')->firstOrFail();
        $parent = User::join('guardian_links as g', 'g.guardian_user_id', '=', 'users.id')->where('g.school_id', $school->id)->where('g.learner_id', $learner->id)->orderBy('users.id')->firstOrFail(['users.*']);
        if ($action === 'issue') {
            app(Wallets::class)->open($parent, ['operation_id' => (string) Str::uuid(), 'owner_user_id' => $parent->id]);
            $wallet = app(Wallets::class)->open($parent, ['operation_id' => (string) Str::uuid(), 'owner_user_id' => $learner->user_id]);
            app(Wallets::class)->controls($parent, $wallet->id, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'daily_spend_cents' => 1000, 'all_categories' => false, 'categories' => ['food']]);
        }
        if ($action === 'disable') {
            app(ModuleGate::class)->change('wallet', false, $parent);
        }

        return ['wallets' => WalletAccount::count(), 'limits' => WalletLimit::count(), 'wallet_entries' => DB::table('ledger_entries as e')->join('ledger_postings as p', fn ($j) => $j->on('p.school_id', '=', 'e.school_id')->on('p.entry_id', '=', 'e.id'))->join('ledger_accounts as a', fn ($j) => $j->on('a.school_id', '=', 'p.school_id')->on('a.id', '=', 'p.account_id'))->where('e.school_id', $school->id)->where('a.module', 'wallet')->distinct()->count('e.id'), 'approved' => app(ApprovalService::class)->approved($learner), 'phase6_cents' => (int) DB::table('ledger_accounts')->where('school_id', $school->id)->whereIn('type', ['clearing', 'due_to_school'])->sum('balance_cents')];
    });
}
$data = require base_path('e2e/support/family-billing-fixture.php');

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $finance = User::where('email', $data['finance'])->firstOrFail();
    $finance->roles()->firstOrFail()->givePermissionTo('wallet-finance-manage');
    app(ModuleGate::class)->change('wallet', true, $finance);
    app(ModuleGate::class)->change('fees', false, $finance);
    foreach ($data['learners'] as $row) {
        $learner = Students::findOrFail($row['id']);
        LearnerApproval::create(['learner_id' => $learner->id, 'guardian_user_id' => $data['parents'][0], 'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        abort_unless(app(ApprovalService::class)->approved($learner), 403);
        User::whereKey($learner->user_id)->update(['status' => 1]);
    }

    return $data;
});
