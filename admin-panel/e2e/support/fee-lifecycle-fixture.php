<?php

declare(strict_types=1);
use App\Models\People\Enrolment;
use App\Models\User;
use App\Services\Money\FeeAccounts;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

$data = require base_path('e2e/support/family-billing-fixture.php');
if (getenv('BILLING_FIXTURE_ACTION') === 'stats') {
    return $data;
}

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $actor = User::where('email', $data['finance'])->firstOrFail();
    $account = app(FeeAccounts::class)->save($actor, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $data['year'], 'name' => 'Synthetic enrolment family', 'currency' => 'ZAR', 'learner_ids' => array_column($data['learners'], 'id'), 'holders' => [['user_id' => $data['parents'][0], 'share_percent' => '50'], ['user_id' => $data['parents'][1], 'share_percent' => '50']]]);
    Enrolment::where('learner_id', $data['learners'][0]['id'])->where('session_year_id', $data['year'])->update(['starts_on' => $data['date']]);
    $year = substr($data['date'], 0, 4);
    $start = new DateTimeImmutable($year.'-01-01');
    $end = new DateTimeImmutable($year.'-12-31');
    $date = new DateTimeImmutable($data['date']);
    $days = $date->diff($end)->days + 1;
    $whole = $start->diff($end)->days + 1;
    $expected = intdiv(1001 * $days + intdiv($whole, 2), $whole);

    return $data + ['account' => $account->id, 'partial_cents' => $expected, 'expected_cents' => 1001 + $expected];
});
