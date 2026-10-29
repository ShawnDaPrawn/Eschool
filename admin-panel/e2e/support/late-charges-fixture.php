<?php

declare(strict_types=1);
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeLateCharge;
use App\Models\Money\FeeLatePolicyRevision;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Money\FeePaymentReconciliation;
use App\Services\Money\FeeStatements;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Carbon;

$action = getenv('BILLING_FIXTURE_ACTION');
if (in_array($action, ['stats', 'pause'], true)) {
    $database = (string) config('database.connections.mysql.database');
    if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
        throw new RuntimeException('Synthetic isolated late-charge fixture only');
    }
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, function () use ($action): array {
        $finance = User::join('school_memberships as m', 'm.user_id', '=', 'users.id')->where('m.school_id', app(CurrentSchool::class)->id())->where('m.role', 'Finance')->firstOrFail(['users.*']);
        if ($action === 'pause') {
            app(ModuleGate::class)->change('fees', false, $finance);
        }

        return ['late_charges' => FeeLateCharge::count(), 'policy_revisions' => FeeLatePolicyRevision::count(), 'late_gross_cents' => (int) FeeLateCharge::sum('amount_cents'), 'healthy' => FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect())];
    });
}
$previous = Carbon::getTestNow();
Carbon::setTestNow(now()->subDays(60));
try {
    $data = require base_path('e2e/support/fee-credits-fixture.php');
} finally {
    Carbon::setTestNow($previous);
}

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $finance = User::where('email', $data['finance'])->firstOrFail();
    $finance->roles()->firstOrFail()->givePermissionTo('fee-plans-manage');
    $source = collect(app(FeeStatements::class)->charges(FeeAccount::findOrFail($data['account']), $data['parents'][0]))->first(fn ($row) => $row['outstanding_cents'] > 0);
    if ($source === null) {
        throw new RuntimeException('Original overdue fixture debt missing');
    }

    return $data + ['late_line' => $source['invoice_line_id']];
});
