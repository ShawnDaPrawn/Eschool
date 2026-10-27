<?php

declare(strict_types=1);

use App\Models\Money\BankStatementImport;
use App\Models\Money\BankStatementLine;
use App\Models\Money\BankStatementResolution;
use App\Models\Money\FeeAccount;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Money\FeePayments;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

$data = require base_path('e2e/support/fee-payments-fixture.php');
if (getenv('BILLING_FIXTURE_ACTION') === 'stats') {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, fn (): array => $data + [
        'imports' => BankStatementImport::whereNotNull('sealed_at')->count(), 'bank_lines' => BankStatementLine::count(),
        'matched' => BankStatementResolution::where('kind', 'matched')->count(), 'excluded' => BankStatementResolution::where('kind', 'excluded')->count(),
    ]);
}

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $reference = 'BANK-'.$data['school'].'-001';
    if (getenv('BANK_FIXTURE_EXISTING') === '1') {
        app(FeePayments::class)->record(User::where('email', $data['finance'])->firstOrFail(), [
            'operation_id' => (string) Str::uuid(), 'fee_account_id' => $data['account'], 'holder_user_id' => $data['parents'][0], 'pinned_learner_id' => null,
            'method' => 'eft', 'source_reference' => $reference, 'amount' => '4.00', 'received_on' => $data['date'], 'confirmed' => true,
        ]);
    }

    return $data + ['bank_reference' => $reference, 'family_reference' => FeeAccount::findOrFail($data['account'])->reference];
});
