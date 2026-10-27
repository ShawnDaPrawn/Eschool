<?php

declare(strict_types=1);

use App\Models\Money\FeePayment;
use App\Models\Money\PaymentAllocation;
use App\Models\Money\PaymentAllocationRun;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Money\BillRuns;
use App\Services\Money\FeeAccounts;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Fee receipt fixtures require an isolated test database.');
}
if (getenv('BILLING_FIXTURE_ACTION') === 'stats') {
    $schoolId = (string) getenv('BILLING_FIXTURE_SCHOOL');
    abort_unless(ctype_digit($schoolId), 422);
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) $schoolId);

    return app(CurrentSchool::class)->run($school, function () use ($school): array {
        $accounts = DB::table('ledger_accounts')->where('school_id', $school->id);

        return [
            'payments' => FeePayment::count(), 'allocations' => PaymentAllocation::count(), 'runs' => PaymentAllocationRun::whereNotNull('sealed_at')->count(),
            'entries' => DB::table('ledger_entries')->where('school_id', $school->id)->count(),
            'receivable_cents' => (int) (clone $accounts)->where('type', 'fee_receivable')->sum('balance_cents'),
            'income_cents' => (int) (clone $accounts)->where('type', 'fee_income')->sum('balance_cents'),
            'bank_cents' => (int) (clone $accounts)->where('type', 'fee_bank')->sum('balance_cents'),
            'credit_cents' => (int) (clone $accounts)->where('type', 'family')->sum('balance_cents'),
            'inactive_learners' => DB::table('students as s')->join('users as u', 'u.id', '=', 's.user_id')->where('s.school_id', $school->id)->where('u.status', 0)->count(),
        ];
    });
}
$data = require base_path('e2e/support/family-billing-fixture.php');

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $actor = User::where('email', $data['finance'])->firstOrFail();
    $actor->roles()->firstOrFail()->givePermissionTo('fee-payments-manage');
    $actor->unsetRelation('roles');
    $account = app(FeeAccounts::class)->save($actor, [
        'operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic receipt family', 'currency' => 'ZAR', 'session_year_id' => $data['year'],
        'learner_ids' => array_column($data['learners'], 'id'), 'holders' => array_map(fn ($id) => ['user_id' => $id, 'share_percent' => '50'], $data['parents']),
    ]);
    $run = app(BillRuns::class)->prepare($actor, ['operation_id' => (string) Str::uuid(), 'session_year_id' => $data['year'], 'as_of' => $data['date'], 'due_on' => $data['due'], 'tax_treatment' => 'no_vat']);
    app(BillRuns::class)->approve($actor, $run->id, ['operation_id' => (string) Str::uuid(), 'revision' => 1]);
    app(BillRuns::class)->post($actor, $run->id, ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'confirmed' => true]);

    return [...$data, 'account' => (int) $account->id];
});
