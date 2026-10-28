<?php

declare(strict_types=1);
use App\Models\Money\FeeCreditNote;
use App\Models\Money\FeeLeaverRun;
use App\Models\Money\Invoice;
use App\Models\People\Enrolment;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;

$data = require base_path('e2e/support/fee-payments-fixture.php');
if (getenv('BILLING_FIXTURE_ACTION') === 'stats') {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, fn (): array => $data + ['leaver_runs' => FeeLeaverRun::whereNotNull('sealed_at')->count(), 'credit_notes' => FeeCreditNote::count(), 'credited_cents' => (int) FeeCreditNote::sum('amount_cents'), 'original_invoice_cents' => (int) Invoice::sum('total_cents')]);
}

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    User::where('email', $data['finance'])->firstOrFail()->roles()->firstOrFail()->givePermissionTo('fee-credits-manage');
    $year = substr($data['date'], 0, 4);
    $start = new DateTimeImmutable($year.'-01-01');
    $end = new DateTimeImmutable($year.'-12-31');
    $departure = max($year.'-01-01', (new DateTimeImmutable($data['date']))->modify('-1 day')->format('Y-m-d'));
    Enrolment::where('learner_id', $data['learners'][0]['id'])->where('session_year_id', $data['year'])->update(['ends_on' => $departure]);
    $days = $start->diff(new DateTimeImmutable($departure))->days + 1;
    $whole = $start->diff($end)->days + 1;
    $served = intdiv(1001 * $days + intdiv($whole, 2), $whole);

    return $data + ['invoice' => Invoice::firstOrFail()->id, 'departure' => $departure, 'expected_credit_cents' => 1001 - $served];
});
