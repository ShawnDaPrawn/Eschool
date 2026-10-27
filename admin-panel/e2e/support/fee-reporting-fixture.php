<?php

declare(strict_types=1);

use App\Models\Money\FeeAccountingSetting;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Money\FeePayments;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

$data = require base_path('e2e/support/fee-payments-fixture.php');
if (getenv('BILLING_FIXTURE_ACTION') === 'stats') {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, fn (): array => $data + ['mapping_revision' => FeeAccountingSetting::first()?->revision ?? 0]);
}

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    app(FeePayments::class)->record(User::where('email', $data['finance'])->firstOrFail(), [
        'operation_id' => (string) Str::uuid(), 'fee_account_id' => $data['account'], 'holder_user_id' => $data['parents'][0], 'pinned_learner_id' => null,
        'method' => 'eft', 'source_reference' => 'REPORT-BROWSER-'.$data['school'], 'amount' => '4.00', 'received_on' => $data['date'], 'confirmed' => true,
    ]);

    return $data;
});
