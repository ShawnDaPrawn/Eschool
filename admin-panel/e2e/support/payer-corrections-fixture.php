<?php

declare(strict_types=1);

use App\Models\Money\FeeAccount;
use App\Models\Money\FeePayerCorrection;
use App\Models\Platform\School;
use App\Services\Money\FeeStatements;
use App\Services\Platform\Tenancy\CurrentSchool;

$data = require base_path('e2e/support/fee-credits-fixture.php');
if (getenv('BILLING_FIXTURE_ACTION') === 'stats') {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, fn (): array => $data + ['corrections' => FeePayerCorrection::count()]);
}

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $source = collect(app(FeeStatements::class)->charges(FeeAccount::findOrFail($data['account']), $data['parents'][0]))->first(fn ($r) => $r['outstanding_cents'] >= 100);
    if ($source === null) {
        throw new RuntimeException('Synthetic original unpaid source missing');
    }

    return $data + ['correction_line' => $source['invoice_line_id'], 'recipient' => $data['parents'][1]];
});
